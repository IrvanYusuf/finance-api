<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Carbon\Carbon;
use App\Models\Source;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SourceController extends Controller
{
    public function index(Request $request)
    {
        $user_id = auth()->id();
        $limit = $request->query('limit', 10);
        $sources = Source::where('user_id', $user_id)
            ->latest()
            ->limit($limit)
            ->get();
        return ApiResponse::success(
            $sources,
            'Successfully get sources',
            200
        );
    }

    public function bank_sources()
    {
        $user_id = auth()->id();
        $sources = Source::bank()
            ->latest()
            ->where("user_id", $user_id)
            ->get();
        return ApiResponse::success(
            $sources,
            'Successfully get sources',
            200
        );
    }
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'type' => ['required', 'in:cash,bank,ewallet'],
                'provider' => ['nullable', 'string', 'max:255'],
                'account_number' => ['nullable', 'string', 'min:10', 'max:16'],
                'account_holder' => ['required', 'string', 'max:255'],
                'balance' => ['required', 'numeric', 'min:0'],
                'note' => ['nullable', 'string'],
                'color_card' => ['nullable', 'string', 'max:7'],
            ]);

            $validator->sometimes('account_number', ['required', 'string', 'min:10', 'max:16'], function ($input) {
                return $input->type !== 'cash';
            });

            $validator->sometimes('provider', ['required', 'string', 'max:255'], function ($input) {
                return $input->type !== 'cash';
            });


            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $data = $validator->validated();
            $data['user_id'] = auth()->id();

            $source = Source::create($data);

            return ApiResponse::success(
                $source,
                'Successfully created source',
                201
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                'Internal Server Error',
                $th->getMessage(),
                500
            );
        }
    }

    public function get_all_sources()
    {
        try {
            $user_id = auth()->id();
            $cashs = Source::cash()
                ->where("user_id", $user_id)
                ->get();
            $banks = Source::bank()
                ->where("user_id", $user_id)
                ->get();
            $ewallets = Source::ewallet()
                ->where("user_id", $user_id)
                ->get();

            $payload = [
                'cash' => $cashs,
                'bank' => $banks,
                'ewallet' => $ewallets
            ];

            return ApiResponse::success($payload);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    public function top_up(Request $request)
    {
        try {
            $request->validate([
                'balance' => 'required|numeric|min:1',
            ]);

            $source = Source::where('id', $request->id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Buat transaksi income (top up)
            Transaction::create([
                'id' => Str::uuid(),
                'user_id' => auth()->id(),
                'name' => "Top up balance {$request->balance} {$source->name}  in " . Carbon::now()->format('Y-m-d'),
                'attachment' => null,
                'category_id' => Category::topup()->value('id'),
                'type' => 'income',
                'description' => "Top up balance {$request->balance} {$source->name}  in " . Carbon::now()->format('Y-m-d'),
                'amount' => $request->balance,
                'date' => Carbon::now()->format('Y-m-d'),
            ]);

            $source->balance += $request->balance;
            $source->save();


            return ApiResponse::success(null, 'success top up');
        } catch (\Throwable $th) {
            return ApiResponse::error(
                'internal server error',
                $th->getMessage(),
                500
            );
        }
    }

    public function show(Request $request)
    {
        $source = Source::where('id', $request->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        return ApiResponse::success($source);
    }
}
