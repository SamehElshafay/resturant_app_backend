<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Branch;
use App\Models\User;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AddItemsToOrderRequest;
use App\Http\Requests\RemoveItemFromOrderRequest;
use App\Http\Requests\UpdateItemQuantityRequest;

class PosController extends Controller
{
    /**
     * Get data for POS initialization.
     */
    /**
     * Get list of categories for the branch.
     */
    public function getCategories(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            if (!$user->pos_account_code) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Unauthorized: Personal POS session is missing or expired. Pin Login required.'
                ], 403);
            }

            $posAccountCode = $user->pos_account_code;

            // تحديد الفرع الخاص بالجهاز لضمان ظهور أسعار ومنتجات هذا الفرع فقط
            $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                return $device ? $device->branch_id : null;
            });

            if (!$branchId) {
                return response()->json(['status' => false, 'message' => 'Branch not identified for this device'], 404);
            }

            // جلب الأقسام التي تحتوي على منتجات في هذا الفرع فقط لزيادة الكفاءة
            $categories = Category::whereHas('products', function($q) use ($branchId) {
                $q->join('branch_products', 'products.id', '=', 'branch_products.product_id')
                  ->where('branch_products.branch_id', $branchId);
            })->get(['id', 'name', 'image', 'printer_ip', 'printer_connection_type'])
            ->map(function($cat) use ($branchId) {
                // Override printer settings if branch-specific config exists
                $branchPrinter = \App\Models\CategoryBranchPrinter::where('category_id', $cat->id)
                    ->where('branch_id', $branchId)
                    ->first();
                
                if ($branchPrinter) {
                    $cat->printer_ip = $branchPrinter->printer_ip;
                    $cat->printer_connection_type = $branchPrinter->printer_connection_type;
                }
                // Safety cast for Flutter
                $cat->id = (int) $cat->id;
                return $cat;
            });

            // إضافة قسم "الكل" (All Category) في بداية المصفوفة يدوياً
            $allCategory = [
                'id' => 0,
                'name' => app()->getLocale() == 'ar' ? 'الكل' : 'All',
                'image' => null
            ];

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'categories' => collect([$allCategory])->concat($categories)
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error loading categories'], 500);
        }
    }

    /**
     * Get products for a specific category or all products for the branch.
     */
    public function getProducts(Request $request, $category_id = null)
    {
        try {
            $user = $request->user();
            $categoryId = $category_id ?? $request->category_id; // 0 means All

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            if (!$user->pos_account_code) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Unauthorized: Personal POS session is missing. Pin Login required.'
                ], 403);
            }

            $posAccountCode = $user->pos_account_code;

            // تحديد الفرع الخاص بالجهاز لضمان سحب الأسعار المحددة لهذا الفرع (Branch Pricing)
            $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                return $device ? $device->branch_id : null;
            });

            if (!$branchId) {
                return response()->json(['status' => false, 'message' => 'Branch not identified for this device'], 404);
            }

            // بناء الاستعلام بـ SQL Joins للحصول على أفضل أداء (O(1) price lookup)
            $query = Product::join('branch_products', 'products.id', '=', 'branch_products.product_id')
                ->where('branch_products.branch_id', $branchId)
                ->select('products.*', 'branch_products.price as branch_price');

            // الفلترة بالقسم إذا لم يكن "الكل" (id: 0)
            if ($categoryId && $categoryId != 0) {
                $query->where('products.category_id', $categoryId);
            }

            $products = $query->with('category:id,printer_ip,printer_connection_type')->get()->map(function($product) use ($branchId) {
                // Fetch branch-specific printer config for category
                $branchPrinter = \App\Models\CategoryBranchPrinter::where('category_id', $product->category_id)
                    ->where('branch_id', $branchId)
                    ->first();
                
                $product->printer_ip = $branchPrinter ? $branchPrinter->printer_ip : $product->category->printer_ip ?? null;
                $product->printer_connection_type = $branchPrinter ? $branchPrinter->printer_connection_type : $product->category->printer_connection_type ?? 'network';
                
                // Safety cast for Flutter
                $product->id = (int) $product->id;
                $product->category_id = (int) $product->category_id;
                $product->branch_price = (float) $product->branch_price;
                
                return $product;
            });

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'products' => $products,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error loading products'], 500);
        }
    }

    public function getInitialData(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->pos_account_code) {
                return response()->json([
                    'status' => false,
                    'message' => app()->getLocale() == 'ar' ? 'المستخدم غير مرتبط بجهاز POS حالياً' : 'User is not assigned to a POS device'
                ], 403);
            }

            $posAccountCode = $user->pos_account_code;

            // 1. استخدام التخزين المؤقت (Caching) لمعرّف الفرع لتقليل زمن الاستجابة (Latency reduction)
            $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                return $device ? $device->branch_id : null;
            });

            if (!$branchId) {
                return response()->json([
                    'status' => false,
                    'message' => app()->getLocale() == 'ar' ? 'فشل تحديد الفرع الخاص بهذا الجهاز' : 'Failed to identify branch'
                ], 404);
            }

            // 2. التحميل المسبق المخصص (Optimized Eager Loading) لجلب الأقسام والمنتجات المسعرة للفرع الحالي فقط
            // هذا التكنيك يمنع N+1 Query ويحمل البيانات بـ SQL Join واحد داخلياً
            $categories = Category::with([
                'products' => function ($query) use ($branchId) {
                    $query->join('branch_products', 'products.id', '=', 'branch_products.product_id')
                        ->where('branch_products.branch_id', $branchId)
                        ->select('products.*', 'branch_products.price as branch_price');
                }
            ])->get()->map(function($cat) use ($branchId) {
                // Override printer settings for category
                $branchPrinter = \App\Models\CategoryBranchPrinter::where('category_id', $cat->id)
                    ->where('branch_id', $branchId)
                    ->first();
                
                $ip = $branchPrinter ? $branchPrinter->printer_ip : $cat->printer_ip;
                $type = $branchPrinter ? $branchPrinter->printer_connection_type : $cat->printer_connection_type;

                $cat->printer_ip = $ip;
                $cat->printer_connection_type = $type;

                // Also inject into each product for easier access in frontend
                foreach($cat->products as $product) {
                    $product->id = (int) $product->id;
                    $product->category_id = (int) $product->category_id;
                    $product->printer_ip = $ip;
                    $product->printer_connection_type = $type;
                    $product->branch_price = (float) $product->branch_price;
                }
                $cat->id = (int) $cat->id;

                return $cat;
            });

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'categories' => $categories,
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Critical Error in getInitialData: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error loading initial data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get tables and zones with their current status for the POS.
     */
    public function getTables(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->pos_account_code) {
                return response()->json([
                    'status' => false,
                    'message' => app()->getLocale() == 'ar' ? 'المستخدم غير مرتبط بجهاز POS حالياً' : 'User is not assigned to a POS device'
                ], 403);
            }

            $posAccountCode = $user->pos_account_code;

            // 1. استخدام Cache لربط كود الـ POS بالفرع (Very High Performance) لتقليل الـ Joins والـ Lookups
            $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                return $device ? $device->branch_id : null;
            });

            if (!$branchId) {
                return response()->json([
                    'status' => false,
                    'message' => app()->getLocale() == 'ar' ? 'فشل تحديد الفرع الخاص بهذا الجهاز' : 'Failed to identify branch for this POS device'
                ], 404);
            }

            // 2. التحميل المسبق الذكي (Smart Eager Loading) بـ Query واحد فقط لكل من الـ Zones، الـ Tables، والـ Active Orders
            // نستخدم select() لتحميل الأعمدة المطلوبة فقط وتوفير الـ Memory
            $zones = \App\Models\Zone::with(['tables' => function ($query) {
                $query->with(['activeOrder' => function($q) {
                    $q->with(['items' => function($itemQuery) {
                        $itemQuery->with('product:id,name'); // تحميل تفاصيل المنتج الأساسية فقط
                    }]);
                }]);
            }])->where('branch_id', $branchId)->get();

            // 3. Batch Retrieval لعمليات الحجز (O(1) Memory Lookup) للقضاء على الـ N+1 Query داخل الحلقات التكرارية
            $tableIds = $zones->flatMap->tables->pluck('id')->toArray();
            $activeBookingsLookup = [];

            if (!empty($tableIds)) {
                $bookedTableIds = \App\Models\Booking::whereIn('table_id', $tableIds)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('booking_time', '>=', now()) // تحسين: الفلترة في الداتا بيز أسرع بمليون مرة من الـ PHP Loop
                    ->pluck('table_id')
                    ->toArray();
                    
                $activeBookingsLookup = array_flip($bookedTableIds);
            }

            // 4. Memory Processing & State Hydration: معالجة الحالات في الذاكرة لتجنب الـ SQL overhead
            $zones->each(function ($zone) use ($activeBookingsLookup) {
                $zone->tables->each(function ($table) use ($activeBookingsLookup) {
                    // الأولوية 1: طاولة مشغولة بطلب نشط
                    if ($table->active_order_id) {
                        $table->status = 'occupied';
                        $table->active_order = $table->activeOrder;
                    } 
                    // الأولوية 2: طاولة محجوزة مسبقاً (Reservation)
                    elseif (isset($activeBookingsLookup[$table->id])) {
                        $table->status = 'reserved';
                        $table->active_order = null;
                    } 
                    // الأولوية 3: طاولة متاحة (Active)
                    else {
                        $table->status = 'active';
                        $table->active_order = null;
                    }

                    // تنظيف الموديلات من العلاقات الزائدة قبل تحويلها لـ JSON لتقليل الـ Payload Size
                    unset($table->activeOrder);
                });
            });

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'zones' => $zones,
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Critical Performance Error in getTables: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'System error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get list of drivers for delivery assignment.
     */
    public function getDrivers(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            // Identify the branch for this user/session
            $branchId = $user->branch_id;

            // If user has a POS session, get that session's branch (Consistent pricing/data)
            if ($user->pos_account_code) {
                $posAccountCode = $user->pos_account_code;
                $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                    $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                    return $device ? $device->branch_id : null;
                }) ?? $branchId;
            }

            if (!$branchId) {
                return response()->json(['status' => false, 'message' => 'Branch not identified'], 404);
            }

            $drivers = User::whereHas('roles', function ($q) {
                $q->where('name', 'driver');
            })
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->get(['id', 'name', 'phone']);

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'drivers' => $drivers
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading drivers: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error loading drivers'], 500);
        }
    }

    public function loginByPin(Request $request)
    {
        Log::info('Login attempt with PIN: ' . $request->pin . ' for POS: ' . $request->account_code);
        $request->validate([
            'pin' => 'required|digits:4',
            'account_code' => 'required|string',
        ]);

        // 1. التحقق من أن الـ account_code يخص جهاز POS مسجل وصحيح
        $deviceExists = \App\Models\PosDevice::where('account_code', $request->account_code)->exists();
        if (!$deviceExists) {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() == 'ar' ? 'كود جهاز الـ POS غير صحيح' : 'Invalid POS account code',
            ], 404);
        }

        $user = User::where('pin', $request->pin)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() == 'ar' ? 'الرمز السري غير صحيح أو الموظف غير نشط' : 'Incorrect PIN or inactive employee',
            ], 401);
        }

        // 2. تحديث الكاشير بكود الـ POS الحالي الذي يعمل عليه الآن
        $user->update([
            'pos_account_code' => $request->account_code
        ]);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'branch_id' => (int) $user->branch_id,
                'pos_account_code' => $user->pos_account_code,
                'token' => $user->createToken('pos-device')->plainTextToken,
            ],
        ]);
    }

    /**
     * Close the current session (End Shift), provide a summary, and logout.
     */
    public function closeSession(Request $request)
    {
        try {
            $user = $request->user();
            $branchId = $user->branch_id;

            // 1. Calculate Shift Summary for today
            $today = now()->format('Y-m-d');
            
            $orders = \App\Models\Order::where('cashier_id', $user->id)
                ->where('branch_id', $branchId)
                ->whereDate('created_at', $today)
                ->get();

            $summary = [
                'cashier_name' => $user->name,
                'shift_date' => $today,
                'total_orders' => $orders->count(),
                'completed_orders' => $orders->where('status', 'completed')->count(),
                'pending_orders' => $orders->where('status', 'pending')->count(),
                'total_amount' => $orders->sum('total_amount'),
                'total_collected_cash' => $orders->sum('paid_amount'),
                'breakdown' => [
                    'dine_in' => $orders->where('type', 'dine_in')->count(),
                    'takeaway' => $orders->where('type', 'takeaway')->count(),
                    'delivery' => $orders->where('type', 'delivery')->count(),
                ]
            ];

            // 2. Perform Logout logic
            $user->update(['pos_account_code' => null]);
            $user->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => app()->getLocale() == 'ar' ? 'تم إغلاق الوردية وتسجيل الخروج بنجاح' : 'Shift closed and logged out successfully',
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Close Session Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error closing session'], 500);
        }
    }

    /**
     * Standard logout (just clears session).
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            // مسح كود الـ POS عند تسجيل الخروج أو إغلاق الجلسة
            $user->update([
                'pos_account_code' => null
            ]);

            // حذف التوكن الحالي
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            return response()->json([
                'status' => true,
                'message' => app()->getLocale() == 'ar' ? 'تم تسجيل الخروج بنجاح' : 'Logged out successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Logout failed',
            ], 500);
        }
    }

    public function registerDeviceByCode(Request $request)
    {
        Log::info('Device registration attempt with code: ' . $request->account_code);
        $request->validate([
            'account_code' => 'required|string',
        ]);

        $device = \App\Models\PosDevice::where('account_code', $request->account_code)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() == 'ar' ? 'كود الجهاز غير صحيح' : 'Invalid Device Account Code',
            ], 404);
        }

        if ($device->is_registered) {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() == 'ar' ? 'هذا الجهاز مسجل مسبقاً' : 'This device is already registered',
            ], 422);
        }

        $device->update([
            'is_registered' => true,
            'registered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => app()->getLocale() == 'ar' ? 'تم تسجيل الجهاز بنجاح' : 'Device registered successfully',
            'device' => [
                'id' => (int) $device->id,
                'name' => $device->name,
                'account_code' => $device->account_code,
                'branch_id' => (int) $device->branch_id,
                'branch_name' => $device->branch->name ?? 'Unknown Branch',
            ]
        ]);
    }

    public function storeOrder(Request $request)
    {
        Log::info('Order creation request recieved', $request->all());
        $request->validate([
            'items' => 'required|array',
            'type' => 'required|in:dine_in,takeaway,delivery',
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'driver_id' => 'nullable|exists:users,id',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'nullable|numeric',
            'tax' => 'required|numeric',
            'discount' => 'nullable|numeric',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                $posAccountCode = $user->pos_account_code;

                // تحديد الفرع الخاص بالجهاز لضمان تسجيل الطلب في الفرع الصحيح
                $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                    $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                    return $device ? $device->branch_id : null;
                });

                if (!$branchId) {
                    throw new \Exception('Branch not identified for this POS device.');
                }

                // Simple daily number logic
                $dailyNumber = \App\Models\Order::where('branch_id', $branchId)
                    ->whereDate('created_at', today())
                    ->count() + 1;

                // Logic: Takeaway and Delivery are paid instantly (Completed), Dine-in stays open (Pending)
                $status = in_array($request->type, ['takeaway', 'delivery']) ? 'completed' : 'pending';

                $order = \App\Models\Order::create([
                    'branch_id' => $branch_id ?? $branchId, // Ensure we use the correct variable
                    'cashier_id' => $user->id,
                    'driver_id' => $request->type === 'delivery' ? $request->driver_id : null,
                    'table_id' => $request->table_id,
                    'daily_number' => $dailyNumber,
                    'type' => $request->type,
                    'status' => $status,
                    'total_amount' => $request->total_amount,
                    'paid_amount' => $request->paid_amount ?? ($status == 'completed' ? $request->total_amount : 0),
                    'tax' => $request->tax,
                    'discount' => $request->discount ?? 0,
                    'notes' => $request->notes,
                ]);

                // Link order to table if it's dine_in
                if ($request->type === 'dine_in' && $request->table_id) {
                    $table = RestaurantTable::find($request->table_id);
                    if ($table->active_order_id) {
                        throw new \Exception('Table already has an active order.');
                    }
                    $table->update(['active_order_id' => $order->id]);
                }

                foreach ($request->items as $item) {
                    // 3. Check Stock Availability
                    $branchProduct = \App\Models\BranchProduct::where('branch_id', $order->branch_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if (!$branchProduct || $branchProduct->stock_quantity < $item['quantity']) {
                        $product = \App\Models\Product::find($item['product_id']);
                        $productName = $product ? $product->name : 'Unknown Product';
                        throw new \Exception(
                            app()->getLocale() == 'ar' 
                                ? "كمية المخزون غير كافية للمنتج: {$productName}" 
                                : "Insufficient stock for product: {$productName}"
                        );
                    }

                    \App\Models\OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'item_total' => $item['price'] * $item['quantity'],
                    ]);

                    // 4. Deduct from branch inventory
                    \App\Models\BranchProduct::where('branch_id', $order->branch_id)
                        ->where('product_id', $item['product_id'])
                        ->decrement('stock_quantity', $item['quantity']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'order' => $order->load('items.product'),
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Order creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function addItemsToOrder(AddItemsToOrderRequest $request, $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $order = \App\Models\Order::find($id);

                if (!$order) {
                    return response()->json([
                        'success' => false,
                        'message' => app()->getLocale() == 'ar' ? 'الطلب غير موجود' : 'Order not found'
                    ], 404);
                }

                if ($order->status === 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => app()->getLocale() == 'ar' ? 'لا يمكن إضافة أصناف لطلب مكتمل' : 'Cannot add items to a completed order'
                    ], 422);
                }

                $branchId = $order->branch_id;

                foreach ($request->items as $item) {
                    // Check Stock Availability
                    $branchProduct = \App\Models\BranchProduct::where('branch_id', $branchId)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if (!$branchProduct || $branchProduct->stock_quantity < $item['quantity']) {
                        $product = \App\Models\Product::find($item['product_id']);
                        $productName = $product ? $product->name : 'Unknown Product';
                        throw new \Exception(
                            app()->getLocale() == 'ar' 
                                ? "كمية المخزون غير كافية للمنتج: {$productName}" 
                                : "Insufficient stock for product: {$productName}"
                        );
                    }

                    \App\Models\OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'item_total' => $item['price'] * $item['quantity'],
                    ]);

                    // Deduct from branch inventory
                    $branchProduct->decrement('stock_quantity', $item['quantity']);
                }

                // Update order total and charges
                $order->update([
                    'total_amount' => $request->total_amount,
                    'tax' => $request->tax ?? $order->tax,
                    'discount' => $request->discount ?? $order->discount,
                    'notes' => $request->notes ?? $order->notes,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => app()->getLocale() == 'ar' ? 'تم إضافة الأصناف للطلب بنجاح' : 'Items added to order successfully',
                    'data' => [
                        'order' => $order->load('items.product')
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Adding items to order failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() == 'ar' ? 'فشلت عملية إضافة الأصناف: ' . $e->getMessage() : 'Failed to add items: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function removeItemFromOrder(RemoveItemFromOrderRequest $request, $order_id, $item_id)
    {
        try {
            return DB::transaction(function () use ($request, $order_id, $item_id) {
                $order = \App\Models\Order::find($order_id);

                if (!$order) {
                    return response()->json([
                        'success' => false,
                        'message' => app()->getLocale() == 'ar' ? 'الطلب غير موجود' : 'Order not found'
                    ], 404);
                }

                if ($order->status === 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => app()->getLocale() == 'ar' ? 'لا يمكن تعديل طلب مكتمل' : 'Cannot modify a completed order'
                    ], 422);
                }

                $item = \App\Models\OrderItem::where('id', $item_id)
                    ->where('order_id', $order_id)
                    ->first();

                if (!$item) {
                    return response()->json([
                        'success' => false,
                        'message' => app()->getLocale() == 'ar' ? 'الصنف غير موجود في هذا الطلب' : 'Item not found in this order'
                    ], 404);
                }

                // Reverse Stock: Return quantity to branch inventory
                $branchProduct = \App\Models\BranchProduct::where('branch_id', $order->branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($branchProduct) {
                    $branchProduct->increment('stock_quantity', $item->quantity);
                }

                // Delete the item
                $item->delete();

                // Update order total and charges
                $order->update([
                    'total_amount' => $request->total_amount,
                    'tax' => $request->tax ?? $order->tax,
                    'discount' => $request->discount ?? $order->discount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => app()->getLocale() == 'ar' ? 'تم حذف الصنف بنجاح' : 'Item removed successfully',
                    'data' => [
                        'order' => $order->load('items.product')
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Removing item from order failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() == 'ar' ? 'فشلت عملية حذف الصنف: ' . $e->getMessage() : 'Failed to remove item: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateItemQuantity(UpdateItemQuantityRequest $request, $order_id, $item_id)
    {
        try {
            return DB::transaction(function () use ($request, $order_id, $item_id) {
                $order = \App\Models\Order::find($order_id);

                if (!$order) {
                    return response()->json([
                        'success' => false,
                        'message' => app()->getLocale() == 'ar' ? 'الطلب غير موجود' : 'Order not found'
                    ], 404);
                }

                if ($order->status === 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => app()->getLocale() == 'ar' ? 'لا يمكن تعديل طلب مكتمل' : 'Cannot modify a completed order'
                    ], 422);
                }

                $item = \App\Models\OrderItem::where('id', $item_id)
                    ->where('order_id', $order_id)
                    ->first();

                if (!$item) {
                    return response()->json([
                        'success' => false,
                        'message' => app()->getLocale() == 'ar' ? 'الصنف غير موجود في هذا الطلب' : 'Item not found in this order'
                    ], 404);
                }

                $newQuantity = $request->quantity;
                $oldQuantity = $item->quantity;
                $diff = $newQuantity - $oldQuantity;

                $branchProduct = \App\Models\BranchProduct::where('branch_id', $order->branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($diff > 0) {
                    // Increasing quantity: check and deduct stock
                    if (!$branchProduct || $branchProduct->stock_quantity < $diff) {
                        $product = \App\Models\Product::find($item->product_id);
                        $productName = $product ? $product->name : 'Unknown Product';
                        throw new \Exception(
                            app()->getLocale() == 'ar' 
                                ? "كمية المخزون غير كافية للمنتج: {$productName}" 
                                : "Insufficient stock for product: {$productName}"
                        );
                    }
                    $branchProduct->decrement('stock_quantity', $diff);
                } elseif ($diff < 0) {
                    // Decreasing quantity: return to stock
                    if ($branchProduct) {
                        $branchProduct->increment('stock_quantity', abs($diff));
                    }
                }

                if ($newQuantity <= 0) {
                    $item->delete();
                    $message = app()->getLocale() == 'ar' ? 'تم حذف الصنف لأن الكمية أصبحت صفر' : 'Item removed as quantity reached zero';
                } else {
                    $item->update([
                        'quantity' => $newQuantity,
                        'item_total' => $newQuantity * $item->price
                    ]);
                    $message = app()->getLocale() == 'ar' ? 'تم تحديث الكمية بنجاح' : 'Quantity updated successfully';
                }

                // Update order total and charges
                $order->update([
                    'total_amount' => $request->total_amount,
                    'tax' => $request->tax ?? $order->tax,
                    'discount' => $request->discount ?? $order->discount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'order' => $order->load('items.product')
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Updating item quantity failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() == 'ar' ? 'فشلت عملية تحديث الكمية: ' . $e->getMessage() : 'Failed to update quantity: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get details of a specific order.
     */
    public function getOrder(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            // High-performance loading of the full order tree
            // We use standard select logic for relations to minimize overhead
            $order = \App\Models\Order::with([
                'items' => function($q) {
                    $q->with('product:id,name,image');
                },
                'table:id,number',
                'cashier:id,name',
                'driver:id,name,phone',
                'branch:id,name,address'
            ])->find($id);

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => app()->getLocale() == 'ar' ? 'الطلب غير موجود' : 'Order not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'order' => $order
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order Retrieval Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error loading order details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark an order as paid and release the table.
     */
    public function payOrder(Request $request, $id)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'paid_amount' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $request) {
                $order = \App\Models\Order::find($id);

                if (!$order) {
                    return response()->json([
                        'status' => false, 
                        'message' => app()->getLocale() == 'ar' ? 'الطلب غير موجود' : 'Order not found'
                    ], 404);
                }

                if ($order->status === 'completed') {
                    return response()->json([
                        'status' => false, 
                        'message' => app()->getLocale() == 'ar' ? 'الطلب مدفوع بالفعل' : 'Order is already paid'
                    ], 422);
                }

                // 1. Update order status and paid amount
                $order->update([
                    'status' => 'completed',
                    'paid_amount' => $request->paid_amount
                ]);

                // 2. Release Table (Crucial for Dine-in)
                if ($order->table_id) {
                    $table = \App\Models\RestaurantTable::find($order->table_id);
                    if ($table && $table->active_order_id == $order->id) {
                        $table->update(['active_order_id' => null]);
                    }
                }

                return response()->json([
                    'status' => true,
                    'message' => app()->getLocale() == 'ar' ? 'تمت عملية الدفع وتفريغ الطاولة بنجاح' : 'Order paid and table released successfully'
                ], 200);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order Payment Error: ' . $e->getMessage());
            return response()->json([
                'status' => false, 
                'message' => 'Payment failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get list of completed orders for the branch with filters.
     */
    public function getOrders(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            // 1. Identify Branch
            $branchId = $user->branch_id;
            if ($user->pos_account_code) {
                $posAccountCode = $user->pos_account_code;
                $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                    $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                    return $device ? $device->branch_id : null;
                }) ?? $branchId;
            }

            if (!$branchId) {
                return response()->json(['status' => false, 'message' => 'Branch not identified'], 404);
            }

            // 2. Set Defaults for Filters
            $type = $request->get('type', 'takeaway'); 
            $period = $request->get('period', 'day');  
            $date = $request->get('date', now()->format('Y-m-d')); 
            $search = $request->get('search'); // Global search term
            $perPage = $request->get('per_page', 15); // Pagination

            $query = \App\Models\Order::with(['items.product', 'table', 'cashier:id,name', 'driver:id,name'])
                ->where('branch_id', $branchId)
                ->where('status', 'completed');

            // 3. Apply Search (Global filter)
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('daily_number', 'LIKE', "%{$search}%")
                      ->orWhere('notes', 'LIKE', "%{$search}%")
                      ->orWhere('total_amount', 'LIKE', "%{$search}%")
                      ->orWhereHas('cashier', function($sq) use ($search) {
                          $sq->where('name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('driver', function($sq) use ($search) {
                          $sq->where('name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('items.product', function($sq) use ($search) {
                          $sq->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            // 4. Apply Type Filter
            if ($type !== 'all') {
                $query->where('type', $type);
            }

            // 5. Apply Date Filtering
            if ($period === 'day') {
                $query->whereDate('created_at', $date);
            } elseif ($period === 'month') {
                $query->whereYear('created_at', date('Y', strtotime($date)))
                      ->whereMonth('created_at', date('m', strtotime($date)));
            } elseif ($period === 'year') {
                $query->whereYear('created_at', date('Y', strtotime($date)));
            } elseif ($period === 'range' && $request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            } else {
                // Stay on current date if no specific period is provided or unrecognized
                if (!$search) { // Only force today if not searching globally
                    $query->whereDate('created_at', now()->format('Y-m-d'));
                }
            }

            // 6. Calculate Stats (before pagination)
            $stats = [
                'total_orders' => (clone $query)->count(),
                'total_amount' => (clone $query)->sum('total_amount'),
                'total_paid' => (clone $query)->sum('paid_amount'),
            ];

            // 7. Paginate Results
            $orders = $query->latest()->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'meta' => array_merge([
                    'current_type' => $type,
                    'current_period' => $period,
                    'current_date' => $date,
                    'search_term' => $search,
                ], $stats),
                'orders' => $orders
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GetOrders Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error loading orders list'], 500);
        }
    }

    /**
     * Get all categories with their branch-specific printer configurations.
     */
    public function getPrinterConfigs(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            // Identify branch from user/device
            $branchId = $user->branch_id;
            if ($user->pos_account_code) {
                $posAccountCode = $user->pos_account_code;
                $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                    $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                    return $device ? $device->branch_id : null;
                }) ?? $branchId;
            }

            if (!$branchId) {
                return response()->json(['status' => false, 'message' => 'Branch not identified'], 404);
            }

            // Fetch all categories and their branch-specific printer settings
            $categories = Category::all(['id', 'name'])->map(function($cat) use ($branchId) {
                $branchPrinter = \App\Models\CategoryBranchPrinter::where('category_id', $cat->id)
                    ->where('branch_id', $branchId)
                    ->first();
                
                return [
                    'category_id' => $cat->id,
                    'category_name' => $cat->name,
                    'printer_ip' => $branchPrinter ? $branchPrinter->printer_ip : '',
                    'printer_connection_type' => $branchPrinter ? $branchPrinter->printer_connection_type : 'network',
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'branch_id' => $branchId,
                'configs' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error loading printer configurations'], 500);
        }
    }

    /**
     * Update or create a branch-specific printer configuration for a category.
     */
    public function updatePrinterConfig(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id',
                'printer_ip' => 'nullable|string',
                'printer_connection_type' => 'nullable|string'
            ]);

            $user = $request->user();
            $branchId = $user->branch_id;
            
            if ($user->pos_account_code) {
                $posAccountCode = $user->pos_account_code;
                $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                    $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                    return $device ? $device->branch_id : null;
                }) ?? $branchId;
            }

            if (!$branchId) {
                return response()->json(['status' => false, 'message' => 'Branch not identified'], 404);
            }

            $config = \App\Models\CategoryBranchPrinter::updateOrCreate(
                [
                    'category_id' => $request->category_id,
                    'branch_id' => $branchId,
                ],
                [
                    'printer_ip' => $request->printer_ip,
                    'printer_connection_type' => $request->printer_connection_type ?? 'network'
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Printer configuration updated successfully',
                'config' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error updating printer configuration'], 500);
        }
    }

    /**
     * Get the inventory (stock quantity) for all products in this branch.
     */
    public function getInventory(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            // Identify branch
            $branchId = $user->branch_id;
            if ($user->pos_account_code) {
                $posAccountCode = $user->pos_account_code;
                $branchId = \Illuminate\Support\Facades\Cache::remember("pos_branch_{$posAccountCode}", now()->addHours(24), function () use ($posAccountCode) {
                    $device = \App\Models\PosDevice::where('account_code', $posAccountCode)->first();
                    return $device ? $device->branch_id : null;
                }) ?? $branchId;
            }

            if (!$branchId) {
                return response()->json(['status' => false, 'message' => 'Branch not identified'], 404);
            }

            // Fetch products with their branch-specific stock
            $inventory = Product::join('branch_products', 'products.id', '=', 'branch_products.product_id')
                ->where('branch_products.branch_id', $branchId)
                ->with('category:id,printer_ip,printer_connection_type')
                ->select(
                    'products.id',
                    'products.name',
                    'products.category_id',
                    'products.image',
                    'branch_products.price as branch_price',
                    'branch_products.stock_quantity'
                )
                ->get()->map(function($product) use ($branchId) {
                    $branchPrinter = \App\Models\CategoryBranchPrinter::where('category_id', $product->category_id)
                        ->where('branch_id', $branchId)
                        ->first();
                    
                    $product->printer_ip = $branchPrinter ? $branchPrinter->printer_ip : $product->category->printer_ip ?? null;
                    $product->printer_connection_type = $branchPrinter ? $branchPrinter->printer_connection_type : $product->category->printer_connection_type ?? 'network';
                    
                    // Explicit casting for POS App sanity
                    $product->id = (int) $product->id;
                    $product->category_id = (int) $product->category_id;
                    $product->branch_price = (float) $product->branch_price;
                    $product->stock_quantity = (float) $product->stock_quantity;
                    
                    return $product;
                });

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'branch_id' => (int) $branchId,
                'inventory' => $inventory
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error loading inventory'], 500);
        }
    }
}
