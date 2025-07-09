<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

//Rota para a pagina HOME quando acedo a '/'
Route::get('/', function () {
    return Inertia::render('index');
})->name('index');

Route::redirect('/home', '/')->name('home'); //Redireciona para / que é a pagina HOME

// Tudo que estiver dentro deste middleware vai ser verificado pela autenticacao
// Se nao tiver sessao iniciada ao tentar aceder a qualquer pagina serei reencaminhado para a pagina de login.
Route::middleware([
    'auth:sanctum', // Autentica o user via session (nao token) usando o laravel sanctrum.
    config('jetstream.auth_session'), // Assegura que a sessao esta devidamente configurada
    'verified',
])->group(function () {
    //Rota para a pagina de dashboard quando acedo a '/dashboard'
//    Route::get('/dashboard', function () {
//        return Inertia::render('Dashboard');
//    })->name('dashboard');

    //Rota para a página product quando acedo a '/products'
    Route::get('/evaluations', function () {
        return Inertia::render('Dashboard/Evaluation');
    })->middleware('check.permission:product,list')->name('evaluations');

    //Rota para a página product quando acedo a '/clients'
    Route::get('/clients', function () {
        return Inertia::render('Dashboard/Client');
    })->middleware('check.permission:client,list')->name('clients');

    //Rota para a página provider quando acedo a '/providers'
    Route::get('/providers', function () {
        return Inertia::render('Provider');
    })->middleware('check.permission:provider,list')->name('providers');

    //Rota para a página location quando acedo a '/locations'
    Route::get('/locations', function () {
        return Inertia::render('Location');
    })->middleware('check.permission:location,list')->name('locations');

    //Rota para a página category quando acedo a '/categories'
    Route::get('/categories', function () {
        return Inertia::render('Category');
    })->middleware('check.permission:category,list')->name('categories');

    //Rota para a página user quando acedo a '/users'
    Route::get('/users', function () {
        return Inertia::render('User');
    })->middleware('check.permission:user,list')->name('users');

    //Rota para a página userGroup quando acedo a '/usergroups'
    Route::get('/usergroups', function () {
        return Inertia::render('UserGroup');
    })->name('user_groups');
});


