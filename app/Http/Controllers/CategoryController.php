<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $limit = $request->query("limit", 10);
            $query = Category::latest()
                ->where('user_id', auth()->id())
                ->orWhereNull('user_id');
            $categories = $query->paginate($limit);
            $payload = [
                'data' => $categories->items(),
                'meta' => [
                    'current_page' => $categories->currentPage(),
                    'from' => $categories->firstItem(),
                    'last_page' => $categories->lastPage(),
                    'path' => $categories->path(),
                    'per_page' => $categories->perPage(),
                    'to' => $categories->lastItem(),
                    'total' => $categories->total(),
                ],
                'links' => [
                    'first' => $categories->url(1),
                    'last' => $categories->url($categories->lastPage()),
                    'prev' => $categories->previousPageUrl(),
                    'next' => $categories->nextPageUrl(),
                ]
            ];
            return ApiResponse::success($payload);
        } catch (\Throwable $th) {
            return ApiResponse::error('internal server error', $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => ['required', 'min:3', 'unique:categories,name'],
                ]
            );

            if ($validator->fails()) {
                return ApiResponse::error(
                    'Validation Failed',
                    $validator->errors(),
                    422
                );
            }
            $validated = $validator->validated();

            // Tambahkan user_id dari user yang login
            $validated['user_id'] = auth()->id();


            $categories = Category::create($validated);

            return ApiResponse::success(
                $categories,
                'success create category',
                201
            );
        } catch (\Throwable $th) {
            return ApiResponse::error('internal server error', $th->getMessage());
        }
    }
}
