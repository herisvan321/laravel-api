<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return ApiResponse::success($request->user(), 'User profile retrieved successfully');
})->middleware('auth:sanctum');

Route::get('/hello', function () {
    return ApiResponse::success([
        'framework' => 'Laravel',
        'version' => app()->version(),
        'status' => 'active',
    ], 'Data retrieved successfully');
});
