<?php

use App\Http\Controllers\ActualitesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PartenairesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/actualites', [ActualitesController::class, 'index'])->name('actualites.index');
Route::get('/actualites/{slug}', [ActualitesController::class, 'show'])->name('actualites.show');

Route::get('/partenaires', [PartenairesController::class, 'index'])->name('partenaires.index');
