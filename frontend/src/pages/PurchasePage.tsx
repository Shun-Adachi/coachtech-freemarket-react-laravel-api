// src/pages/PurchasePage.tsx
import React, { useEffect, useState} from "react";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import { loadStripe } from "@stripe/stripe-js";
import "../styles/purchase.css";

/* ---------- API 型定義 ---------- */
type Seller = { id: number; name: string };
type Item = {
  id: number;
  name: string;
  image_url: string;
  price: number;
  seller: Seller;
};

type PaymentMethod = { id: number; name: string };
type ShippingDefaults = {
  shipping_post_code: string | null;
  shipping_address: string | null;
  shipping_building: string | null;
};
type BeforeRes = {
  item: Item;
  paymentMethods: PaymentMethod[];
  shippingDefaults: ShippingDefaults;
};

type Purchase = {
  id: number;
  status: string;
  payment: string;
  shipping: {
    shipping_post_code: string;
    shipping_address: string;
    shipping_building: string;
  };
  item: Item & { price: string };
};

type ApiRes = BeforeRes;

const PurchasePage: React.FC = () => {
  const { itemId } = useParams<{ itemId: string }>();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const stripePromise = loadStripe(
    process.env.REACT_APP_STRIPE_PUBLISHABLE_KEY!
  );
  const [item, setItem] = useState<Item | null>(null);
  const [methods, setMethods] = useState<PaymentMethod[]>([]);
  const [shipping, setShipping] = useState<ShippingDefaults>({
    shipping_post_code: "",
    shipping_address: "",
    shipping_building: "",
  });
  const [selectedPM, setSelectedPM] = useState<number | "">("");

  useEffect(() => {


    const fetch = async () => {
      try {
        const { data } = await axios.get<ApiRes>(`/api/purchase/${itemId}`, {
          headers: { Accept: "application/json" },
        });

        setItem(data.item);
        setMethods(data.paymentMethods);
        setShipping(data.shippingDefaults);
        const key = `purchase_${itemId}_pm`;
        const saved = localStorage.getItem(key);
        const savedId = saved ? Number(saved) : null;
        if (savedId && data.paymentMethods.some((m) => m.id === savedId)) {
          setSelectedPM(savedId);
        } else {
          const defaultId = data.paymentMethods[0]?.id ?? "";
          setSelectedPM(defaultId);
          if (defaultId) localStorage.setItem(key, defaultId.toString());
        }
      } catch (e: any) {
        if (e.response?.status === 403) {
          alert(e.response.data.message);
          navigate("/");
        } else {
          console.error("購入情報取得エラー:", e);
        }
      } finally {
        setLoading(false);
      }
    };
    fetch();
  }, [itemId, navigate]);

  const handlePMChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const pm = Number(e.target.value);
    setSelectedPM(pm);
    const key = `purchase_${itemId}_pm`;
    localStorage.setItem(key, pm.toString());
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedPM) {
      alert("支払い方法を選択してください。");
      return;
    }
    try {
      // Checkout セッション作成
      const { data } = await axios.post<{ id: string }>(
        `/api/purchase/${itemId}/checkout`,
        {
          item_id: item!.id,
          payment_method: selectedPM,
          shipping_post_code: shipping.shipping_post_code ?? "",
          shipping_address: shipping.shipping_address ?? "",
          shipping_building: shipping.shipping_building ?? "",
        },
        {
          headers: { "Content-Type": "application/json" },
          withCredentials: true,
        }
      );
      const stripe = await stripePromise;
      const result = await stripe!.redirectToCheckout({ sessionId: data.id });
      if (result.error) {
        console.error(result.error.message);
        alert("決済画面への遷移中にエラーが発生しました。");
      }
    } catch (error: any) {
      console.error("Checkout セッション生成エラー:", error);
      alert(
        error.response?.data?.message || "決済セッションの生成に失敗しました。"
      );
    }
  };

  if (loading) return <p>読み込み中…</p>;

  if (!item) return <p>データを取得できませんでした。</p>;

  return (
    <div className="purchase-content">
      <form className="purchase-form" onSubmit={handleSubmit}>
        {/* 左側: 商品情報、支払方法、配送先 */}
        <div className="purchase-information">
          {/* 商品情報 */}
          <div className="purchase-item-information">
            <input type="hidden" name="item_id" value={item.id} />
            <img
              className="purchase-form__image"
              src={item.image_url}
              alt={item.name}
            />
            <div className="purchase-form__item-group">
              <p className="purchase-form__text--item">{item.name}</p>
              <p className="purchase-form__text--item">
                ¥ {item.price.toLocaleString()}
              </p>
            </div>
          </div>

          {/* 支払方法 */}
          <div className="payment-method">
            <label className="purchase-form__label" htmlFor="payment_method">
              支払い方法
            </label>
            <select
              className="purchase-form__select"
              name="payment_method"
              id="payment_method"
              value={selectedPM}
              onChange={handlePMChange}
              required
            >
              <option value="">選択してください</option>
              {methods.map((m) => (
                <option key={m.id} value={m.id}>
                  {" "}
                  {m.name}
                </option>
              ))}
            </select>
            <p className="purchase-form__error-message">
              {/* バリデーションメッセージ */}
            </p>
          </div>

          {/* 配送先住所 */}
          <div className="purchase-address">
            <div className="purchase-address__group">
              <label className="purchase-form__label">配送先</label>
              <div className="purchase-address__sub-group">
                <p className="purchase-form__text--address">〒</p>
                <input
                  className="purchase-form__input"
                  type="text"
                  name="shipping_post_code"
                  value={shipping.shipping_post_code ?? ""}
                  onChange={(e) =>
                    setShipping({
                      ...shipping,
                      shipping_post_code: e.target.value,
                    })
                  }
                  required
                />
              </div>
              <p className="purchase-form__error-message"></p>
              <div className="purchase-address__sub-group">
                <p className="purchase-form__text--address"></p>
                <input
                  className="purchase-form__input"
                  type="text"
                  name="shipping_address"
                  value={shipping.shipping_address ?? ""}
                  onChange={(e) =>
                    setShipping({
                      ...shipping,
                      shipping_address: e.target.value,
                    })
                  }
                  required
                />
              </div>
              <p className="purchase-form__error-message"></p>
              <div className="purchase-address__sub-group">
                <p className="purchase-form__text--address"></p>
                <input
                  className="purchase-form__input"
                  type="text"
                  name="shipping_building"
                  value={shipping.shipping_building ?? ""}
                  onChange={(e) =>
                    setShipping({
                      ...shipping,
                      shipping_building: e.target.value,
                    })
                  }
                />
              </div>
            </div>
            {/* 住所変更リンク */}
            <button
              type="button"
              className="purchase-form__link"
              onClick={() => navigate(`/purchase/address/${item.id}`)}
            >
              変更する
            </button>
          </div>
        </div>

        {/* 右側: 支払概要、購入ボタン */}
        <div className="payment-summary">
          <table className="purchase-form__table">
            <tbody>
              <tr className="purchase-form__row">
                <th className="purchase-form__cell">商品代金</th>
                <td className="purchase-form__cell">
                  ¥ {item.price.toLocaleString()}
                </td>
              </tr>
              <tr className="purchase-form__row">
                <th className="purchase-form__cell">支払い方法</th>
                <td className="purchase-form__cell">
                  {methods.find((m) => m.id === selectedPM)?.name || ""}
                </td>
              </tr>
            </tbody>
          </table>
          <button
            type="submit"
            className={
              selectedPM
                ? "purchase-form__button--active"
                : "purchase-form__button--inactive"
            }
          >
            購入する
          </button>
        </div>
      </form>
    </div>
  );
};

export default PurchasePage;
