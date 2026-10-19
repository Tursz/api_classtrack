<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupLessonController;
use App\Http\Controllers\IsolatedClassController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [UserController::class, 'store']);
Route::post('/verify-pin', [UserController::class, 'verifyPin']);
Route::post('/resend-pin', [UserController::class, 'resendPin']);
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/isolated-classes', IsolatedClassController::class);
    Route::apiResource('/learner', LearnerController::class);
    Route::apiResource('/plan', PlanController::class);
    Route::get('/payments/report', [PaymentController::class, 'report']);
    Route::apiResource('/payment', PaymentController::class);
    Route::apiResource('/group', GroupController::class);
    Route::apiResource('/lesson', LessonController::class)->only(['index', 'store', 'destroy', 'update']);
    Route::apiResource('/group-lesson', GroupLessonController::class)->only(['index', 'store', 'destroy', 'update']);
});


