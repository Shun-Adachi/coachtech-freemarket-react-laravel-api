import React, { useState, useEffect, useMemo } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import axios from "axios";

import "../styles/common/item-list.css";

interface Item {
  id: number;
  name: string;
  image_url: string;
  is_sold: boolean;
  /** API で追加したプロパティ  */
  isFavorite: boolean;
}
const ItemList: React.FC = () => {
  const [items, setItems] = useState<Item[]>([]);
  const [tab, setTab] = useState<"recommend" | "mylist">("recommend");

  // .env で指定した API のベース URL
  const base = process.env.REACT_APP_API_BASE_URL ?? "";

  /** 一覧取得（初回だけ）  */
  useEffect(() => {
    const fetch = async () => {
      try {
        const res = await axios.get<Item[]>(`${base}/api/items`, {
          headers: { Accept: "application/json" },
        });
        setItems(res.data);
      } catch (e) {
        console.error("❌ 商品一覧取得エラー:", e);
      }
    };
    fetch();
  }, [base]);

  /** タブに応じた表示アイテムをメモ化  */
  const visibleItems = useMemo(() => {
    if (tab === "mylist") return items.filter((i) => i.isFavorite);
    return items;
  }, [items, tab]);
  return (
    <>
      <div className="item-tab">
        <button
          className={
            tab === "mylist" ? "item-tab__link" : "item-tab__link--active"
          }
          onClick={() => setTab("recommend")}
        >
          おすすめ
        </button>
        <button
          className={
            tab === "mylist" ? "item-tab__link--active" : "item-tab__link"
          }
          onClick={() => setTab("mylist")}
        >
          マイリスト
        </button>
      </div>

      <div className="item-list">
        {visibleItems.map((item) => (
          <div key={item.id} className="item-card">
            <Link to={`/item/${item.id}`} className="item-card__link">
              {item.is_sold && (
                <span className="item-card__text--sold">Sold</span>
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
