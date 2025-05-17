// src/pages/EditAddressPage.tsx
import React, { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";

import "../styles/common/user-form.css";

/* ---------- API 型定義 ---------- */
type ShippingDefaults = {
  shipping_post_code: string | null;
  shipping_address: string | null;
  shipping_building: string | null;
};

type EditRes = {
  shippingDefaults: ShippingDefaults;
};

const EditAddressPage: React.FC = () => {
  const { itemId } = useParams<{ itemId: string }>();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [shipping, setShipping] = useState<ShippingDefaults>({
    shipping_post_code: "",
    shipping_address: "",
    shipping_building: "",
  });
  const [errors, setErrors] = useState<{ [key: string]: string[] }>({});
  const [saving, setSaving] = useState(false);

  // 初期データ取得
  useEffect(() => {
    const fetch = async () => {
      try {
        const { data } = await axios.get<EditRes>(
          `/api/purchase/address/${itemId}`,
          {
            headers: { Accept: "application/json" },
          }
        );
        setShipping(data.shippingDefaults);
      } catch (e) {
        console.error("配送先取得エラー:", e);
      } finally {
        setLoading(false);
      }
    };
    fetch();
  }, [itemId]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setShipping((prev) => ({ ...prev, [name]: value }));
  };
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      await axios.post(
        `/api/purchase/address/${itemId}`,
        { _method: "PUT", ...shipping },
        {
          headers: { "Content-Type": "application/json" },
          withCredentials: true,
        }
      );
      navigate(`/purchase/${itemId}`);
    } catch (e: any) {
      console.error("配送先更新エラー:", e);
      if (e.response?.status === 422) {
        setErrors(e.response.data.errors || {});
      } else {
        alert("配送先の更新に失敗しました。もう一度お試しください。");
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) return <p>読み込み中…</p>;

  return (
    <div className="purchase-content">
      <div className="user-form">
        <h1 className="user-form__heading">配送先変更</h1>
        <form className="user-form__form" onSubmit={handleSubmit}>
          <div className="user-form__group">
            <label htmlFor="post" className="user-form__label">
              郵便番号
            </label>
            <input
              id="shipping_post_code"
              name="shipping_post_code"
              type="text"
              className="user-form__input"
              placeholder="例: 001-0000"
              value={shipping.shipping_post_code ?? ""}
              onChange={handleChange}
            />
            {errors.shipping_post_code && (
              <div className="user-form__error-message">
                {errors.shipping_post_code[0]}
              </div>
            )}
          </div>
          <div className="user-form__group">
            <label htmlFor="addr" className="user-form__label">
              住所
            </label>
            <input
              id="shipping_address"
              name="shipping_address"
              type="text"
              className="user-form__input"
              value={shipping.shipping_address ?? ""}
              onChange={handleChange}
            />
            {errors.shipping_address && (
              <div className="user-form__error-message">
                {errors.shipping_address[0]}
              </div>
            )}
          </div>
          <div className="user-form__group">
            <label htmlFor="build" className="user-form__label">
              建物名・部屋番号
            </label>
            <input
              id="shipping_building"
              name="shipping_building"
              type="text"
              className="user-form__input"
              value={shipping.shipping_building ?? ""}
              onChange={handleChange}
            />
            {errors.shipping_building && (
              <div className="user-form__error-message">
                {errors.shipping_building[0]}
              </div>
            )}
          </div>
          <button type="submit" className="user-form__button" disabled={saving}>
            {saving ? "保存中…" : "保存"}
          </button>
        </form>
      </div>
    </div>
  );
};

export default EditAddressPage;
