<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AgreementController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => 'api',
    'prefix' => 'v1'
], function ($router) {

    Route::group([
        'prefix' => 'auth'
    ], function ($router) {
        Route::post('/get_token', [AuthController::class, 'get_token']);
        Route::post('/get_qr', [AuthController::class, 'get_qr']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::group(['middleware' => ['auth:sanctum']], function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/change_mode/{role_type_id}', [AuthController::class, 'change_mode']);
            Route::post('/change_language/{lang_tag}', [AuthController::class, 'change_language']);
            //Route::post('/change_mode/{role_type_id}', [AuthController::class, 'change_mode']);
            //Route::post('/change_language/{lang_tag}', [AuthController::class, 'change_language']);
            //Route::post('/change_theme/{theme_slug}', [AuthController::class, 'change_theme']);
            //Route::post('/change_location/{location_id}', [AuthController::class, 'change_location']);
            // Route::post('/update', [AuthController::class, 'update']);
            // Route::post('/upload_avatar', [AuthController::class, 'upload_avatar']);
            // Route::post('/delete_avatar', [AuthController::class, 'delete_avatar']);
            // Route::post('/change_password', [AuthController::class, 'change_password']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::group([
        'prefix' => 'users'
    ], function ($router) {
        Route::group(['middleware' => ['auth:sanctum']], function () {
            Route::get('/get/{iin}', [UserController::class, 'get_by_iin']);
            Route::post('/get', [UserController::class, 'get']);
            Route::post('/get/{user_id}', [UserController::class, 'get_user']);
            Route::post('/get_user_attributes', [UserController::class, 'get_user_attributes']);
            Route::post('/update', [UserController::class, 'update_user']);
        });
    });

    Route::group([
        'prefix' => 'agreement'
    ], function ($router) {
        Route::group(['middleware' => ['auth:sanctum']], function () {
            Route::get('/get_attributes', [AgreementController::class, 'get_attributes']);
            Route::get('/get_my_templates', [AgreementController::class, 'get_my_templates']);
            Route::post('/get', [AgreementController::class, 'get_agreements']);
            Route::post('/save', [AgreementController::class, 'save']);
            Route::post('/sign/{uuid}', [AgreementController::class, 'sign']);
            Route::get('/sign/verify/{uuid}', [AgreementController::class, 'sign_verify']);
            Route::post('/get/{uuid}', [AgreementController::class, 'get_agreement']);
            Route::get('/cms/{type}/{uuid}', [AgreementController::class, 'get_cms_file']);
        });
    });

    Route::group([
        'prefix' => 'document'
    ], function ($router) {
        Route::get('/get/{uuid}', [DocumentController::class, 'get_document']);
        Route::get('/get_file/{document}/{type}/{uuid}', [DocumentController::class, 'get_file'])->middleware('throttle:10,1'); // 10 попыток на одну минуту
    });
});