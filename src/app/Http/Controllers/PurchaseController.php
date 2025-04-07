<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PaymentMethod;
use App\Models\Item;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Trade;
use App\Http\Requests\EditAddressRequest;
use App\Http\Requests\PurchaseRequest;
use Stripe\StripeClient;

class PurchaseController extends Controller
{
    protected $stripeClient;

    public function __construct(StripeClient $stripeClient)
    {
        $this->stripeClient = $stripeClient;
    }

    // 購入画面表示
    public function purchase(Request $request, $itemId)
    {
        $item = Item::where('id', $itemId)->first();
        $user = User::findOrFail(Auth::id());
        $paymentMethods = PaymentMethod::get();
        $purchase =  Purchase::where('item_id', $itemId)->exists();

        // 円形式変換
        $item->price = number_format($item->price);

        return view('purchase', compact('user', 'item', 'paymentMethods', 'purchase'));
    }

    // 配送先住所変更画面表示
    public function edit(Request $request)
    {
        $user = auth()->user();
        $itemId = $request->item_id;

        // 支払方法の更新(POST時のみ)
        if ($request->payment_method) {
            User::where('id', $user->id)->update(['payment_method_id' => $request->payment_method]);
            $user->payment_method_id = $request->payment_method;
        }

        return view('edit-address', compact('user', 'itemId'));
    }

    // 配送先住所を変更処理
    public function update(EditAddressRequest $request)
    {
        $user = auth()->user();
        $currentUserData  = [
            'shipping_post_code' => $user->shipping_post_code,
            'shipping_address' => $user->shipping_address,
            'shipping_building' => $user->shipping_building,
        ];

        $updateData  = [
            'shipping_post_code' => $request->shipping_post_code,
            'shipping_address' => $request->shipping_address,
            'shipping_building' => $request->shipping_building,
        ];

        // 変更なしの場合は更新処理およびメッセージなし
        if ($currentUserData == $updateData) {
            return redirect()->route('purchase', ['item_id' => $request->item_id]);
        }

        User::where('id', $user->id)->update($updateData);
        return redirect()->route('purchase', ['item_id' => $request->item_id])->with('message', '配送先住所を変更しました');
    }

    // 購入処理
    public function buy()
    {
        $user = auth()->user();
        $requestData = session('request_data', []);
        $user = Auth::user();

        if (!$requestData) {
            return redirect('/mypage')->with('error', '決済情報が見つかりません');
        }

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'item_id' => $requestData['item_id'],
            'payment_method_id' => $requestData['payment_method'],
            'shipping_post_code' => $requestData['shipping_post_code'],
            'shipping_address' => $requestData['shipping_address'],
            'shipping_building' => $requestData['shipping_building'],
        ]);

        Trade::create([
            'purchase_id' => $purchase->id,
            'is_complete' => false,
        ]);

        return redirect('/mypage')->with('message', '商品を購入しました');
    }

    // Stripeセッション
    public function createCheckoutSession(PurchaseRequest $request)
    {
        $user = auth()->user();
        $item = Item::where('id', $request->item_id)->first();

        try {
            $session = $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name' => $item->name,
                        ],
                        'unit_amount' => $item->price,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'customer_email' =>  $user->email,
                'success_url' => url('/purchase/buy'),
                'cancel_url' => url('/purchase/' . $request->item_id),
            ]);

            session()->put('request_data', $request->all());

            return redirect($session->url);
        } catch (\Exception $e) {
            return back()->with('error', 'チェックアウトセッションの生成に失敗しました ' . $e->getMessage());
        }
    }
}
