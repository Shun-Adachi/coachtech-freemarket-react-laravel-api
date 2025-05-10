// src/hooks/useTradeChat.ts
import { useState, useEffect } from "react";
import axios from "axios";

// --- 型定義 ---
export interface SidebarTrade {
  id: number;
  itemName: string;
}

export interface User {
  id: number;
  name: string;
  thumbnail_url: string | null;
}

export interface Item {
  id: number;
  name: string;
  price: string;
  image_url: string;
}

export interface TradeInfo {
  id: number;
  is_complete: boolean;
  purchaseUserId: number;
}

export interface Message {
  id: number;
  message: string;
  image_url: string | null;
  user: User;
  is_read: boolean;
  created_at: string;
}

// --- フック本体 ---
export function useTradeChat(tradeId: number) {
  const [loading, setLoading] = useState<boolean>(true);

  const [sidebarTrades, setSidebarTrades] = useState<SidebarTrade[]>([]);
  const [tradeInfo, setTradeInfo] = useState<TradeInfo | null>(null);
  const [item, setItem] = useState<Item | null>(null);
  const [tradePartner, setTradePartner] = useState<User | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [currentUserId, setCurrentUserId] = useState<number | null>(null);
  const [showRatingModal, setShowRatingModal] = useState<boolean>(false);

  useEffect(() => {
    const fetchAll = async () => {
      try {
        const res = await axios.get<{
          sidebarTrades: SidebarTrade[];
          trade: { id: number; is_complete: boolean; purchase_user_id: number };
          item: Item;
          tradePartner: User;
          messages: Message[];
          showModal: boolean;
          currentUserId: number;
        }>(`/api/trades/${tradeId}/messages`, {
          withCredentials: true,
        });

        // サイドバー情報
        setSidebarTrades(res.data.sidebarTrades);

        // 取引基本情報
        setTradeInfo({
          id: res.data.trade.id,
          is_complete: res.data.trade.is_complete,
          purchaseUserId: res.data.trade.purchase_user_id,
        });

        // 商品・相手ユーザー
        setItem(res.data.item);
        setTradePartner(res.data.tradePartner);

        // メッセージ一覧
        setMessages(res.data.messages);

        // 評価モーダル表示フラグ
        setShowRatingModal(res.data.showModal);

        // ログインユーザID
        setCurrentUserId(res.data.currentUserId);
      } catch (err) {
        console.error("useTradeChat fetchAll error:", err);
      } finally {
        setLoading(false);
      }
    };

    fetchAll();
  }, [tradeId]);

  return {
    loading,
    sidebarTrades,
    tradeInfo,
    item,
    tradePartner,
    messages,
    currentUserId,
    showRatingModal,
    // 以下はフック外で状態を更新したい場合に使います
    setMessages,
    setTradeInfo,
    setShowRatingModal,
  };
}
