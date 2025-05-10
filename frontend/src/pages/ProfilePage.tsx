// src/pages/ProfilePage.tsx
import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import axios from "axios";

import "../styles/common/item-list.css";
import "../styles/mypage.css";

type Item = {
  id: number;
  trade_id: number | null;
  name: string;
  image_url: string;
  is_sold: boolean;
  message_count: number | null;
};

type ProfileData = {
  user: { id: number; name: string; thumbnail_url: string };
  sellingItems: Item[];
  purchasedItems: Item[];
  tradingItems: Item[];
  totalTradePartnerMessages: number;
  averageTradeRating: number;
  ratingCount: number;
};

const ProfilePage: React.FC = () => {
  const base = process.env.REACT_APP_API_BASE_URL || "";
  const [tab, setTab] = useState<"sell" | "purchase" | "trade">("sell");
  const [data, setData] = useState<ProfileData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const res = await axios.get<ProfileData>(`${base}/api/mypage`, {
          headers: { Accept: "application/json" },
          withCredentials: true,
        });
        setData(res.data);
      } catch (err) {
        console.error("プロフィール取得エラー:", err);
      } finally {
        setLoading(false);
      }
    })();
  }, [base]);
  const handleTabClick = (newTab: "sell" | "purchase" | "trade") => {
    setTab(newTab);
  };

  if (loading) return <div>読み込み中…</div>;
  if (!data) return <div>プロフィールを取得できませんでした。</div>;

  let itemsToShow: Item[];
  if (tab === "sell") itemsToShow = data.sellingItems;
  else if (tab === "purchase") itemsToShow = data.purchasedItems;
  else itemsToShow = data.tradingItems;

  // プロフィール画像URL生成
  const thumb = data.user.thumbnail_url?.trim() || "";
  let profileSrc: string;
  if (thumb.startsWith("http")) {
    profileSrc = thumb;
  } else if (thumb.startsWith("/storage/")) {
    profileSrc = `${base}${thumb}`;
  } else if (thumb) {
    profileSrc = `${base}/storage/${thumb}`;
  } else {
    profileSrc = "/images/default-profile.png";
  }

  return (
    <div className="my-page">
      {/* ユーザー情報 */}
      <div className="profile__group">
        <img
          className="profile__image"
          src={profileSrc}
          alt="プロフィール画像"
        />
        <div className="profile__group--detail">
          <p className="profile__label">{data.user.name}</p>
          {/* 星評価表示 */}
          <div className="profile__group--rating">
            {Array.from({ length: 5 }).map((_, i) => (
              <span key={i} className="profile__rating">
                {i < data.averageTradeRating ? "★" : "☆"}
              </span>
            ))}
          </div>
        </div>
        {/* プロフィール編集ボタン */}
        <Link to="/mypage/profile" className="profile__link">
          プロフィール編集
        </Link>
      </div>

      {/* タブ */}
      <div className="item-tab">
        <button
          className={`item-tab__link${
            tab === "sell" ? " item-tab__link--active" : ""
          }`}
          onClick={() => handleTabClick("sell")}
        >
          出品した商品
        </button>
        <button
          className={`item-tab__link${
            tab === "purchase" ? " item-tab__link--active" : ""
          }`}
          onClick={() => handleTabClick("purchase")}
        >
          購入した商品
        </button>
        <button
          className={`item-tab__link${
            tab === "trade" ? " item-tab__link--active" : ""
          }`}
          onClick={() => handleTabClick("trade")}
        >
          取引中の商品
        </button>
        {data.totalTradePartnerMessages > 0 && (
          <span className="item-tab__message-count">
            {data.totalTradePartnerMessages}
          </span>
        )}
      </div>

      {/* 商品一覧 */}
      <div className="item-list">
        {itemsToShow.map((item) => {
          const path = item.image_url?.trim() || "";
          let imgSrc: string;
          if (path.startsWith("http")) {
            imgSrc = path;
          } else if (path.startsWith("/storage/")) {
            imgSrc = `${base}${path}`;
          } else {
            imgSrc = `${base}/storage/${path}`;
          }
          // タブごとにリンク先を切り替え
          const to =
            tab === "trade" && item.trade_id
              ? `/trades/${item.trade_id}/messages`
              : `/item/${item.id}`;

          return (
            <div key={item.id} className="item-card">
              <Link to={to} className="item-card__link">
                <img
                  src={imgSrc}
                  alt={item.name}
                  className="item-card__image"
                />
                {tab === "sell" && item.is_sold && (
                  <span className="item-card__text--sold">Sold</span>
                )}
                {tab === "trade" && item.message_count! > 0 && (
                  <span className="item-card__unread-badge">
                    {item.message_count}
                  </span>
                )}
              </Link>
              <p className="item-card__label">{item.name}</p>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default ProfilePage;
