<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\InvestmentTransactionController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get("/me", [AuthController::class, 'me']);

    // transactions
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/expense-statistic', [TransactionController::class, 'expense_statistic']);

    Route::get('/transactions/weekly-activity', [TransactionController::class, 'weeklyActivity']);
    Route::get('/transactions/{id}', [TransactionController::class, 'detail']);



    // sources
    Route::get('/sources', [SourceController::class, 'index']);
    Route::get('/sources/bank', [SourceController::class, 'bank_sources']);
    Route::get('/sources/all', [SourceController::class, 'get_all_sources']);
    Route::post('/sources', [SourceController::class, 'store']);
    Route::post('/sources/top-up/{id}', [SourceController::class, 'top_up']);
    Route::get('/sources/{id}', [SourceController::class, 'show']);

    // category
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);


    // investment
    Route::get("/investments", [InvestmentController::class, 'index']);
    Route::post("/investments", [InvestmentController::class, 'store']);

    // investment transactions
    Route::get("/investment-transactions", [InvestmentTransactionController::class, 'index']);
    Route::post("/investment-transactions", [InvestmentTransactionController::class, 'store']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
