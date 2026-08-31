<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/book')->name('home');
Route::inertia('/book', 'Book/Index')->name('book');

// PROTOTYPE: Terminal POS screen map — throwaway, not production.
Route::inertia('/prototype/terminal', 'Terminal/prototype/Index')->name('prototype.terminal');
