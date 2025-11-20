<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemOhmsController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ExhibitController;
use App\Http\Controllers\ExhibitPageController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Public routes - accessible to everyone, filtered by visibility
Route::get('/', function () {
    return view('welcome');
});

// Admin routes - require authentication and admin role
// These MUST come before public {item}/{collection}/{exhibit} routes to avoid conflicts
Route::middleware(['auth', 'admin'])->group(function () {
    // Collection management
    Route::get('collections/create', [CollectionController::class, 'create'])->name('collections.create');
    Route::post('collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::get('collections/{collection}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
    Route::put('collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
    Route::patch('collections/{collection}', [CollectionController::class, 'update']);
    Route::delete('collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');

    // Item management
    Route::get('items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('items', [ItemController::class, 'store'])->name('items.store');
    Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
    Route::put('items/{item}', [ItemController::class, 'update'])->name('items.update');
    Route::patch('items/{item}', [ItemController::class, 'update']);
    Route::delete('items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');

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

    // Exhibit management
    Route::get('exhibits/create', [ExhibitController::class, 'create'])->name('exhibits.create');
    Route::post('exhibits', [ExhibitController::class, 'store'])->name('exhibits.store');
    Route::get('exhibits/{exhibit}/edit', [ExhibitController::class, 'edit'])->name('exhibits.edit');
    Route::put('exhibits/{exhibit}', [ExhibitController::class, 'update'])->name('exhibits.update');
    Route::patch('exhibits/{exhibit}', [ExhibitController::class, 'update']);
    Route::delete('exhibits/{exhibit}', [ExhibitController::class, 'destroy'])->name('exhibits.destroy');
    Route::post('exhibits/{id}/restore', [ExhibitController::class, 'restore'])->name('exhibits.restore');
    Route::delete('exhibits/{id}/force-delete', [ExhibitController::class, 'forceDelete'])->name('exhibits.force-delete');
    Route::post('exhibits/{exhibit}/items/attach', [ExhibitController::class, 'attachItem'])->name('exhibits.items.attach');
    Route::delete('exhibits/{exhibit}/items/{item}', [ExhibitController::class, 'detachItem'])->name('exhibits.items.detach');
    Route::patch('exhibits/{exhibit}/items/reorder', [ExhibitController::class, 'reorderItems'])->name('exhibits.items.reorder');

    // Exhibit page routes (nested under exhibits)
    Route::get('exhibits/{exhibit}/pages', [ExhibitPageController::class, 'index'])->name('exhibits.pages.index');
    Route::get('exhibits/{exhibit}/pages/create', [ExhibitPageController::class, 'create'])->name('exhibits.pages.create');
    Route::post('exhibits/{exhibit}/pages', [ExhibitPageController::class, 'store'])->name('exhibits.pages.store');
    Route::get('exhibits/{exhibit}/pages/{page}/edit', [ExhibitPageController::class, 'edit'])->name('exhibits.pages.edit');
    Route::patch('exhibits/{exhibit}/pages/{page}', [ExhibitPageController::class, 'update'])->name('exhibits.pages.update');
    Route::delete('exhibits/{exhibit}/pages/{page}', [ExhibitPageController::class, 'destroy'])->name('exhibits.pages.destroy');

    // Exhibit page item management (HTMX endpoints)
    Route::post('exhibits/{exhibit}/pages/{page}/items/attach', [ExhibitPageController::class, 'attachItem'])->name('exhibits.pages.items.attach');
    Route::patch('exhibits/{exhibit}/pages/{page}/items/{item}', [ExhibitPageController::class, 'updateItem'])->name('exhibits.pages.items.update');
    Route::delete('exhibits/{exhibit}/pages/{page}/items/{item}', [ExhibitPageController::class, 'detachItem'])->name('exhibits.pages.items.detach');
    Route::patch('exhibits/{exhibit}/pages/reorder', [ExhibitPageController::class, 'reorder'])->name('exhibits.pages.reorder');
    Route::patch('exhibits/{exhibit}/pages/{page}/items/reorder', [ExhibitPageController::class, 'reorderItems'])->name('exhibits.pages.items.reorder');

    // Export routes
    Route::get('export', [ExportController::class, 'index'])->name('export.index');
    Route::post('export/omeka', [ExportController::class, 'omeka'])->name('export.omeka');
});

// Public browsing routes - use visibility scopes to filter content
// These MUST come after admin routes to avoid /items/create being matched as /items/{item}
Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');
Route::get('items', [ItemController::class, 'index'])->name('items.index');
Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('exhibits', [ExhibitController::class, 'index'])->name('exhibits.index');
Route::get('exhibits/{exhibit}', [ExhibitController::class, 'show'])->name('exhibits.show');
Route::get('exhibits/{exhibit}/pages/{page}', [ExhibitPageController::class, 'show'])->name('exhibits.pages.show');

