<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserGroupController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Clients
Route::middleware(['auth:sanctum'])->prefix('clients')->group(function () {
    Route::get('/', [ClientController::class, 'listClients']);
    Route::post('/', [ClientController::class, 'insertClient']);
    Route::put('/{id}', [ClientController::class, 'updateClient']);
    Route::delete('/{id}', [ClientController::class, 'deleteClient']);
});

// Avaliações
Route::middleware(['auth:sanctum'])->prefix('evaluations')->group(function () {
    Route::get('/', [EvaluationController::class, 'listEvaluations']);
    Route::post('/', [EvaluationController::class, 'insertEvaluation']);
    Route::put('/{id}', [EvaluationController::class, 'updateEvaluation']);
    Route::delete('/{id}', [EvaluationController::class, 'deleteEvaluation']);
});

// Providers
Route::middleware(['auth:sanctum'])->prefix('providers')->group(function () {
    Route::get('/', [ProviderController::class, 'listProviders']);
    Route::post('/', [ProviderController::class, 'insertProvider']);
    Route::put('/{id}', [ProviderController::class, 'updateProvider']);
    Route::delete('/{id}', [ProviderController::class, 'deleteProvider']);
});


// Categories
Route::middleware(['auth:sanctum'])->prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'listCategories']);
    Route::post('/', [CategoryController::class, 'insertCategory']);
    Route::put('/{id}', [CategoryController::class, 'updateCategory']);
    Route::delete('/{id}', [CategoryController::class, 'deleteCategory']);
});

// Locations
Route::middleware(['auth:sanctum'])->prefix('locations')->group(function () {
    Route::get('/', [LocationController::class, 'listLocations']);
    Route::post('/', [LocationController::class, 'insertLocation']);
    Route::put('/{id}', [LocationController::class, 'updateLocation']);
    Route::delete('/{id}', [LocationController::class, 'deleteLocation']);
});

// Users
Route::middleware(['auth:sanctum'])->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'list']);
    Route::post('/', [UserController::class, 'create']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'delete']);
});

// User Groups
Route::middleware(['auth:sanctum'])->prefix('groups')->group(function () {
    Route::get('/', [UserGroupController::class, 'list']);
    Route::post('/', [UserGroupController::class, 'create']);
    Route::put('/{id}', [UserGroupController::class, 'update']);
    Route::delete('/{id}', [UserGroupController::class, 'delete']);
});
