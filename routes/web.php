<?php

use App\Http\Controllers\ActualitesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeClubController;
use App\Http\Controllers\PartenairesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/actualites', [ActualitesController::class, 'index'])->name('actualites.index');
Route::get('/actualites/{slug}', [ActualitesController::class, 'show'])->name('actualites.show');

Route::get('/partenaires', [PartenairesController::class, 'index'])->name('partenaires.index');

Route::get('/le-club', [LeClubController::class, 'index'])->name('le-club.index');
Route::get('/le-club/presentation', [LeClubController::class, 'presentation'])->name('le-club.presentation');
Route::get('/le-club/entraineurs', [LeClubController::class, 'entraineurs'])->name('le-club.entraineurs');
Route::get('/le-club/arbitres', [LeClubController::class, 'arbitres'])->name('le-club.arbitres');
Route::get('/le-club/bureau', [LeClubController::class, 'bureau'])->name('le-club.bureau');
Route::get('/le-club/commissions', [LeClubController::class, 'commissions'])->name('le-club.commissions');
