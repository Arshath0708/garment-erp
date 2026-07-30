<?php

use App\Http\Controllers\Masters\CategoryController;
use App\Http\Controllers\Masters\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagement\PermissionController;
use App\Http\Controllers\UserManagement\RoleController;
use App\Http\Controllers\UserManagement\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Masters
    |--------------------------------------------------------------------------
    | Built in dependency order — Category has no parent, Product needs it.
    | Agent, Buyer, Supplier, Jobber, PO Format and Markup follow.
    |
    | As with User Management, per-action permissions are declared inside each
    | controller's middleware() method, not here.
    */
    Route::prefix('masters')->name('masters.')->group(function () {

        Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
            ->name('categories.toggle-status');
        Route::resource('categories', CategoryController::class);

        // Declared before the resource so "check-code" is not swallowed by
        // products/{product}.
        Route::get('products/check-code', [ProductController::class, 'checkCode'])
            ->name('products.check-code');
        Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
            ->name('products.toggle-status');
        Route::resource('products', ProductController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    | Per-action permissions are declared inside each controller's
    | middleware() method rather than here, so a new action cannot be added
    | without also deciding its permission.
    */
    Route::prefix('user-management')->name('user-management.')->group(function () {

        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
        Route::resource('users', UserController::class);

        Route::resource('roles', RoleController::class);

        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions/sync', [PermissionController::class, 'sync'])->name('permissions.sync');
    });

});

require __DIR__.'/auth.php';
