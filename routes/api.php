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
                'content' => '<p>About us content goes here.</p>'
            ]
        ]);
    });

    Route::get('/terms', function () {
        return response()->json([
            'error' => false,
            'data' => [
                'content' => '<h1>Terms of Service</h1><p>Welcome to Homezen. By using our app, you agree to our terms of service.</p>'
            ]
        ]);
    });

    Route::get('/privacy', function () {
        return response()->json([
            'error' => false,
            'data' => [
                'content' => '<h1>Privacy Policy</h1><p>We value your privacy. We do not sell your personal data.</p>'
            ]
        ]);
    });

    Route::get('/help', function () {
        return response()->json([
            'error' => false,
            'data' => [
                'content' => '<h1>Help Center</h1><p>Welcome to the Homezen Help Center. Here you can find answers to frequently asked questions and get support for any issues you might be having.</p>'
            ]
        ]);
    });

    Route::get('/neighborhood-issue', function () {
        return response()->json([
            'error' => false,
            'data' => [
                'content' => '<h1>Submit a Neighborhood Issue</h1><p>If you see a safety concern, noise complaint, or property issue in your neighborhood, please let us know. We take neighborhood harmony very seriously. Contact local authorities for immediate emergencies.</p>'
            ]
        ]);
    });

    Route::post('/feedback', function () {
        return response()->json([
            'error' => false,
            'message' => 'Feedback submitted successfully'
        ]);
    });

    // Public API Wrappers
    Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'index']);
    Route::get('/properties/id/{id}', [\Botble\RealEstate\Http\Controllers\API\PropertyController::class, 'show']);
    Route::get('/properties/{property_id}/reviews', [\Botble\RealEstate\Http\Controllers\API\ReviewController::class, 'index']);
    Route::post('/contacts', [\Botble\Contact\Http\Controllers\API\ContactController::class, 'store']);
    Route::get('/agents', [\Botble\RealEstate\Http\Controllers\API\AccountController::class, 'index']);
    Route::get('/posts', [\Botble\Blog\Http\Controllers\API\PostController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        
        Route::post('/profile/update', [\App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
        Route::put('/password/change', [AuthController::class, 'changePassword']);
        
        Route::post('/properties/{property_id}/reviews', [\Botble\RealEstate\Http\Controllers\API\ReviewController::class, 'store']);
        
        // Account Data Routes
        Route::get('/trips', [\App\Http\Controllers\Api\AccountDataController::class, 'getTrips']);
        Route::get('/properties', [\App\Http\Controllers\Api\AccountDataController::class, 'getProperties']);
        Route::get('/consults', [\App\Http\Controllers\Api\AccountDataController::class, 'getConsults']);
        Route::get('/reviews', [\App\Http\Controllers\Api\AccountDataController::class, 'getReviews']);
        Route::get('/invoices', [\App\Http\Controllers\Api\AccountDataController::class, 'getInvoices']);
        Route::get('/packages', [\App\Http\Controllers\Api\AccountDataController::class, 'getPackages']);
        Route::post('/packages/subscribe', [\App\Http\Controllers\Api\AccountDataController::class, 'subscribePackage']);
        
        Route::get('/privacy-settings', [PrivacySettingController::class, 'getSettings']);
        Route::post('/privacy-settings', [PrivacySettingController::class, 'updateSettings']);
        
        Route::get('/chats', [\App\Http\Controllers\Api\ChatController::class, 'index']);
        Route::post('/chats', [\App\Http\Controllers\Api\ChatController::class, 'startConversation']);
        Route::get('/chats/{id}/messages', [\App\Http\Controllers\Api\ChatController::class, 'show']);
        Route::post('/chats/{id}/messages', [\App\Http\Controllers\Api\ChatController::class, 'store']);
        
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    });
});
