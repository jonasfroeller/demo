<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ChatClient;

Route::view('/', 'chat');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/chat', ChatClient::class);

require __DIR__.'/auth.php';
