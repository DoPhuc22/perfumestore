<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Requests\StoreOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;
use function Sodium\add;

class OrderController extends Controller
{
    public function checkoutProcess(StoreOrderRequest $request)
    {
        //date mua hang
        $dateBuy = date("Y-m-d H:i:s");
        //lay status (status mac dinh la 0 tuong ung trang thai xac nhan don hang)
        $status = 0;
        //1: pay on delivery, 2: pay on vnpay
        $methodId = 1;
        //customer id
        $customerId = Auth::guard('customer')->id();

////        if ($request->validated()) {
        $array = [];
        $array = Arr::add($array, 'order_date', $dateBuy);
        $array = Arr::add($array, 'order_status', $status);
        $array = Arr::add($array, 'receiver_name', $request->receiver_name);
        $array = Arr::add($array, 'receiver_phone', $request->receiver_phone);
        $array = Arr::add($array, 'receiver_address', $request->receiver_address);
//        $array = Arr::add($array, 'admin_id', 1);
        $array = Arr::add($array, 'customer_id', $customerId);
//        $array = Arr::add($array, 'method_id', $methodId);
        Order::create($array);

        $maxOrderId = Order::where('customer_id', $customerId)->max('id');
        if (!$maxOrderId) {
            $maxOrderId = 1;
        }

        //lay du lieu de insert vao bang order_details
        foreach (\Illuminate\Support\Facades\Session::get('cart') as $product_id => $product) {
            $orderDetails = [];
            $orderDetails = Arr::add($orderDetails, 'order_id', $maxOrderId);
            $orderDetails = Arr::add($orderDetails, 'product_id', $product_id);
            $orderDetails = Arr::add($orderDetails, 'sold_price', $product['price']);
            $orderDetails = Arr::add($orderDetails, 'sold_quantity', $product['quantity']);

            $productQuantity = Product::find($product_id);
            $productQuantity->quantity -= $product['quantity'];
            $productQuantity->save();
            OrderDetail::create($orderDetails);
        }

        Session::forget('cart');
        return Redirect::route('orderHistory')->with('success', 'Order created successfully!');
//        } else {
//            dd("loi");
////            return Redirect::route('checkout')->with('error', 'Create order failed!');
//        }
    }

    public function cancelOrder(Order $order)
    {
        $array = [];
        $array = Arr::add($array, 'order_status', 4);
        $order->update($array);
        return to_route('orderHistory')->with('success', 'Cancel order successfully!');
    }

    //ADMIN
    public function index()
    {
        $orders = Order::with('admin')->paginate(6);
        return view("admins.order_manager.index", [
            "orders" => $orders,
        ]);
    }

    public function showDetail(Order $order, Request $request)
    {
        $orderId = $order->id;
        $orderDetails = DB::table('orders_details')
            ->where('order_id', '=', $orderId)
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->get();

        $orderAmount = 0;
        $orderItems = 0;
        foreach ($orderDetails as $detail) {
            $orderItems += $detail->sold_quantity;
            $orderAmount += $detail->sold_price * $detail->sold_quantity;
        }
        $orderTotal = $orderAmount + 10;
        $admin = Admin::where('id', '=', $order->admin_id)->first();

//        $product = Product::all();
//        $customer = Customer::all();
//        $admin = Admin::all();
        return view('admins.order_manager.order-detail', [
            'order' => $order,
            'admin' => $admin,
            'order_details' => $orderDetails,
            'order_item' => $orderItems,
            'order_amount' => $orderAmount,
            'order_total' => $orderTotal,
//            'product' => $product,
//            'customer' => $customer,
//            'admin' => $admin
        ]);
    }
    public function updateStatusOrder(Request $request, Order $order )
    {
        $array = [];
        $array = Arr::add($array, 'order_status', $request->order_status);
        $order->update($array);
        //Quay về danh sách
        return Redirect::route('order.index')->with('success', "Edit Order's Status successfully!");
    }
}
