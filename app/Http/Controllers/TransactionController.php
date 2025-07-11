<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Laravel\Facades\Image;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query("limit", 10);
        $type = $request->query('type', 'all');

        $user_id = auth()->id();
        // Mulai query dasar
        $query = Transaction::latest()
            ->where("user_id", $user_id)
            ->with(['category', 'source']);

        // Tambahkan kondisi jika type bukan "all"
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Ambil hasil paginasi
        $transactions = $query->paginate($limit);
        $payload = [
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'from' => $transactions->firstItem(),
                'last_page' => $transactions->lastPage(),
                'path' => $transactions->path(),
                'per_page' => $transactions->perPage(),
                'to' => $transactions->lastItem(),
                'total' => $transactions->total(),
            ],
            'links' => [
                'first' => $transactions->url(1),
                'last' => $transactions->url($transactions->lastPage()),
                'prev' => $transactions->previousPageUrl(),
                'next' => $transactions->nextPageUrl(),
            ]
        ];
        return ApiResponse::success($payload);
    }


    public function detail($id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return ApiResponse::error(
                'error while get transaction',
                [
                    'id' => "Transaction with id {$id} not found"
                ],
                404
            );
        }

        return ApiResponse::success($transaction);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'attachment' => ['nullable', 'file', 'max:3072'], // max 3MB
                    'category_id' => ['required', 'uuid', 'exists:categories,id'],
                    'source_id' => ['required', 'uuid', 'exists:sources,id'],
                    'type' => ['required', 'in:income,expense'],
                    'description' => ['nullable', 'string'],
                    'amount' => ['required', 'numeric', 'min:0'],
                    'date' => ['required', 'date'],
                    'name' => ['required']
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
            // Handle file upload (if any)
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');

                // Buat nama file baru (webp)
                $filename = Str::uuid() . '.webp';
                $filepath = 'attachments/' . $filename;

                $manager = new ImageManager(new Driver());
                $image_final = $manager->read($file)
                    ->resize(600, 600)
                    ->toWebp(quality: 80);

                // Simpan ke storage
                Storage::disk('public')->put($filepath, $image_final);

                $data['attachment'] = $filepath;
            }

            $transaction = Transaction::create($data);

            $source->balance -= $request->amount;
            $source->save();

            return ApiResponse::success($transaction, 'Success create transaction', 201);
        } catch (\Throwable $th) {
            return ApiResponse::error('internal server error', $th->getMessage(), 500);
        }
    }

    public function expense_statistic()
    {
        $user_id = auth()->id();
        $data = Transaction::with('category')
            ->where('type', 'expense')
            ->where("user_id", $user_id)
            ->get()
            ->groupBy('category.name')
            ->map(function ($group, $categoryName) {
                return [
                    'name' => $categoryName,
                    'value' => $group->sum('amount'),
                    'selected' => true,
                ];
            })
            ->values();
        return ApiResponse::success($data);
    }

    public function weeklyActivity()
    {
        // ambil 7 hari terakhir (Senin - Minggu)
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        $user_id = auth()->id();

        // ambil data transaksi dari PostgreSQL
        $transactions = Transaction::where('user_id', $user_id)
            ->selectRaw("
            TO_CHAR(created_at, 'FMDay') as day,
            EXTRACT(DOW FROM created_at) as dow,
            type,
            SUM(amount) as total
        ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw("dow, day, type")
            ->orderByRaw("dow asc")
            ->get();

        // Susunan hari dalam PostgreSQL (0=Sunday, 6=Saturday)
        $dayOrder = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $result = [];

        foreach ($dayOrder as $dow => $day) {
            $dayFormatted = $day;

            $income = 0;
            $expense = 0;

            foreach ($transactions->where('day', $dayFormatted) as $trx) {
                if ($trx->type === 'income') $income = $trx->total;
                if ($trx->type === 'expense') $expense = $trx->total;
            }

            $result[] = [
                'day' => substr($day, 0, 3), // ex: 'Sun', 'Mon'
                'income' => (int) $income,
                'expense' => (int) $expense,
            ];
        }

        return ApiResponse::success($result);
    }
}
