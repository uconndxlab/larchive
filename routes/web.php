<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemOhmsController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('collections', CollectionController::class);
Route::resource('items', ItemController::class);

// HTMX partial for transcript field
Route::get('items/transcript-field', [ItemController::class, 'transcriptField'])->name('items.transcript-field');

// OHMS routes
Route::post('items/{item}/ohms', [ItemOhmsController::class, 'store'])->name('items.ohms.store');

// Media routes
Route::post('items/{item}/media', [MediaController::class, 'store'])->name('items.media.store');
Route::get('items/{item}/media', [MediaController::class, 'index'])->name('items.media.index');
Route::patch('items/{item}/media/reorder', [MediaController::class, 'reorder'])->name('items.media.reorder');
Route::patch('media/{media}', [MediaController::class, 'update'])->name('media.update');
Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
