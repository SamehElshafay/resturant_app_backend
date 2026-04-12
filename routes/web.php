<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::prefix('accounting')->group(function () {
        Route::get('/chart', [App\Http\Controllers\AccountingController::class, 'chart'])->name('accounting.chart');
        Route::post('/accounts', [App\Http\Controllers\AccountingController::class, 'storeAccount'])->name('accounting.accounts.store');
        Route::put('/accounts/{account}', [App\Http\Controllers\AccountingController::class, 'updateAccount'])->name('accounting.accounts.update');
        Route::delete('/accounts/{account}', [App\Http\Controllers\AccountingController::class, 'destroyAccount'])->name('accounting.accounts.destroy');
        Route::get('/vouchers-list', [App\Http\Controllers\AccountingController::class, 'vouchers'])->name('accounting.vouchers');
        Route::get('/accounting-reports', [App\Http\Controllers\AccountingController::class, 'reports'])->name('accounting.reports');
        Route::get('/statement/{account}', [App\Http\Controllers\AccountingController::class, 'statement'])->name('accounting.statement');

        // Entity Accounting Configs
        Route::get('/entity-configs', [App\Http\Controllers\AccountingController::class, 'entityConfigs'])->name('accounting.entity-configs');
        Route::post('/entity-configs', [App\Http\Controllers\AccountingController::class, 'storeEntityConfig'])->name('accounting.entity-configs.store');
        Route::delete('/entity-configs/{config}', [App\Http\Controllers\AccountingController::class, 'destroyEntityConfig'])->name('accounting.entity-configs.destroy');

        // Entity Types Management
        Route::post('/entity-types', [App\Http\Controllers\AccountingController::class, 'storeEntityType'])->name('accounting.entity-types.store');
        Route::delete('/entity-types/{type}', [App\Http\Controllers\AccountingController::class, 'destroyEntityType'])->name('accounting.entity-types.destroy');

        Route::get('/reports/trial-balance', [App\Http\Controllers\AccountingReportController::class, 'getTrialBalance'])->name('accounting.reports.trial-balance');
        Route::get('/reports/account-balance', [App\Http\Controllers\AccountingReportController::class, 'getAccountBalance'])->name('accounting.reports.account-balance');

        // Dynamic Accounting Engine (Scenarios)
        Route::get('/scenarios', [App\Http\Controllers\AccountingScenarioController::class, 'index'])->name('accounting.scenarios.index');
        Route::get('/scenarios/{scenario}', [App\Http\Controllers\AccountingScenarioController::class, 'show'])->name('accounting.scenarios.show');
        Route::post('/scenarios/{scenario}/toggle', [App\Http\Controllers\AccountingScenarioController::class, 'toggleScenario'])->name('accounting.scenarios.toggle');
        Route::post('/scenarios/{scenario}/steps', [App\Http\Controllers\AccountingScenarioController::class, 'storeStep'])->name('accounting.scenarios.steps.store');
        Route::put('/scenarios/steps/{step}', [App\Http\Controllers\AccountingScenarioController::class, 'updateStep'])->name('accounting.scenarios.steps.update');
        Route::delete('/scenarios/steps/{step}', [App\Http\Controllers\AccountingScenarioController::class, 'destroyStep'])->name('accounting.scenarios.steps.destroy');
    });

    Route::get('/branches', [App\Http\Controllers\BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [App\Http\Controllers\BranchController::class, 'store'])->name('branches.store');
    Route::get('/branches/{branch}', [App\Http\Controllers\BranchController::class, 'show'])->name('branches.show');
    Route::put('/branches/{branch}', [App\Http\Controllers\BranchController::class, 'update'])->name('branches.update');
    Route::delete('/branches/{branch}', [App\Http\Controllers\BranchController::class, 'destroy'])->name('branches.destroy');
    Route::get('/branches/{branch}/pos', [App\Http\Controllers\BranchController::class, 'pos'])->name('branches.pos');
    Route::post('/branches/{branch}/pos', [App\Http\Controllers\BranchController::class, 'storePos'])->name('branches.pos.store');

    Route::get('/tables', [App\Http\Controllers\TableController::class, 'index'])->name('tables.index');
    Route::post('/zones', [App\Http\Controllers\TableController::class, 'storeZone'])->name('zones.store');
    Route::put('/zones/{zone}', [App\Http\Controllers\TableController::class, 'updateZone'])->name('zones.update');
    Route::delete('/zones/{zone}', [App\Http\Controllers\TableController::class, 'destroyZone'])->name('zones.destroy');
    Route::post('/tables', [App\Http\Controllers\TableController::class, 'storeTable'])->name('tables.store');
    Route::put('/tables/{table}', [App\Http\Controllers\TableController::class, 'updateTable'])->name('tables.update');
    Route::delete('/tables/{table}', [App\Http\Controllers\TableController::class, 'destroyTable'])->name('tables.destroy');

    Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
    Route::post('/products/bulk-update-prices', [App\Http\Controllers\ProductController::class, 'bulkUpdatePrices'])->name('products.bulk-update-prices');
    Route::post('/products', [App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/employees', [App\Http\Controllers\EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/generate-pin', [App\Http\Controllers\EmployeeController::class, 'generatePin'])->name('employees.generate-pin');
    Route::post('/employees', [App\Http\Controllers\EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [App\Http\Controllers\EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/drivers', [App\Http\Controllers\DriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [App\Http\Controllers\DriverController::class, 'store'])->name('drivers.store');
    Route::put('/drivers/{driver}', [App\Http\Controllers\DriverController::class, 'update'])->name('drivers.update');
    Route::delete('/drivers/{driver}', [App\Http\Controllers\DriverController::class, 'destroy'])->name('drivers.destroy');

    Route::get('/suppliers', [App\Http\Controllers\SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [App\Http\Controllers\SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [App\Http\Controllers\SupplierController::class, 'destroy'])->name('suppliers.destroy');

    Route::get('/inventory', [App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::put('/inventory/{product}/{branch}', [App\Http\Controllers\InventoryController::class, 'updateStock'])->name('inventory.updateStock');

    // Ingredients
    Route::resource('ingredients', App\Http\Controllers\IngredientController::class);
    Route::post('productions/calculate', [App\Http\Controllers\ProductionController::class, 'calculate'])->name('productions.calculate');
    Route::resource('productions', App\Http\Controllers\ProductionController::class);

    // Purchase Invoices
    Route::get('purchase_invoices/{purchaseInvoice}/duplicate', [App\Http\Controllers\PurchaseInvoiceController::class, 'duplicate'])->name('purchase_invoices.duplicate');
    Route::resource('purchase_invoices', App\Http\Controllers\PurchaseInvoiceController::class);
    Route::get('purchase_invoices/{purchaseInvoice}/approve', [App\Http\Controllers\PurchaseInvoiceController::class, 'approve'])->name('purchase_invoices.approve');
    Route::get('purchase_invoices/{purchaseInvoice}/cancel', [App\Http\Controllers\PurchaseInvoiceController::class, 'cancel'])->name('purchase_invoices.cancel');

    // Recipes
    Route::get('products/{product}/recipe', [App\Http\Controllers\ProductController::class, 'getRecipe'])->name('products.recipe');
    Route::resource('recipes', App\Http\Controllers\RecipeController::class)->except(['edit', 'update']);

    // Expenses
    Route::resource('expenses', App\Http\Controllers\ExpenseController::class)->only(['index', 'create', 'store', 'destroy', 'edit', 'update', 'show']);
    Route::post('expenses/{expense}/approve', [App\Http\Controllers\ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::post('expenses/{expense}/cancel', [App\Http\Controllers\ExpenseController::class, 'cancelStatus'])->name('expenses.cancel');

    // Roles & Permissions
    Route::resource('roles', App\Http\Controllers\RoleController::class);

    // Vouchers (Receipt / Payment / Transfer)
    Route::get('/vouchers', [App\Http\Controllers\VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create', [App\Http\Controllers\VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers', [App\Http\Controllers\VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('/vouchers/{voucher}', [App\Http\Controllers\VoucherController::class, 'show'])->name('vouchers.show');
    Route::post('/vouchers/{voucher}/post', [App\Http\Controllers\VoucherController::class, 'post'])->name('vouchers.post');
    Route::post('/vouchers/{voucher}/cancel', [App\Http\Controllers\VoucherController::class, 'cancel'])->name('vouchers.cancel');

    // API: Account tree for dropdown filter
    Route::get('/api/accounts/tree', [App\Http\Controllers\VoucherController::class, 'accountTree'])->name('api.accounts.tree');
    Route::get('/api/accounts/next-code', [App\Http\Controllers\AccountingController::class, 'getNextCode'])->name('api.accounts.next-code');

    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');

    // System Settings & Token Management
    Route::get('/system/settings', [App\Http\Controllers\SystemSettingController::class, 'index'])->name('system.settings');
    Route::post('/system/settings/tokens', [App\Http\Controllers\SystemSettingController::class, 'updateTokenSettings'])->name('system.settings.tokens');
});

// Settings & Preferences
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['ar', 'en'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
});

Route::get('/theme-toggle', function () {
    $current = session('theme', 'light');
    session(['theme' => $current == 'light' ? 'dark' : 'light']);
    return redirect()->back();
});
