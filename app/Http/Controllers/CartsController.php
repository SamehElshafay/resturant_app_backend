<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel\Address;
use App\Models\CustomerModel\Cart;
use App\Models\CustomerModel\CartItem;
use App\Models\CustomerModel\CartItemOption;
use App\Models\OrdersModels\Order;
use App\Models\OrdersModels\OrderAddress;
use App\Models\OrdersModels\OrderItem;
use App\Models\OrdersModels\OrderItemOption;
use App\Models\OrdersModels\OrderState;
use App\Models\OrdersModels\OtherUser;
use App\Models\ProductsModel\Product;
use App\Models\ProductsModel\ProductModifierOptions;
use App\Traits\TransactionResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartsController extends Controller {

    use TransactionResponse;

    public function index() {
        try {
            $data = Cart::with('items.product')->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|integer'
            ]);

            $cart = Cart::create($validated);

            return response()->json([
                'success' => true,
                'data' => $cart
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function show() {
        try {
            $customer = auth('customer')->user();

            $cart = Cart::where('customer_id', $customer->id)->get()->first() ;
            
            if (!$cart) {
                $cart = Cart::create([
                    'customer_id' => $customer->id
                ]);
            }

            $cart->total = $cart->totalPrice + $cart->delivery + $cart->services ;
        
            
            if(count($cart->cart_items) == 0)
                return response()->json([
                    'success' => true,
                    'message' => 'Cart is empty',
                    'data' => []
                ], 200);

            return response()->json([
                'success' => true,
                'data' => $cart
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function addItem(Request $request) {
        try {
            DB::beginTransaction();
            $customer = auth('customer')->user();
            
            $validated = $request->validate([
                'product_id'   => 'required|integer',
                'qty'          => 'required|integer|min:1',
                'commercial_id' => 'required|integer',
                'options_ids'   => 'sometimes|array',
                'options_ids.*' => 'integer|exists:product_modifiers_options,id' ,
            ]);


            $cart = Cart::where('customer_id', $customer->id)->get()->first();
            
            if($cart->commercial_id != null && $cart->commercial_id != $validated['commercial_id']){
                return response()->json([
                    'success' => false ,
                    'title' => 'conflict',
                    'message' => 'you can not add item to cart because you are not in this commercial place'
                ], 200);
            }

            if(count($cart->cart_items) == 0){
                $cart->delivery_price = 500 ;
                $cart->services = 5 ;
            }


            $cart->commercial_id = $validated['commercial_id'];
            $cart->save();

            $product = Product::findOrFail($validated['product_id']);
            
            $item = CartItem::create([
                'cart_id' => $cart->id ,
                'product_id' => $validated['product_id'],
                'qty' => $validated['qty'],
                'unit_price' => $product->price ,
                'total_price' => $validated['qty'] ,
            ]);

            $price = 0;

            if (!empty($request->options_ids)) {
                foreach ($request->options_ids as $optionId) {
                    $productModifierOptions = ProductModifierOptions::findOrFail($optionId);
                    CartItemOption::create([
                        'cart_item_id'       => $item->id,
                        'modifier_option_id' => $optionId,
                        'price'              => $productModifierOptions->price,
                    ]);
                    $price += $productModifierOptions->price;
                }
            }

            $price += $item->unit_price;
            $price *= $item->qty;
            $item->total_price = $price;
            $item->save();

            //DB::rollBack();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function updateItem(Request $request, $id) {
        try {
            $item = CartItem::find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'data' => 'Item not found'
                ], 404);
            }

            $validated = $request->validate([
                'qty' => 'sometimes|integer|min:1'
            ]);

            if (isset($validated['qty'])) {
                $item->qty = $validated['qty'];
                $item->total_price = $item->qty * $item->unit_price;
            }

            $item->save();

            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteItem($id) {
        try {
            $item = CartItem::find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'data' => 'Item not found'
                ], 404);
            }

            $cart = Cart::find($item->cart_id);
            
            $item->delete();

            if(count($cart->cart_items) == 0){
                $cart->commercial_id = null ;
                $cart->save();
            }
                
            return response()->json([
                'success' => true,
                'data' => 'Item deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function updateCartItem(Request $request) {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'cart_item_id' => 'required|integer|exists:cart_items,id',
                'qty' => 'sometimes|integer|min:1',
                'options' => 'sometimes|array',
                'options.*.modifier_option_id' => 'required|integer|exists:modifier_options,id',
                'options.*.price' => 'required|numeric|min:0',
            ]);

            $cartItem = CartItem::with('cart_item_data')->findOrFail($validated['cart_item_id']);

            /* =========================
            Update Quantity (لو موجودة)
            ========================== */
            if (isset($validated['qty'])) {
                $cartItem->qty = $validated['qty'];
            }

            /* =========================
            Update Options (لو موجودة)
            ========================== */
            $optionsTotal = 0;

            if (isset($validated['options'])) {
                // حذف الاختيارات القديمة
                $cartItem->cart_item_data()->delete();

                // إضافة الجديدة
                foreach ($validated['options'] as $option) {
                    $cartItem->cart_item_data()->create([
                        'modifier_option_id' => $option['modifier_option_id'],
                        'price' => $option['price'],
                    ]);

                    $optionsTotal += $option['price'];
                }
            } else {
                // لو مفيش options جديدة → احسب القديمة
                $optionsTotal = $cartItem->cart_item_data->sum('price');
            }

            /* =========================
            Recalculate Prices
            ========================== */
            $cartItem->unit_price = $cartItem->product->price + $optionsTotal;
            $cartItem->total_price = $cartItem->unit_price * $cartItem->qty;

            $cartItem->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $cartItem->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkout(Request $request){
        return $this->transactionResponse(function () use ($request) {
            $validated = $request->validate([
                'other_user'        => 'sometimes|array' ,
                'other_user.phone_number'      => 'sometimes|string',
                'other_user.user_name'         => 'sometimes|string',
                'other_user.address'           => 'sometimes|string' ,
                'payment_method_id' => 'required|integer|exists:method,id',
                'address_id'        => 'nullable|integer' ,
                'order_time'        => 'nullable|timestamp',
                'note'              => 'sometimes|string' ,
            ]);
            
            $user = auth('customer')->user();
            
            if(!$user){
                throw new Exception('unAuthorized');
            }

            $cart = Cart::where('customer_id' , $user->id)->get()->first();

            $cart->services = 50 ;
            $cart->delivery_price = 20 ;
            $cart->save();
            
            $order = Order::create([
                'user_id' => $user->id,
                'total_value' => $cart->cart_items->sum('total_price') ,
                'commercial_place_id' => $cart->commercial_id ,
                'delivery_price' => $cart->delivery_price ,
                'services' => $cart->services ,
                'coupon_id' => $cart->coupon_id ,
                'phoneNumber' => $user->phone_number ,
                'discount' => $cart->total_discount ,
                'payment_method_id' => $validated['payment_method_id'],
                'note' => $validated['note'] ,
                'order_time' => $validated['order_time'] ,
            ]);

            $orderAddress = null ;
            
            if($request->address_id == null){
                OtherUser::create([
                    'order_id' => $order->id ,
                    'phone_number' => $validated['other_user']['phone_number'] ,
                    'address' => $validated['other_user']['address'] ,
                    'user_name' => $validated['other_user']['user_name'] ,
                ]);
            }else{
                $address = Address::find($request->address_id);
                $orderAddress = OrderAddress::create([
                    'order_id' => $order->id ,
                    'zone_id' => $address->zone_id ,
                    'lng' => $address->lng ,
                    'lat' => $address->lat ,
                    'city' => $address->city ,
                    'street_name' => $address->street_name ,
                    'building_number' => $address->building_number ,
                    'floor_number' => $address->floor_number ,
                    'apartment_number' => $address->apartment_number 
                ]);
            }

            $order->total_recipt = $cart->total_price + $cart->delivery_price + $cart->services - $cart->total_discount ;
            $order->save();
            
            OrderState::create(['state_id' => 1 ,'order_id' => $order->id]);

            foreach ($cart->cart_items as $item) {
                $orderitem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ]);
                $item->delete();
                foreach ($item->cart_item_data as $modifier){
                    OrderItemOption::create([
                        'order_item_id' => $orderitem->id,
                        'option_id' => $modifier->modifier_option_id,
                        'price' => $modifier->price
                    ]);
                    $modifier->delete();
                }
            }

            $delete_cart =  Cart::find($cart->id);
            $delete_cart->commercial_id = null;
            $delete_cart->save();
            //send notification here to commercial place about new order

            return 'Order placed successfully' ;
        });
    }

    public function clearCart(){
        try {
            $user = auth('customer')->user() ;
            
            if(!$user){
                return response()->json([
                    'success' => false,
                    'data' => 'unAuthorized'
                ], 401);
            }

            $cart = Cart::with('cart_items.cart_item_data')->where('customer_id' , $user->id)->get()->first();

            foreach ($cart->cart_items as $item) {
                $item->cart_item_data()->delete();
            }


            $cart->cart_items()->delete();

            $cart->update([
                'coupon_id' => null,
                'commercial_id' => null,
                'delivery_price' => 0,
                'services' => 0
            ]);

            return response()->json([
                'success' => true,
                'data' => 'Cart cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }
}