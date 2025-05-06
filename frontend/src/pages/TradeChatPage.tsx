// src/pages/TradeChatPage.tsx
import React, {
  useState,
  useEffect,
  useRef,
  FormEvent,
  ChangeEvent,
} from "react";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import "../styles/trade-chat.css";
import "../styles/rating-modal.css";

interface SidebarTrade {
  id: number;
  itemName: string;
}

interface User {
  id: number;
  name: string;
  thumbnail_url: string | null;
}

interface Item {
  id: number;
  name: string;
  price: string;
  image_url: string;
}

interface TradeInfo {
  id: number;
  is_complete: boolean;
  purchaseUserId: number;
}

interface Message {
  id: number;
  message: string;
  image_url: string | null;
  user: User;
  is_read: boolean;
  created_at: string;
}

const TradeChatPage: React.FC = () => {
  const { tradeId } = useParams<{ tradeId: string }>();
  const navigate = useNavigate();

  // ステート
  const [loading, setLoading] = useState(true);
  const [sidebarTrades, setSidebarTrades] = useState<SidebarTrade[]>([]);
  const [tradeInfo, setTradeInfo] = useState<TradeInfo | null>(null);
  const [item, setItem] = useState<Item | null>(null);
  const [tradePartner, setTradePartner] = useState<User | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [editingId, setEditingId] = useState<number | null>(null);

  const [newMessage, setNewMessage] = useState("");
  const [newImage, setNewImage] = useState<File | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const textareaRef = useRef<HTMLTextAreaElement>(null);
  useEffect(() => {
    const ta = textareaRef.current;
    if (ta) {
      ta.style.height = "auto";
      ta.style.height = ta.scrollHeight + "px";
    }
    const saved = localStorage.getItem("tradeMessageDraft");
    if (saved) setNewMessage(saved);
  }, []);

  const [currentUserId, setCurrentUserId] = useState<number | null>(null);
  const [showRatingModal, setShowRatingModal] = useState(false);
  const [rating, setRating] = useState(3);

  // マウント時にデータ取得
  useEffect(() => {
    const fetchAll = async () => {
      try {
        // 1) チャット画面用データ一式を取得
        const { data } = await axios.get<{
          sidebarTrades: SidebarTrade[];
          trade: { id: number; is_complete: boolean; purchase_user_id: number };
          item: Item;
          tradePartner: User;
          messages: Message[];
          showModal: boolean;
          currentUserId: number; // ← 追加
        }>(`/api/trades/${tradeId}/messages`, {
          withCredentials: true,
        });

        setSidebarTrades(data.sidebarTrades);
        setTradeInfo({
          id: data.trade.id,
          is_complete: data.trade.is_complete,
          purchaseUserId: data.trade.purchase_user_id,
        });
        setItem(data.item);
        setTradePartner(data.tradePartner);
        setMessages(data.messages);
        setShowRatingModal(data.trade.is_complete && data.showModal);

        // 2) フロントではここから currentUserId をセット
        setCurrentUserId(data.currentUserId);
      } catch (err) {
        console.error("チャット画面取得エラー:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchAll();
  }, [tradeId]);

  if (loading) return <p>読み込み中…</p>;

  if (!tradeInfo || !item || !tradePartner) {
    return <p>チャットデータを取得できませんでした。</p>;
  }

  const isSeller = tradeInfo.purchaseUserId !== currentUserId;
  const canComplete =
    !tradeInfo.is_complete && currentUserId === tradeInfo.purchaseUserId;

  // 取引完了ボタンハンドラ
  const handleComplete = async () => {
    try {
      await axios.post(`/api/trades/${tradeInfo.id}/complete`, null, {
        withCredentials: true,
      });
      setTradeInfo((t) => t && { ...t, is_complete: true });
      setShowRatingModal(true);
    } catch (err: any) {
      alert(err.response?.data?.message || "取引完了に失敗しました");
    }
  };

  // メッセージ送信ハンドラ
  const handleSend = async (e: FormEvent) => {
    e.preventDefault();
    if (!newMessage.trim() && !newImage) return;

    const form = new FormData();
    form.append("message", newMessage);
    if (newImage) form.append("image", newImage);

    try {
      const res = await axios.post<{ message: Message }>(
        `/api/trades/${tradeInfo.id}/messages`,
        form,
        {
          withCredentials: true,
          headers: { "Content-Type": "multipart/form-data" },
        }
      );
      setMessages((ms) => [...ms, res.data.message]);
      setNewMessage("");
      setNewImage(null);
      localStorage.removeItem("tradeMessageDraft");
    } catch (err: any) {
      console.error("メッセージ送信エラー:", err);
      alert(err.response?.data?.message || "送信に失敗しました");
    }
  };

  // 画像選択
  const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
    setNewImage(e.target.files?.[0] ?? null);
  };

  // 自動リサイズ＋ローカルストレージ
  const handleMessageChange = (e: ChangeEvent<HTMLTextAreaElement>) => {
    setNewMessage(e.target.value);
    localStorage.setItem("tradeMessageDraft", e.target.value);
  };

  return (
    <div className="trade-container">
      {/* サイドバー */}
      <aside className="trade-sidebar">
        <h2 className="trade-sidebar__heading">その他の取引</h2>
        <ul className="trade-sidebar__list">
          {sidebarTrades.map((st) => (
            <li key={st.id} className="trade-sidebar__list--item">
              <a
                className="trade-sidebar__list--link"
                href={`/trades/${st.id}/messages`}
              >
                {st.itemName}
              </a>
            </li>
          ))}
        </ul>
      </aside>

      {/* メイン */}
      <main className="trade-main">
        {/* ヘッダー */}
        <div className="trade-main__header">
          <div className="trade-partner">
            <img
              className="trade-partner__image"
              src={tradePartner.thumbnail_url || "/images/default-profile.png"}
              alt="ユーザー画像"
            />
            <h2 className="trade-partner__heading">
              「{tradePartner.name}」さんとの取引
            </h2>
          </div>
          {tradeInfo.is_complete ? (
            <p className="trade-main__complete-message">取引は完了しています</p>
          ) : (
            canComplete && (
              <button
                className="trade-main__complete-button"
                onClick={handleComplete}
              >
                取引を完了する
              </button>
            )
          )}
        </div>

        {/* 商品情報 */}
        <div className="item-info">
          <div className="item-info__left">
            <img
              className="item-info__image"
              src={item.image_url}
              alt="商品画像"
            />
          </div>
          <div className="item-info__right">
            <h3 className="item-info__heading">{item.name}</h3>
            <p className="item-info__price">¥ {item.price}</p>
          </div>
        </div>

        {/* メッセージ一覧 */}
        <div className="chat-messages">
          {messages.map((msg) => {
            const isMine = msg.user.id === currentUserId;
            return (
              <div
                key={msg.id}
                className={`message-container--${isMine ? "mine" : "others"}`}
              >
                {isMine ? (
                  <>
                    <div className="message-header">
                      <span className="message-header__name">
                        {msg.user.name}
                      </span>
                      <img
                        className="message-header__image"
                        src={
                          msg.user.thumbnail_url ||
                          "/images/default-profile.png"
                        }
                        alt="ユーザー画像"
                      />
                    </div>
                    <div className="message-body--mine">
                      <textarea
                        className="message-container__message"
                        rows={1}
                        readOnly
                        value={msg.message}
                      />
                      {msg.image_url && (
                        <img
                          className="message-container__image--mine"
                          src={msg.image_url}
                          alt="添付画像"
                        />
                      )}
                    </div>
                    {!tradeInfo.is_complete && (
                      <div className="message-actions">
                        {/* 編集・削除ボタンの実装は省略 */}
                      </div>
                    )}
                  </>
                ) : (
                  <>
                    <div className="message-header">
                      <img
                        className="message-header__image"
                        src={
                          msg.user.thumbnail_url ||
                          "/images/default-profile.png"
                        }
                        alt="ユーザー画像"
                      />
                      <span className="message-header__name">
                        {msg.user.name}
                      </span>
                    </div>
                    <div className="message-body--others">
                      <textarea
                        className="message-container__message"
                        rows={1}
                        readOnly
                        value={msg.message}
                      />
                    </div>
                    {msg.image_url && (
                      <img
                        className="message-container__image--others"
                        src={msg.image_url}
                        alt="添付画像"
                      />
                    )}
                  </>
                )}
              </div>
            );
          })}
        </div>

        {/* 新規メッセージ送信フォーム */}
        <form className="message-form" onSubmit={handleSend}>
          <div className="message-form__filename-wrapper">
            <span className="message-form__filename">
              {newImage?.name || ""}
            </span>
          </div>
          <div className="message-form__controls">
            <textarea
              ref={textareaRef}
              className="message-form__message"
              placeholder="取引メッセージを記入してください"
              value={newMessage}
              onChange={handleMessageChange}
              disabled={tradeInfo.is_complete}
            />
            <label htmlFor="image" className="message-form__image--label">
              画像を追加
            </label>
            <input
              type="file"
              id="image"
              name="image"
              className="message-form__image--input"
              ref={fileInputRef}
              onChange={handleFileChange}
              disabled={tradeInfo.is_complete}
            />
            <button
              type="submit"
              className={
                tradeInfo.is_complete
                  ? "message-form__button--inactive"
                  : "message-form__button--active"
              }
              disabled={tradeInfo.is_complete}
            >
              <img
                className="message-form__button--image"
                src="/images/input-message.png"
                alt="送信"
              />
            </button>
          </div>
        </form>

        {/* 評価モーダル */}
        {showRatingModal ? (
          <div id="ratingModal" className="modal">
            <div className="modal-content">
              <h2 className="modal-content__heading">取引が完了しました。</h2>
              <p className="modal-content__message">
                今回の取引相手はどうでしたか？
              </p>
              <form
                className="modal-form"
                onSubmit={(e) => {
                  e.preventDefault();
                  // POST /api/trades/{tradeId}/rate を呼び出す実装を入れてください
                }}
              >
                <div className="star-rating">
                  {[5, 4, 3, 2, 1].map((v) => (
                    <React.Fragment key={v}>
                      <input
                        type="radio"
                        name="rating"
                        value={v}
                        id={`star${v}`}
                        checked={rating === v}
                        onChange={() => setRating(v)}
                      />
                      <label htmlFor={`star${v}`}>&#9733;</label>
                    </React.Fragment>
                  ))}
                </div>
                <button type="submit" className="modal-submit-button">
                  送信する
                </button>
              </form>
            </div>
          </div>
        ) : null}
      </main>
    </div>
  );
};

export default TradeChatPage;
