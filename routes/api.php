<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PrivacySettingController;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);

    Route::get('/about', function () {
        return response()->json([
            'error' => false,
            'data' => [
                'content' => ''
            ]
        ]);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        
        Route::post('/profile/update', [\App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
        Route::put('/password/change', [AuthController::class, 'changePassword']);
        
        Route::get('/privacy-settings', [PrivacySettingController::class, 'getSettings']);
        Route::post('/privacy-settings', [PrivacySettingController::class, 'updateSettings']);
        
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    });
});
