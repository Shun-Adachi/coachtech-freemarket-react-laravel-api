// src/pages/ItemDetail.tsx
import React, { useState, useEffect } from "react";
import { Link, useParams, useNavigate, useLocation } from "react-router-dom";
import axios from "axios";
import { isLoggedIn } from "../utils/auth";

import "../styles/common/item-list.css";
import "../styles/item.css";

// API から返却されるデータ構造
interface ItemDetailData {
  id: number;
  name: string;
  image_url: string;
  price: string;
  description: string;
  user: {
    id: number;
    name: string;
  };
  purchase: boolean;
  isFavorite: boolean;
  favoritesCount: number;
  commentsCount: number;
  categories: string[];
  condition: {
    id: number;
    name: string;
  };
}

interface Comment {
  id: number;
  message: string;
  createdAt: string;
  user: {
    name: string;
    thumbnail_url: string | null;
  };
}

interface CommentsResponse {
  comments: Comment[];
}

const ItemDetail: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { itemId } = useParams<{ itemId: string }>();
  const [toggling, setToggling] = useState(false);
  const [item, setItem] = useState<ItemDetailData | null>(null);
  const [comments, setComments] = useState<Comment[]>([]);
  const [loading, setLoading] = useState(true);
  const [commentText, setCommentText] = useState("");
  const [posting, setPosting] = useState(false);

  useEffect(() => {
    const fetchItem = async () => {
      try {
        const res = await axios.get<ItemDetailData>(`/api/item/${itemId}`, {
          headers: { Accept: "application/json" },
        });
        setItem(res.data);
      } catch (err) {
        console.error("商品詳細取得エラー:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchItem();
  }, [itemId]);

  useEffect(() => {
    const fetchComments = async () => {
      try {
        const res = await axios.get<CommentsResponse>(
          `/api/item/${itemId}/comments`,
          { headers: { Accept: "application/json" } }
        );
        setComments(res.data.comments);
      } catch (err) {
        console.error("コメント一覧取得エラー:", err);
      }
    };
    if (item) fetchComments();
  }, [itemId, item]);

  if (loading) return <div>読み込み中…</div>;
  if (!item) return <div>商品が見つかりませんでした。</div>;

  // お気に入り登録・解除
  const handleToggleFavorite = async () => {
    if (!item || toggling) return;
    setToggling(true);
    /* 未ログイン ⇒ /login へリダイレクト */
    if (!isLoggedIn()) {
      navigate("/login", { state: { from: location.pathname } });
      return;
    }
    try {
      if (item.isFavorite) {
        await axios.delete(`/api/item/${item.id}/favorite`, {
          headers: { Accept: "application/json" },
        });
        setItem(
          (prev) =>
            prev && {
              ...prev,
              isFavorite: false,
              favoritesCount: prev.favoritesCount - 1,
            }
        );
      } else {
        await axios.post(`/api/item/${item.id}/favorite`, null, {
          headers: { Accept: "application/json" },
        });
        setItem(
          (prev) =>
            prev && {
              ...prev,
              isFavorite: true,
              favoritesCount: prev.favoritesCount + 1,
            }
        );
      }
    } catch (err: any) {
      if (err.response?.status === 403) {
        alert(err.response.data.message);
      } else {
        console.error("お気に入り切り替えエラー:", err);
      }
    } finally {
      setToggling(false);
    }
  };

  // コメント送信
  const handleSubmitComment = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!commentText.trim() || posting) return;

    if (!isLoggedIn()) {
      navigate("/login", { state: { from: location } });
      return;
    }

    setPosting(true);
    try {
      const res = await axios.post<{ comment: Comment }>(
        `/api/item/${itemId}/comments`,
        { comment: commentText },
        { headers: { Accept: "application/json" } }
      );
      setComments((prev) =>
        prev ? [...prev, res.data.comment] : [res.data.comment]
      );

      setItem((prev) =>
        prev
          ? {
              ...prev,
              commentsCount: prev.commentsCount + 1,
            }
          : prev
      );
      setCommentText("");
    } catch (err: any) {
      console.error("コメント送信エラー:", err);
      alert(err.response?.data?.message || "コメント送信に失敗しました");
    } finally {
      setPosting(false);
    }
  };

  return (
    <div className="item-content">
      {/* 商品画像 */}
      <img className="item__image" src={item.image_url} alt={item.name} />

      {/* 商品情報 */}
      <div className="item-information">
        <h1 className="item-information__main-heading">{item.name}</h1>
        <p className="item-information__label--brand">{item.user.name}</p>
        <p className="item-information__label--price">¥ {item.price}</p>

        <div className="item-icon">
          <div className="item-icon__group">
            <button
              type="button"
              onClick={handleToggleFavorite}
              disabled={item.purchase || toggling}
              className={`item-icon__link--${
                item.purchase ? "inactive" : "active"
              }`}
            >
              <img
                className="item-icon__image"
                src={`/images/favorite-${
                  item.isFavorite ? "active" : "inactive"
                }.png`}
                alt={item.isFavorite ? "お気に入り済み" : "お気に入り登録"}
              />
            </button>
            <p className="item-icon__label">{item.favoritesCount}</p>
          </div>
          <div className="item-icon__group">
            <img
              className="item-icon__image"
              src="/images/comment.svg"
              alt="コメントアイコン"
            />
            <p className="item-icon__label">{item.commentsCount}</p>
          </div>
        </div>

        {item.purchase ? (
          <span className="item-form__link--inactive">購入手続きへ</span>
        ) : (
          <Link className="item-form__link--active" to={`/purchase/${item.id}`}>
            購入手続きへ
          </Link>
        )}

        <h2 className="item-information__sub-heading">商品説明</h2>
        <p className="item-information__label--description">
          {(item.description ?? "").split("\n").map((line, idx) => (
            <React.Fragment key={idx}>
              {line}
              <br />
            </React.Fragment>
          ))}
        </p>

        <h2 className="item-information__sub-heading">商品の情報</h2>
        <div className="item-category">
          <h3 className="item-category__heading">カテゴリー</h3>
          <div className="item-category__group">
            {item.categories.map((name, i) => (
              <p key={i} className="item-category__label">
                {name}
              </p>
            ))}
          </div>
        </div>
        <div className="item-condition">
          <h3 className="item-condition__heading">商品の状態</h3>
          <div className="item-condition__group">
            <p className="item-condition__label">{item.condition.name}</p>
          </div>
        </div>

        {/* コメント一覧 */}
        <div className="item-comment">
          <h2 className="item-information__sub-heading">
            コメント ({item.commentsCount})
          </h2>
          {comments.length > 0 ? (
            comments.map((c) => (
              <React.Fragment key={c.id}>
                <div className="item-comment__user-group">
                  <img
                    className="item-comment__user-image"
                    src={
                      c.user.thumbnail_url
                        ? c.user.thumbnail_url
                        : "/images/default-profile.png"
                    }
                    alt={c.user.name}
                  />
                  <p className="item-comment__user-name">{c.user.name}</p>
                </div>
                <p className="item-comment__text">
                  {(c.message ?? "").split("\n").map((line, idx) => (
                    <React.Fragment key={idx}>
                      {line}
                      <br />
                    </React.Fragment>
                  ))}
                </p>
              </React.Fragment>
            ))
          ) : (
            <div className="item-comment__user-group">
              <p className="item-comment__text">
                この商品に関するコメントはありません
              </p>
            </div>
          )}
        </div>

        {/* コメント投稿フォーム */}
        <form className="item-form" onSubmit={handleSubmitComment}>
          <h2 className="item-information__sub-heading">商品へのコメント</h2>
          <textarea
            className="item-form__textarea"
            placeholder="コメントを入力"
            value={commentText}
            onChange={(e) => setCommentText(e.target.value)}
            disabled={posting}
          />
          <button
            type="submit"
            className="item-form__button--active"
            disabled={posting || !commentText.trim()}
          >
            {posting ? "送信中…" : "コメントを投稿"}
          </button>
        </form>
      </div>
    </div>
  );
};

export default ItemDetail;
