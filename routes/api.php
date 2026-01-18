<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProductModifierOptionController;
use App\Http\Controllers\ParentCategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppDataController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CartsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommercialPlaceCommissionController;
use App\Http\Controllers\CommercialPlaceController;
use App\Http\Controllers\CommissionTypeController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverRequestController;
use App\Http\Controllers\DriverServiceController;
use App\Http\Controllers\FavoritePlaceController;
use App\Http\Controllers\FavoriteProductController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\MerchantServiceController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ModifierOptionsController;
use App\Http\Controllers\ModifiersController;
use App\Http\Controllers\MultiOfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderCouponController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\PhoneNumberController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductModifiersController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SingleOfferController;
use App\Http\Controllers\ZoneController;
use App\Http\Middleware\CheckCommercialPlace;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckVerification;
use App\Models\Method;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
    Route::get('logout', [AdminController::class, 'logout']);

    Route::prefix('driver')->group(function () {
        Route::get('/{id}', [DriverController::class, 'show']);
        Route::get('/', [DriverController::class, 'index']);
        Route::post('/add-driver', [DriverController::class, 'addDriver']);
        Route::post('/update-driver', [DriverController::class, 'updateDriver']);
    });

    Route::prefix('merchant-services')->group(function () {
        Route::get('/{id}', [MerchantServiceController::class, 'show']);
        Route::get('/', [MerchantServiceController::class, 'index']);
        Route::post('/change-state', [MerchantServiceController::class, 'changeState']);
        Route::delete('/{id}', [MerchantServiceController::class, 'destroy']);
    });

    Route::prefix('driver-services')->group(function () {
        Route::get('/{id}', [DriverServiceController::class, 'show']);
        Route::get('/', [DriverServiceController::class, 'index']);
        Route::post('/change-state', [DriverServiceController::class, 'changeState']);
        Route::delete('/{id}', [DriverServiceController::class, 'destroy']);
    });

    Route::prefix('banners')->group(function () {
        Route::get('/', [AppDataController::class, 'index'])->middleware(CheckPermission::class . ':banners_management');
        Route::get('/{id}', [AppDataController::class, 'show'])->middleware(CheckPermission::class . ':banners_management');
        Route::post('/', [AppDataController::class, 'store'])->middleware(CheckPermission::class . ':banners_management');
        Route::post('/{id}', [AppDataController::class, 'update'])->middleware(CheckPermission::class . ':banners_management');
        Route::delete('/{id}', [AppDataController::class, 'destroy'])->middleware(CheckPermission::class . ':banners_management');
    });

    Route::prefix('admin_management')->group(function(){
        Route::post('/store', [AdminController::class, 'store'])->middleware(CheckPermission::class . ':admins_management');
        Route::post('/update', [AdminController::class, 'update'])->middleware(CheckPermission::class . ':admins_management');
        Route::get('/getAllAdmin', [AdminController::class, 'index'])->middleware(CheckPermission::class . ':admins_management');
        Route::get('/show/{id}', [AdminController::class, 'show'])->middleware(CheckPermission::class . ':admins_management');
        Route::delete('/delete/{id}', [AdminController::class, 'destroy'])->middleware(CheckPermission::class . ':admins_management');
    });
    
    Route::prefix('roles')->group(function () {
        Route::post('/addRole', [RolesController::class, 'store'])->middleware(CheckPermission::class . ':roles_management');
        Route::post('/updateRole', [RolesController::class, 'update'])->middleware(CheckPermission::class . ':roles_management');
        Route::get('/listAllRoles', [RolesController::class, 'index'])->middleware(CheckPermission::class . ':roles_management');
        Route::get('/showRole/{id}', [RolesController::class, 'show'])->middleware(CheckPermission::class . ':roles_management');
        Route::delete('/deleteRole/{id}', [RolesController::class, 'destroy'])->middleware(CheckPermission::class . ':roles_management');
    });
    
    Route::prefix('permission')->group(function () {
        Route::post('/addPermission', [PermissionsController::class, 'store'])->middleware(CheckPermission::class . ':permission_management');
        Route::post('/updatePermission', [PermissionsController::class, 'update'])->middleware(CheckPermission::class . ':permission_management');
        Route::get('/listAllPermissions', [PermissionsController::class, 'index'])->middleware(CheckPermission::class . ':permission_management');
        Route::get('/showPermission/{id}', [PermissionsController::class, 'show'])->middleware(CheckPermission::class . ':permission_management');
        Route::delete('/deletePermission/{id}', [PermissionsController::class, 'destroy'])->middleware(CheckPermission::class . ':permission_management');
    });
    
    Route::prefix('RolePermissions')->group(function () {
        Route::post('/linkRolePermision', [RolePermissionController::class, 'linkRolePermision'])->middleware(CheckPermission::class . ':role_permision_management');
    });

    Route::prefix('commercial-places')->group(function () {
        Route::get('/', [CommercialPlaceController::class, 'index'])->middleware(CheckPermission::class . ':commercial_places_management');
        Route::get('/{id}', [CommercialPlaceController::class, 'show'])->middleware(CheckPermission::class . ':commercial_places_management');
        Route::post('/', [CommercialPlaceController::class, 'store'])->middleware(CheckPermission::class . ':commercial_places_management');
        Route::put('/{id}', [CommercialPlaceController::class, 'update'])->middleware(CheckPermission::class . ':commercial_places_management');
        Route::post('/{id}', [CommercialPlaceController::class, 'update'])->middleware(CheckPermission::class . ':commercial_places_management');
        Route::delete('/{id}', [CommercialPlaceController::class, 'destroy'])->middleware(CheckPermission::class . ':commercial_places_management');
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->middleware(CheckPermission::class . ':categories_management');;
        Route::get('/{id}', [CategoryController::class, 'show'])->middleware(CheckPermission::class . ':categories_management');;
        Route::post('/', [CategoryController::class, 'store'])->middleware(CheckPermission::class . ':categories_management');;
        Route::post('/{id}', [CategoryController::class, 'update'])->middleware(CheckPermission::class . ':categories_management');;
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware(CheckPermission::class . ':categories_management');;
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->middleware(CheckPermission::class . ':products_management');
        Route::get('/{id}', [ProductController::class, 'show'])->middleware(CheckPermission::class . ':products_management');
        Route::post('/', [ProductController::class, 'store'])->middleware(CheckPermission::class . ':products_management');
        Route::post('/{id}', [ProductController::class, 'update'])->middleware(CheckPermission::class . ':products_management');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware(CheckPermission::class . ':products_management');
    });

    Route::prefix('modifiers')->group(function () {
        Route::get('/', [ModifiersController::class, 'index'])->middleware(CheckPermission::class . ':modifiers_management');
        Route::get('/{id}', [ModifiersController::class, 'show'])->middleware(CheckPermission::class . ':modifiers_management');
        Route::post('/', [ModifiersController::class, 'store'])->middleware(CheckPermission::class . ':modifiers_management');
        Route::patch('/{id}', [ModifiersController::class, 'update'])->middleware(CheckPermission::class . ':modifiers_management');
        Route::delete('/{id}', [ModifiersController::class, 'destroy'])->middleware(CheckPermission::class . ':modifiers_management');
    });

    Route::prefix('modifier-options')->group(function () {
        Route::get('modifiers/{modifier_id}/options', [ModifierOptionsController::class, 'index'])->middleware(CheckPermission::class . ':modifier_options_management');
        Route::post('/', [ModifierOptionsController::class, 'store'])->middleware(CheckPermission::class . ':modifier_options_management');
        Route::patch('/{id}', [ModifierOptionsController::class, 'update'])->middleware(CheckPermission::class . ':modifier_options_management');
        Route::delete('/{id}', [ModifierOptionsController::class, 'destroy'])->middleware(CheckPermission::class . ':modifier_options_management');
    });

    Route::prefix('modifier-products')->group(function () {
        Route::get('/', [ProductModifiersController::class, 'index'])->middleware(CheckPermission::class . ':modifier_products_management');
        Route::get('/{product_id}', [ProductModifiersController::class, 'show'])->middleware(CheckPermission::class . ':modifier_products_management');
        Route::post('/', [ProductModifiersController::class, 'store'])->middleware(CheckPermission::class . ':modifier_products_management');
        Route::patch('/{product_id}', [ProductModifiersController::class, 'update'])->middleware(CheckPermission::class . ':modifier_products_management');
        Route::delete('/{product_id}', [ProductModifiersController::class, 'destroy'])->middleware(CheckPermission::class . ':modifier_products_management');
    });

    Route::prefix('commercial-place-commission')->group(function () {
        Route::get('/', [CommercialPlaceCommissionController::class, 'index'])->middleware(CheckPermission::class . ':commercial_place_commissiont_management');
        Route::get('/{id}', [CommercialPlaceCommissionController::class, 'show'])->middleware(CheckPermission::class . ':commercial_place_commissiont_management');
        Route::post('/', [CommercialPlaceCommissionController::class, 'store'])->middleware(CheckPermission::class . ':commercial_place_commissiont_management');
        Route::put('/{id}', [CommercialPlaceCommissionController::class, 'update'])->middleware(CheckPermission::class . ':commercial_place_commissiont_management');
        Route::delete('/{id}', [CommercialPlaceCommissionController::class, 'destroy'])->middleware(CheckPermission::class . ':commercial_place_commissiont_management');
    });

    Route::prefix('commission-type')->group(function () {
        Route::get('/', [CommissionTypeController::class, 'index'])->middleware(CheckPermission::class . ':commission_type_management');
        Route::get('/{id}', [CommissionTypeController::class, 'show'])->middleware(CheckPermission::class . ':commission_type_management');
        Route::post('/', [CommissionTypeController::class, 'store'])->middleware(CheckPermission::class . ':commission_type_management');
        Route::put('/{id}', [CommissionTypeController::class, 'update'])->middleware(CheckPermission::class . ':commission_type_management');
        Route::delete('/{id}', [CommissionTypeController::class, 'destroy'])->middleware(CheckPermission::class . ':commission_type_management');
    });

    Route::prefix('parent_categor')->group(function () {
        Route::get('/', [ParentCategoryController::class, 'index'])->middleware(CheckPermission::class . ':parent_categories_management');
        Route::get('/{id}', [ParentCategoryController::class, 'show'])->middleware(CheckPermission::class . ':parent_categories_management');
        Route::post('/', [ParentCategoryController::class, 'store'])->middleware(CheckPermission::class . ':parent_categories_management');
        Route::put('/{id}', [ParentCategoryController::class, 'update'])->middleware(CheckPermission::class . ':parent_categories_management');
        Route::patch('/{id}', [ParentCategoryController::class, 'update'])->middleware(CheckPermission::class . ':parent_categories_management');
        Route::delete('/{id}', [ParentCategoryController::class, 'destroy'])->middleware(CheckPermission::class . ':parent_categories_management');
    });

    Route::prefix('product_modifiers_oprions')->group(function () {
        Route::get('/',       [ProductModifierOptionController::class, 'index'])->middleware(CheckPermission::class . ':product_modifier_options_management');
        Route::post('/',      [ProductModifierOptionController::class, 'store'])->middleware(CheckPermission::class . ':product_modifier_options_management');
        Route::get('{id}',    [ProductModifierOptionController::class, 'show'])->middleware(CheckPermission::class . ':product_modifier_options_management');
        Route::post('{id}',   [ProductModifierOptionController::class, 'update'])->middleware(CheckPermission::class . ':product_modifier_options_management');
        Route::delete('{id}', [ProductModifierOptionController::class, 'destroy'])->middleware(CheckPermission::class . ':product_modifier_options_management');
    });

    Route::prefix('merchant')->group(function () {
        Route::get('/',       [MerchantController::class, 'index']);
        Route::post('/',      [MerchantController::class, 'register']);
        Route::get('{id}',    [MerchantController::class, 'show']);
        Route::post('{id}',   [MerchantController::class, 'update']);
        Route::delete('{id}', [MerchantController::class, 'destroy']);
    });

    Route::prefix('offers')->group(function () {
        Route::get('/', [MultiOfferController::class, 'index']);
        Route::get('{id}', [MultiOfferController::class, 'show']);
        Route::post('/', [MultiOfferController::class, 'store']);
        Route::post('/updateOffer', [MultiOfferController::class, 'update']);
        Route::delete('{id}', [MultiOfferController::class, 'destroy']);

        Route::post('{id}/add-product', [MultiOfferController::class, 'addProduct']);
        Route::post('{id}/remove-product', [MultiOfferController::class, 'removeProduct']);
    });

    Route::prefix('single-offers')->group(function () {
        Route::get('/', [SingleOfferController::class, 'index']);
        Route::get('{id}', [SingleOfferController::class, 'show']);
        Route::post('/', [SingleOfferController::class, 'store']);
        Route::post('/update', [SingleOfferController::class, 'update']);
        Route::delete('{id}', [SingleOfferController::class, 'destroy']);
    });
    
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::get('{id}', [AppointmentController::class, 'show']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::put('{id}', [AppointmentController::class, 'update']);
        Route::delete('{id}', [AppointmentController::class, 'destroy']);
    });

    Route::prefix('zones')->group(function () {
        Route::get('/', [ZoneController::class, 'index']);
    });

    Route::apiResource('coupons', CouponController::class);
});

Route::prefix('customer')->group(function () {
    Route::post('/login', [CustomerController::class, 'login']);
    Route::post('/register', [CustomerController::class, 'register']);
    Route::post('/signUp', [CustomerController::class, 'signUp']);
    
    Route::prefix('commercial_place')->group(function () {
        Route::get('/getCommercialPlace/{id}', [CommercialPlaceController::class, 'getCommercialPlace']);
    });

    Route::prefix('customer_management')->group(function () {
        Route::get('/getUserProfile', [CustomerController::class, 'getUserProfile']);
        Route::post('/update', [CustomerController::class, 'updateCustomerProfile']);
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
        Route::post('/verifiyOtpCode', [CustomerController::class, 'verifiyOtpCode']);
        
        Route::get('resendOtpCode' , [CustomerController::class, 'resendOtpCode']);
        Route::post('/verifiyOtpCodeChangePhoneNumber', [CustomerController::class, 'verifiyOtpCodeChangePhoneNumber']);
        Route::post('/changePhoneNumber' , [CustomerController::class, 'changePhoneNumber']);
    });

    Route::prefix('customer_address')->group(function () {
        Route::get('/',        [AddressController::class, 'index']);
        Route::post('/',       [AddressController::class, 'store']);
        Route::get('{id}',     [AddressController::class, 'show']);
        Route::post('{id}',    [AddressController::class, 'update']);
        Route::delete('{id}',  [AddressController::class, 'destroy']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('getParentCategories', [ParentCategoryController::class, 'getAllParentCategories']);
        Route::get('getCategoriesOfParent', [CategoryController::class, 'getAllCategoriesOfParent']);
        Route::get('parent_category_data/{id}' , [ParentCategoryController::class, 'parentCategoryData']);
        Route::get('getAllCategoriesOfCommercial/{commercial_place_id}' , [CategoryController::class, 'getAllCategoriesOfCommercial']);
    });

    Route::prefix('products')->group(function () {
        Route::get('getProductOfCategory', [ProductController::class, 'getProductOfCategory']);
        Route::get('searchOnProducts', [ProductController::class, 'searchOnProducts']);
        Route::get('getProduct/{id}', [ProductController::class, 'show']);
    });

    Route::prefix('customer_carts')->group(function () {
        Route::post('/checkout', [CartsController::class, 'checkout']);
        Route::get('/',               [CartsController::class, 'show']);
        Route::post('updateCartItem', [CartsController::class, 'updateCartItem']);
        Route::post('item',           [CartsController::class, 'addItem']);
        Route::post('item/{id}',      [CartsController::class, 'updateItem']);
        Route::delete('item/{id}',    [CartsController::class, 'deleteItem']);
        Route::get('/clearCart', [CartsController::class, 'clearCart']);
    });

    Route::prefix('payment_methods')->group(function () {
        Route::get('/', [MethodController::class, 'index']);
    });

    Route::prefix('banners')->group(function () {
        Route::get('/', [AppDataController::class, 'index']);
    });

    Route::prefix('orders')->group(function () {
        Route::get('/allOrderOfUser', [OrderController::class, 'allOrderOfUser']);
        Route::post('/cancelOrder', [OrderController::class, 'cancelOrder']);
        Route::get('/getOrderOfUser', [OrderController::class, 'getOrderOfUser']);
    });

    Route::prefix('coupon')->group(function () {
        Route::post('apply-coupon', [OrderCouponController::class, 'apply']);
        Route::post('rollback-coupon', [OrderCouponController::class, 'rollback']);
    });

    Route::prefix('favorite-products')->group(function () {
        Route::get('/', [FavoriteProductController::class, 'index']);
        Route::post('/', [FavoriteProductController::class, 'store']);
        Route::delete('{id}', [FavoriteProductController::class, 'destroy']);
    });

    Route::prefix('favorites-places')->group(function () {
        Route::get('/', [FavoritePlaceController::class, 'index']);
        Route::post('/', [FavoritePlaceController::class, 'store']);
        Route::delete('{id}', [FavoritePlaceController::class, 'destroy']);
    });

    Route::prefix('zones')->group(function () {
        Route::get('/', [ZoneController::class, 'index']);
    });

    Route::prefix('multiOffers')->group(function () {
        Route::get('/{id}', [MultiOfferController::class, 'show']);
    });
});

Route::prefix('commercial-place')->group(function () {
    Route::post('merchant-services', [MerchantServiceController::class, 'store']);
    
    Route::prefix('merchant')->group(function () {
        Route::post('/login', [MerchantController::class, 'login']);
        Route::post('/register', [MerchantController::class, 'register']);
        Route::post('/verifiyOtpCode', [MerchantController::class, 'verifiyOtpCode']);
        Route::get('/logout', [MerchantController::class, 'logout']);
        Route::get('/getProfile', [MerchantController::class, 'getProfile']);
        Route::post('/resetPassword', [MerchantController::class, 'resetPassword']);
        Route::post('/update', [MerchantController::class, 'update']);
    });

    Route::prefix('phone_number')->group(function () {
        Route::get('/', [PhoneNumberController::class, 'index']);
        Route::post('/', [PhoneNumberController::class, 'store']);
        Route::put('{id}', [PhoneNumberController::class, 'update']);
        Route::delete('{id}', [PhoneNumberController::class, 'destroy']);
    });
    
    Route::prefix('appointments')->group(function () {
        Route::get('/index', [AppointmentController::class, 'indexAll']);
        Route::get('{id}', [AppointmentController::class, 'show']);
        Route::post('/store', [AppointmentController::class, 'addAppointment']);
        Route::put('{id}', [AppointmentController::class, 'update']);
        Route::delete('{id}', [AppointmentController::class, 'destroy']);
    });

    Route::prefix('visibilty')->group(function (){
        Route::get('productVisibility', [ProductController::class, 'productVisibility']);
        Route::get('modifierVisibility', [ProductController::class, 'modifierVisibility']);
        Route::get('optionVisibility', [ProductController::class, 'optionVisibility']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/commercial-place-products', [ProductController::class, 'getProductsOfMerchant'])->middleware(CheckCommercialPlace::class);
        Route::get('/{id}', [ProductController::class, 'show'])->middleware(CheckCommercialPlace::class);
        Route::post('/{id}', [ProductController::class, 'update'])->middleware(CheckCommercialPlace::class);
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware(CheckCommercialPlace::class);
        Route::post('/update/{id}', [ProductController::class, 'update']);
    });

    //->middleware(CheckVerification::class)
    Route::prefix('orders')->group(function () {
        Route::get('/get_all_orders', [OrderController::class, 'getOrdersOfMerchant'])->middleware(CheckCommercialPlace::class);
        Route::get('/get_order', [OrderController::class, 'getOrder'])->middleware(CheckCommercialPlace::class);
        Route::get('/getOrderByStatus', [OrderController::class, 'getOrderByStatus'])->middleware(CheckCommercialPlace::class);
        Route::get('/getOrderDashboard', [OrderController::class, 'getOrderDashboard'])->middleware(CheckCommercialPlace::class);
        Route::post('/update_order_status', [OrderController::class, 'updateOrderStatus'])->middleware(CheckCommercialPlace::class);
    });

    /*Route::prefix('customer_management')->group(function () {
        Route::get('/getUserProfile', [CustomerController::class, 'getUserProfile']);
        Route::post('/update', [CustomerController::class, 'updateCustomerProfile']);
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
    });*/
});

Route::prefix('driver')->group(function () {
    Route::post('driver-services', [DriverServiceController::class, 'store']);
    Route::prefix('auth')->group(function () {
        Route::post('/login', [DriverController::class, 'login']);
        Route::get('/logout', [DriverController::class, 'logout']);
    });

    Route::prefix('profile')->group(function () {
        Route::get('/getProfile', [DriverController::class, 'getProfile']);
        Route::post('/changePassword', [DriverController::class, 'changePassword']);
        Route::post('/updateDriver', [DriverController::class, 'update']);
    });

    Route::prefix('driver-requests')->group(function () {
        Route::get('/', [DriverRequestController::class, 'index']);
        Route::post('', [DriverRequestController::class, 'store']);
        Route::get('/{id}', [DriverRequestController::class, 'show']);
        Route::put('/{id}', [DriverRequestController::class, 'update']);
        Route::delete('/{id}', [DriverRequestController::class, 'destroy']);
    });

    Route::prefix('dashboard')->group(function (){
        Route::get('/getDashboard', [DriverRequestController::class, 'getDashboard']);
        Route::get('/getOrderDashboard', [DriverRequestController::class, 'getOrderDashboard']);
        Route::put('/{id}', [DriverRequestController::class, 'updateOrderStatus']);
    });


});

/*git add .
git commit -m "update"
git push
*/