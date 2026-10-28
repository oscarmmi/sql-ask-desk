<?php

use App\Http\Controllers\SqlAskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SqlAskController::class, 'index'])->name('home');
Route::post('/ask', [SqlAskController::class, 'ask'])->middleware('throttle:20,1')->name('ask');
