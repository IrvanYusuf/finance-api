<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Source;
use App\Models\Category;
use App\Models\Investment;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InvestmentTransaction;
use Illuminate\Support\Facades\Validator;

class InvestmentTransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user_id = auth()->id();
            $limit = $request->query("limit", 10);
            // $type = $request->query('type', 'all');

            // Mulai query dasar
            $query = InvestmentTransaction::latest()
                ->where("user_id", $user_id)
                ->with(['source', 'investment']);

            // Tambahkan kondisi jika type bukan "all"
            // if ($type !== 'all') {
            //     $query->where('type', $type);
            // }

            // Ambil hasil paginasi
            $investmentTransactions = $query->paginate($limit);
            $payload = [
                'data' => $investmentTransactions->items(),
                'meta' => [
                    'current_page' => $investmentTransactions->currentPage(),
                    'from' => $investmentTransactions->firstItem(),
                    'last_page' => $investmentTransactions->lastPage(),
                    'path' => $investmentTransactions->path(),
                    'per_page' => $investmentTransactions->perPage(),
                    'to' => $investmentTransactions->lastItem(),
                    'total' => $investmentTransactions->total(),
                ],
                'links' => [
                    'first' => $investmentTransactions->url(1),
                    'last' => $investmentTransactions->url($investmentTransactions->lastPage()),
                    'prev' => $investmentTransactions->previousPageUrl(),
                    'next' => $investmentTransactions->nextPageUrl(),
                ]
            ];
            return ApiResponse::success($payload);
        } catch (\Throwable $th) {
            return ApiResponse::error(
                'Internal server error',
                $th->getMessage(),
                500
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'source_id' => ['required', 'uuid', 'exists:sources,id'],
                    'notes' => ['nullable', 'string'],
                    'amount' => ['required', 'numeric', 'min:0'],
                    'investment_id' => ['required', 'uuid', 'exists:investments,id'],
                    'transaction_date' => ['required', 'date'],
                ]
            );

            if ($validator->fails()) {
                return ApiResponse::error(
                    'Validation Failed',
                    $validator->errors(),
                    422
                );
            }

            $data = $validator->validated();
            $user_id = auth()->id();
            $data['user_id'] = $user_id;

            $source = Source::where('id', $data['source_id'])
                ->where('user_id', auth()->id())
                ->firstOrFail();
            // cek jika amount di source nya tidak mencukupi
            if ($data['amount'] > $source->balance) {
                return ApiResponse::error(
                    "You don’t have enough money in this source.",
                    [
                        'source_id' => "You don’t have enough money in this source."
                    ],
                    400
                );
            }
            // 🟡 Begin Transaction
            DB::beginTransaction();
            $transaction = InvestmentTransaction::create($data);

            $investment = Investment::where('id', $data['investment_id'])
                ->where('user_id', auth()->id())
                ->firstOrFail();

            Transaction::create([
                'id' => Str::uuid(),
                'user_id' => auth()->id(),
                'name' => $data['notes'] ?? "Investment on {$investment->name} in {$data['transaction_date']}",
                'attachment' => null,
                'category_id' => Category::investment()->value('id'),
                'type' => 'expense',
                'description' => $data['notes'] ?? "Investment on {$investment->name} in {$data['transaction_date']}",
                'amount' => $data['amount'],
                'date' => $data['transaction_date'],
            ]);

            // kurangi balance
            $source->balance -= $data['amount'];
            $source->save();

            // tambah save amount di investment
            $investment->saved_amount += $data['amount'];
            $investment->save();

            // 🟢 Commit Transaction
            DB::commit();

            return ApiResponse::success($transaction, 'Success create investment transaction', 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ApiResponse::error(
                'Internal server error',
                $th->getMessage(),
                500
            );
        }
    }
}
