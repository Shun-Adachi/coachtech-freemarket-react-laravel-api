import React, { useState, useEffect } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import axios from "axios";

import "../styles/common/item-list.css";

interface Item {
  id: number;
  name: string;
  image_url: string;
  is_sold: boolean;
  message_count?: number;
}

const ItemList: React.FC = () => {
  const navigate = useNavigate();
  const { search, pathname } = useLocation();
  const params = new URLSearchParams(search);
  const tab = params.get("tab") || "";

  const [items, setItems] = useState<Item[]>([]);

  // 環境変数からベースURLを取得（.envに設定している場合）
  const base = process.env.REACT_APP_API_BASE_URL!;

  useEffect(() => {
    const fetchItems = async () => {
      try {
        // proxyを使わない場合、baseを利用して正しいURLを構築
        const url = `${base}/api/items${tab ? `?tab=${tab}` : ""}`;
        console.log("📦 Fetching items from", url);
        const res = await axios.get<Item[]>(url);
        console.log("📦 API Items:", res.data);
        setItems(res.data);
      } catch (err) {
        console.error("❌ fetch error:", err);
      }
    };
    fetchItems();
  }, [tab, base]);

  // タブ切り替えハンドラ
  const handleTab = (selectedTab: string) => {
    const searchParams = new URLSearchParams();
    if (selectedTab) searchParams.set("tab", selectedTab);
    navigate(
      `/${searchParams.toString() ? `?${searchParams.toString()}` : ""}`
    );
  };

  return (
    <>
      <div className="item-tab">
        <button
          className={
            tab === "mylist" ? "item-tab__link" : "item-tab__link--active"
          }
          onClick={() => handleTab("")}
        >
          おすすめ
        </button>
        <button
          className={
            tab === "mylist" ? "item-tab__link--active" : "item-tab__link"
          }
          onClick={() => handleTab("mylist")}
        >
          マイリスト
        </button>
      </div>

      <div className="item-list">
        {items.map((item) => (
          <div key={item.id} className="item-card">
            <Link to={`/item/${item.id}`} className="item-card__link">
              {item.is_sold && (
                <span className="item-card__text--sold">Sold</span>
              )}
              {item.message_count && item.message_count > 0 && (
                <span className="item-card__unread-badge">
                  {item.message_count}
                </span>
              )}
              <img
                className="item-card__image"
                src={item.image_url}
                alt={item.name}
              />
            </Link>
            <p className="item-card__label">{item.name}</p>
          </div>
        ))}
      </div>
    </>
  );
};

export default ItemList;
