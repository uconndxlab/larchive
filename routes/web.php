<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemOhmsController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ExhibitController;
use App\Http\Controllers\ExhibitPageController;
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

// Exhibit routes
Route::resource('exhibits', ExhibitController::class);
Route::post('exhibits/{id}/restore', [ExhibitController::class, 'restore'])->name('exhibits.restore');
Route::delete('exhibits/{id}/force-delete', [ExhibitController::class, 'forceDelete'])->name('exhibits.force-delete');
Route::post('exhibits/{exhibit}/items/attach', [ExhibitController::class, 'attachItem'])->name('exhibits.items.attach');
Route::delete('exhibits/{exhibit}/items/{item}', [ExhibitController::class, 'detachItem'])->name('exhibits.items.detach');
Route::patch('exhibits/{exhibit}/items/reorder', [ExhibitController::class, 'reorderItems'])->name('exhibits.items.reorder');

// Exhibit page routes (nested under exhibits)
Route::get('exhibits/{exhibit}/pages', [ExhibitPageController::class, 'index'])->name('exhibits.pages.index');
Route::get('exhibits/{exhibit}/pages/create', [ExhibitPageController::class, 'create'])->name('exhibits.pages.create');
Route::post('exhibits/{exhibit}/pages', [ExhibitPageController::class, 'store'])->name('exhibits.pages.store');
Route::get('exhibits/{exhibit}/pages/{page}', [ExhibitPageController::class, 'show'])->name('exhibits.pages.show');
Route::get('exhibits/{exhibit}/pages/{page}/edit', [ExhibitPageController::class, 'edit'])->name('exhibits.pages.edit');
Route::patch('exhibits/{exhibit}/pages/{page}', [ExhibitPageController::class, 'update'])->name('exhibits.pages.update');
Route::delete('exhibits/{exhibit}/pages/{page}', [ExhibitPageController::class, 'destroy'])->name('exhibits.pages.destroy');

// Exhibit page item management (HTMX endpoints)
Route::post('exhibits/{exhibit}/pages/{page}/items/attach', [ExhibitPageController::class, 'attachItem'])->name('exhibits.pages.items.attach');
Route::patch('exhibits/{exhibit}/pages/{page}/items/{item}', [ExhibitPageController::class, 'updateItem'])->name('exhibits.pages.items.update');
Route::delete('exhibits/{exhibit}/pages/{page}/items/{item}', [ExhibitPageController::class, 'detachItem'])->name('exhibits.pages.items.detach');
Route::patch('exhibits/{exhibit}/pages/reorder', [ExhibitPageController::class, 'reorder'])->name('exhibits.pages.reorder');
Route::patch('exhibits/{exhibit}/pages/{page}/items/reorder', [ExhibitPageController::class, 'reorderItems'])->name('exhibits.pages.items.reorder');

