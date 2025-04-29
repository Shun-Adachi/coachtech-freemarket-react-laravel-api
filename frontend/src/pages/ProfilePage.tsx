// src/pages/ProfilePage.tsx
import React, { useEffect, useState } from "react";
import axios from "axios";

import "../styles/common/item-list.css";

interface Item {
  id: number;
  name: string;
  image_url: string;
  message_count: number | null;
}

interface ProfileData {
  user: { id: number; name: string };
  items: Item[];
  totalTradePartnerMessages: number;
  averageTradeRating: number;
  ratingCount: number;
  tab: string;
}

const ProfilePage: React.FC = () => {
  const [data, setData] = useState<ProfileData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        const res = await axios.get<ProfileData>("/api/mypage", {
          headers: { Accept: "application/json" },
        });
        setData(res.data);
      } catch (err) {
        console.error("プロフィール取得エラー:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchProfile();
  }, []);

  if (loading) {
    return <div>読み込み中…</div>;
  }

  if (!data) {
    return <div>プロフィールを取得できませんでした。</div>;
  }

  return (
    <div className="my-page">
      <h1>{data.user.name}さんのマイページ</h1>
      <p>
        評価: {data.averageTradeRating} / {data.ratingCount}件
      </p>
      <p>未読メッセージ: {data.totalTradePartnerMessages}</p>

      <div className="item-list">
        {data.items.map((item) => (
          <div key={item.id} className="item-card">
            <img
              src={item.image_url}
              alt={item.name}
              className="item-card__image"
            />
            <p className="item-card__label">{item.name}</p>
            {item.message_count && (
              <span className="item-card__unread-badge">
                {item.message_count}
              </span>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default ProfilePage;
