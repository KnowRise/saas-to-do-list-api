<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Midtrans\Snap;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)->with('plan')->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $plan = Plan::findOrFail($request->plan_id);
        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'invoice_number' => 'INV-' . Str::random(10),
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with('plan')->findOrFail($id);
        $invoice = Invoice::where('order_id', $order->id)->first();

        $data = [
            'order' => $order,
        ];

        if ($invoice) {
            $data['invoice'] = $invoice;
        }

        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully',
        ]);
    }
}
