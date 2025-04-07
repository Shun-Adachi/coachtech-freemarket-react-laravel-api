<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UserRequest;
use App\Models\Trade;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;

class UserController extends Controller
{
    // プロフィール画面表示
    public function index(Request $request)
    {
        // ユーザー情報・タブ情報取得
        $user = Auth::user();
        $userId = $user->id ?? null;
        $tab = $request->tab;

        // 取引が自分に関係しているもので評価を入力していないものを表示
        $trades = Trade::with(['purchase.item', 'tradeMessages'])
            ->where(function($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    // 自分が購入者の場合、buyer_rating_pointsがnull
                    $q->whereHas('purchase', function ($q2) use ($userId) {
                        $q2->where('user_id', $userId);
                    })->whereNull('buyer_rating_points');
                })->orWhere(function ($q) use ($userId) {
                    // 自分が出品者の場合、seller_rating_pointsがnull
                    $q->whereHas('purchase.item', function ($q2) use ($userId) {
                        $q2->where('user_id', $userId);
                    })->whereNull('seller_rating_points');
                });
            })
            ->get();

        // 各取引ごとに、最新の取引メッセージ日時を算出
        // ※メッセージが存在しない場合は、tradeの作成日時(created_at)をフォールバックとする
        $trades->each(function ($trade) {
            $latestMessageDate = $trade->tradeMessages->max('created_at');
            $trade->latest_trade_message_at = $latestMessageDate ?? $trade->created_at;
        });

        // 取引を商品単位にグループ化（trade->purchase->item）
        $groupedTrades = $trades->groupBy(function ($trade) {
            return $trade->purchase->item->id;
        });

        // グループごとに、最新の取引メッセージ日時と、未読メッセージ数を持つItemを作成
        $items = $groupedTrades->map(function ($tradesForItem) use ($userId) {
            $item = $tradesForItem->first()->purchase->item;
            $item->trade_id = $tradesForItem->first()->id;

            // 「自分以外が送った」メッセージだけを対象とする
            $partnerMessages = $tradesForItem->flatMap(function ($trade) use ($userId) {
                return $trade->tradeMessages->where('user_id', '!=', $userId);
            });
            // 最新の相手メッセージの作成日時を取得（相手のメッセージがない場合は null）
            $item->latest_partner_message_at = $partnerMessages->max('created_at');
            // 未読の相手からのメッセージ数もカウント（任意）
            $item->message_count = $partnerMessages->where('is_read', false)->count();
            return $item;
        });

        // 未読メッセージの合計を取得
        $totalTradePartnerMessages = $items->sum('message_count');

        // 新規メッセージが来た順に並べ替え
        $items = $items->sortByDesc(function ($item) {
            return $item->latest_partner_message_at ? $item->latest_partner_message_at->timestamp : 0;
        })->values();

        // 出品した商品の場合、商品リストを更新
        if ($tab === 'sell') {
            $items = Item::where('user_id', $userId)->get();
        }
        // 購入した商品の場合、商品リストを更新
        else if(!$tab){
            $purchaseItemIds = Purchase::where('user_id', $userId)->pluck('item_id');
            $items = Item::whereIn('id', $purchaseItemIds)->get();
        }

        // 自身に関わる取引取得
        $allTrades = Trade::with(['purchase.item', 'purchase.user'])
            ->where(function ($query) use ($userId) {
                $query->whereHas('purchase', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->orWhereHas('purchase.item', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->get();

        // 自分に対する評価の数と合計値を取得
        $totalRating = 0;
        $ratingCount = 0;
        foreach ($allTrades as $trade) {
            // ユーザーが購入者の場合（出品者側の評価）
            if ($trade->purchase->user_id == $userId && $trade->seller_rating_points !== null) {
                $totalRating += $trade->seller_rating_points;
                $ratingCount++;
            }
            // ユーザーが出品者の場合（購入者側の評価）
            if ($trade->purchase->item->user_id == $userId && $trade->buyer_rating_points !== null) {
                $totalRating += $trade->buyer_rating_points;
                $ratingCount++;
            }
        }

        // 平均値処理
        $averageTradeRating = $ratingCount > 0 ? round($totalRating / $ratingCount) : 0;

        return view('mypage', compact('user', 'items', 'totalTradePartnerMessages', 'averageTradeRating','ratingCount', 'tab'));
    }

    // プロフィール編集ページ表示
    public function edit(Request $request)
    {
        //表示
        $user = Auth::user();
        return view('edit-profile', compact('user'));
    }

    // プロフィール更新
    public function update(UserRequest $request)
    {
        $user = auth()->user();
        $tempImage = $request->temp_image;

        // 更新前データ
        $currentUserData  = [
            'name' => $user->name,
            'current_post_code' => $user->current_post_code,
            'current_address' => $user->current_address,
            'current_building' => $user->current_building,
            'thumbnail_path' => $user->thumbnail_path,
        ];

        // 画像選択あり
        if ($request->hasFile('image')) {
            // 古い画像を削除
            $this->deleteThumbnail($user->thumbnail_path);
            // ファイルを保存し、パスを取得
            $thumbnailPath = $request->file('image')->store('images/users/', 'public');
        }
        // 画像選択なし、一時ファイルあり
        elseif ($tempImage) {
            // 古い画像を削除
            $this->deleteThumbnail($user->thumbnail_path);
            // 一時ファイルを移動し、パスを取得
            $thumbnailPath = moveTempImageToPermanentLocation($tempImage, 'images/users/');
        }
        // 画像選択なし、一時ファイルなし
        else {
            $thumbnailPath = $user->thumbnail_path;
        }

        // 更新データ
        $updateData = [
            'name' => $request->input(['name']),
            'current_post_code' => $request->input(['current_post_code']),
            'current_address' => $request->input(['current_address']),
            'current_building' => $request->input(['current_building']),
            'thumbnail_path' => $thumbnailPath,
        ];

        // 変更なしの場合は更新処理およびメッセージなし
        if ($currentUserData == $updateData) {
            return redirect('/mypage/profile');
        }

        //初回ログイン時は現住所と送付先を同時に変更
        if (!$user->current_post_code && !$user->current_address && !$user->current_building) {
            $shippingData = [
                'shipping_post_code' => $request->input(['current_post_code']),
                'shipping_address' => $request->input(['current_address']),
                'shipping_building' => $request->input(['current_building']),
            ];
            $updateData = array_merge($updateData, $shippingData);
        }

        User::where('id', $request->id)->update($updateData);
        return redirect('/')->with('message', 'プロフィールが更新されました');
    }

    // ログアウト処理
    public function logout()
    {
        Auth::logout();
        return redirect('/')->with('message', 'ログアウトしました');
    }

    //ユーザープロフィール画像削除
    public function deleteThumbnail($thumbnailPath)
    {
        $dummyDataDirectory = 'default/users/';
        if ($thumbnailPath && !str_starts_with($thumbnailPath, $dummyDataDirectory)) {
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }
    }
}
