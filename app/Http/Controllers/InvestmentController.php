<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Helpers\ApiResponse;
use App\Models\InvestmentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvestmentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $limit = $request->query("limit", 10);

            $user_id = auth()->id();
            $query = Investment::latest()
                ->where("user_id", $user_id);

            $investments = $query->paginate($limit);
            $payload = [
                'data' => $investments->items(),
                'meta' => [
                    'current_page' => $investments->currentPage(),
                    'from' => $investments->firstItem(),
                    'last_page' => $investments->lastPage(),
                    'path' => $investments->path(),
                    'per_page' => $investments->perPage(),
                    'to' => $investments->lastItem(),
                    'total' => $investments->total(),
                ],
                'links' => [
                    'first' => $investments->url(1),
                    'last' => $investments->url($investments->lastPage()),
                    'prev' => $investments->previousPageUrl(),
                    'next' => $investments->nextPageUrl(),
                ]
            ];

            return ApiResponse::success($payload);
        } catch (\Throwable $th) {
            return ApiResponse::error(
                'Internal server error',
                $th->getMessage()
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'type' => ['required', 'in:goal,stock'],
                    'description' => ['nullable', 'string'],
                    'target_amount' => ['required', 'numeric', 'min:0'],
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



            $investment = Investment::create($data);

            return ApiResponse::success(
                $investment,
                'Success create investment',
                201
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                'internal server error',
                $th->getMessage(),
                500
            );
        }
    }
}
