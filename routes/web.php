<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/book')->name('home');
Route::inertia('/book', 'Book/Index')->name('book');
