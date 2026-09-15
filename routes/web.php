<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'auth.login')->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::livewire('/', 'dashboard.overview')->name('dashboard');
    Route::livewire('/phones', 'phones.index')->name('phones.index');
    Route::livewire('/phones/create', 'phones.create')->name('phones.create');
    Route::livewire('/phones/{phone}', 'phones.show')->name('phones.show');
    Route::livewire('/automations', 'automations.index')->name('automations.index');
});
