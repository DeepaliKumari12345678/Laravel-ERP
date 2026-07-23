<?php

use App\Http\Controllers\Api\WebserviceApiController;
use App\Http\Middleware\AuthenticateWebservice;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'app' => config('erp.name'),
    'version' => config('erp.version'),
    'status' => 'ok',
]))->name('health');

$resourcePattern = 'addresses|brands|categories|customers|orders|products|stock|suppliers';

Route::prefix('webservice')->middleware(AuthenticateWebservice::class)->group(function () use ($resourcePattern) {
    Route::match(['GET', 'HEAD'], '/', [WebserviceApiController::class, 'resources']);

    Route::match(['GET', 'HEAD', 'POST'], '/{resource}', [WebserviceApiController::class, 'collection'])
        ->where('resource', $resourcePattern);

    Route::match(['GET', 'HEAD', 'PUT', 'PATCH', 'DELETE'], '/{resource}/{id}', [WebserviceApiController::class, 'item'])
        ->where('resource', $resourcePattern)
        ->whereNumber('id');
});
