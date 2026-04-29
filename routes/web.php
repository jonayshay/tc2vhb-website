<?php

use App\Http\Controllers\ActualitesController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/actualites', [ActualitesController::class, 'index'])->name('actualites.index');
Route::get('/actualites/{slug}', [ActualitesController::class, 'show'])->name('actualites.show');
