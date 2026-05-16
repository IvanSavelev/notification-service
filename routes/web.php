<?php

use App\Http\Controllers\ApiDocsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', [ApiDocsController::class, 'swaggerUi'])->name('api.docs');
Route::get('/docs/openapi.yaml', [ApiDocsController::class, 'openApiSpec'])->name('api.openapi');
