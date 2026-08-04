<?php

use App\Http\Controllers\Masters\BuyerController;
use App\Http\Controllers\Masters\AgentController;
use App\Http\Controllers\Masters\CategoryController;
use App\Http\Controllers\Masters\DocumentFormatController;
use App\Http\Controllers\Masters\GeoController;
use App\Http\Controllers\Masters\MarkupController;
use App\Http\Controllers\Masters\ProductController;
use App\Http\Controllers\Masters\SupplierController;
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

        /*
         * Cascading Country -> State -> City dropdowns. Shared reference data,
         * so these are guarded by `auth` alone rather than a module permission
         * — see GeoController.
         */
        Route::get('geo/states', [GeoController::class, 'states'])->name('geo.states');
        Route::get('geo/cities', [GeoController::class, 'cities'])->name('geo.cities');

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

        // Same ordering rule as products — before the resource, or
        // buyers/{buyer} matches "check-code" first.
        Route::get('buyers/check-code', [BuyerController::class, 'checkCode'])
            ->name('buyers.check-code');
        Route::patch('buyers/{buyer}/toggle-status', [BuyerController::class, 'toggleStatus'])
            ->name('buyers.toggle-status');
        Route::resource('buyers', BuyerController::class);

        /*
         * Same ordering rule again. "agents" here is the col X dropdown source
         * filtered by party type — it is a cascade endpoint on the Supplier
         * form, not the Agent master, which is masters.agents.* below.
         */
        Route::get('suppliers/check-code', [SupplierController::class, 'checkCode'])
            ->name('suppliers.check-code');
        Route::get('suppliers/agents', [SupplierController::class, 'agents'])
            ->name('suppliers.agents');
        Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])
            ->name('suppliers.toggle-status');
        Route::resource('suppliers', SupplierController::class);

        Route::get('agents/check-code', [AgentController::class, 'checkCode'])
            ->name('agents.check-code');
        Route::patch('agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus'])
            ->name('agents.toggle-status');
        Route::resource('agents', AgentController::class);

        /*
         * Order Formats. Bound as {format} rather than {documentFormat} — the
         * client calls these order formats, and the URL is the one place that
         * name is visible to them.
         */
        Route::patch('formats/{format}/toggle-status', [DocumentFormatController::class, 'toggleStatus'])
            ->name('formats.toggle-status');
        Route::resource('formats', DocumentFormatController::class)
            ->parameters(['formats' => 'format']);

        // Before the resource, or markups/{markup} matches "supplier-discount".
        Route::get('markups/supplier-discount', [MarkupController::class, 'supplierDiscount'])
            ->name('markups.supplier-discount');
        Route::patch('markups/{markup}/toggle-status', [MarkupController::class, 'toggleStatus'])
            ->name('markups.toggle-status');
        Route::resource('markups', MarkupController::class);
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
