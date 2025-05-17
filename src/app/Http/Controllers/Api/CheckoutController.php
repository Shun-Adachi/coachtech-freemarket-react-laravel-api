<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use Stripe\StripeClient;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Stripe Checkout セッションを作成して ID を返す
     */
    public function createCheckoutSession(PurchaseRequest $request, string $item_id): JsonResponse
    {
        $user = Auth::user();
        $item = Item::findOrFail($item_id);

        $front = config('app.frontend_url');
        $success = "{$front}/purchase/{$item_id}/complete?session_id={CHECKOUT_SESSION_ID}";
        $cancel  = "{$front}/purchase/{$item_id}";

        try {
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => ['name' => $item->name],
                        'unit_amount' => $item->price,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'customer_email' => $user->email,
                'success_url' => $success,
                'cancel_url'  => $cancel,
            ]);

            return response()->json(['id' => $session->id]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'チェックアウトセッションの生成に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
