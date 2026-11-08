<?php

use App\Http\Controllers\AskController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('chat'))->name('chat');

Route::post('/ask', AskController::class)->name('ask');
