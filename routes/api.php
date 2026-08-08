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

    // Public API Wrappers
    Route::post('/contacts', [\Botble\Contact\Http\Controllers\API\ContactController::class, 'store']);
    Route::get('/agents', [\Botble\RealEstate\Http\Controllers\API\AccountController::class, 'index']);
    Route::get('/posts', [\Botble\Blog\Http\Controllers\API\PostController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        
        Route::post('/profile/update', [\App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
        Route::put('/password/change', [AuthController::class, 'changePassword']);
        
        // Account Data Routes
        Route::get('/properties', [\App\Http\Controllers\Api\AccountDataController::class, 'getProperties']);
        Route::get('/consults', [\App\Http\Controllers\Api\AccountDataController::class, 'getConsults']);
        Route::get('/reviews', [\App\Http\Controllers\Api\AccountDataController::class, 'getReviews']);
        Route::get('/invoices', [\App\Http\Controllers\Api\AccountDataController::class, 'getInvoices']);
        Route::get('/packages', [\App\Http\Controllers\Api\AccountDataController::class, 'getPackages']);
        Route::post('/packages/subscribe', [\App\Http\Controllers\Api\AccountDataController::class, 'subscribePackage']);
        
        Route::get('/privacy-settings', [PrivacySettingController::class, 'getSettings']);
        Route::post('/privacy-settings', [PrivacySettingController::class, 'updateSettings']);
        
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    });
});
