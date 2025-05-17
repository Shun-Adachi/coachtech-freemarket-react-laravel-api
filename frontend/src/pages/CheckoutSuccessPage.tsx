// src/pages/CheckoutSuccessPage.tsx
import React, { useEffect, useState, useRef } from "react";
import { useSearchParams, useParams, useNavigate } from "react-router-dom";
import axios from "axios";

const CheckoutSuccessPage: React.FC = () => {
  const { itemId } = useParams<{ itemId: string }>();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [error, setError] = useState<string | null>(null);
  const fetchedRef = useRef(false);

  useEffect(() => {
    const sessionId = searchParams.get("session_id");
    if (!sessionId || fetchedRef.current) return; // ２回目以降は走らせない
    fetchedRef.current = true;
    const completePurchase = async () => {
      try {
        // 購入前データを再取得して配送先を取得
        const { data } = await axios.get<{
          shippingDefaults: {
            shipping_post_code: string;
            shipping_address: string;
            shipping_building: string;
          };
        }>(`/api/purchase/${itemId}`, { withCredentials: true });

        const shipping = data.shippingDefaults;
        const payment_method = Number(
          localStorage.getItem(`purchase_${itemId}_pm`)
        );

        // 購入確定 API 呼び出し
        await axios.post(
          "/api/purchase",
          {
            item_id: Number(itemId),
            payment_method,
            shipping_post_code: shipping.shipping_post_code,
            shipping_address: shipping.shipping_address,
            shipping_building: shipping.shipping_building,
          },
          { withCredentials: true }
        );

        // マイページに遷移
        navigate("/mypage");
      } catch (e: any) {
        console.error("購入確定エラー:", e);
        setError(e.response?.data?.message || "購入の確定に失敗しました。");
      }
    };
    completePurchase();
  }, [itemId, navigate]);

  if (error) return <div className="error-message">{error}</div>;
  return <div>購入手続きを完了しています…</div>;
};

export default CheckoutSuccessPage;
