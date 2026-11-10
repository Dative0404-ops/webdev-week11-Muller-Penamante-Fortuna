<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Week 10 pages (kept).
Route::get('/', [ReportController::class, 'home'])->name('home');
Route::get('/report', [ReportController::class, 'index'])->name('report');

// Week 11 pages.
Route::get('/events',         [EventController::class, 'index'])->name('events.index');
Route::get('/events/naive',   [EventController::class, 'naive'])->name('events.naive');
Route::get('/events/jsonb',   [EventController::class, 'jsonbFilter'])->name('events.jsonb');
Route::get('/events/search',  [EventController::class, 'search'])->name('events.search');
Route::get('/events/scopes',  [EventController::class, 'scopesDemo'])->name('events.scopes');
