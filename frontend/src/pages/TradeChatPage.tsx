// src/pages/TradeChatPage.tsx
import React, {
  useState,
  useEffect,
  useRef,
  FormEvent,
  ChangeEvent,
  useLayoutEffect,
} from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useTradeChat } from "../hooks/useTradeChat";
import axios from "axios";
import "../styles/trade-chat.css";
import "../styles/rating-modal.css";

interface User {
  id: number;
  name: string;
  thumbnail_url: string | null;
}

interface Message {
  id: number;
  message: string;
  image_url: string | null;
  user: User;
  is_read: boolean;
  created_at: string;
}

function resizeTextarea(ta: HTMLTextAreaElement) {
  ta.style.height = "auto";
  const style = window.getComputedStyle(ta);
  const padding =
    parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
  const border =
    parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
  const h = ta.scrollHeight - padding - border;
  ta.style.height = `${h}px`;
}

const TradeChatPage: React.FC = () => {
  const { tradeId } = useParams<{ tradeId: string }>();
  const {
    loading,
    sidebarTrades,
    tradeInfo,
    item,
    tradePartner,
    messages,
    currentUserId,
    showRatingModal,
    setMessages,
    setTradeInfo,
    setShowRatingModal,
  } = useTradeChat(Number(tradeId));

  const [editingId, setEditingId] = useState<number | null>(null);
  const [editingText, setEditingText] = useState<string>("");
  const [newMessage, setNewMessage] = useState("");
  const [newImage, setNewImage] = useState<File | null>(null);
  const [completing, setCompleting] = useState(false);
  const [rating, setRating] = useState(3);
  const [submittingRating, setSubmittingRating] = useState(false);
  const [sendErrors, setSendErrors] = useState<string[]>([]);
  const [editErrors, setEditErrors] = useState<string[]>([]);

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

  useLayoutEffect(() => {
    document
      .querySelectorAll<HTMLTextAreaElement>(
        ".message-container__message, .message-edit-form__textarea"
      )
      .forEach(resizeTextarea);
  }, [messages, editingId, editingText]);

  // 編集開始
  const handleStartEdit = (msg: Message) => {
    setEditErrors([]);
    setEditingId(msg.id);
    setEditingText(msg.message);
  };

  if (loading) return <p>読み込み中…</p>;

  if (!tradeInfo || !item || !tradePartner) {
    return <p>チャットデータを取得できませんでした。</p>;
  }

  const canComplete =
    !tradeInfo.is_complete && currentUserId === tradeInfo.purchaseUserId;

  // 取引完了ボタンハンドラ
  const handleComplete = async () => {
    if (completing) return;
    setCompleting(true);
    try {
      await axios.post(`/api/trades/${tradeInfo!.id}/complete`, null, {
        withCredentials: true,
      });
      setTradeInfo((t) => t && { ...t, is_complete: true });
      setShowRatingModal(true);
    } catch (err: any) {
      alert(err.response?.data?.message || "取引完了に失敗しました");
    } finally {
      setCompleting(false);
    }
  };

  // 評価送信ハンドラ
  const handleRatingSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (submittingRating) return;
    setSubmittingRating(true);
    try {
      await axios.post(
        `/api/trades/${tradeInfo!.id}/rate`,
        { rating },
        { withCredentials: true }
      );
      // 成功したらモーダルを閉じる
      setShowRatingModal(false);
    } catch (err: any) {
      console.error("評価送信エラー:", err);
      alert(err.response?.data?.message || "評価送信に失敗しました");
    } finally {
      setSubmittingRating(false);
    }
  };

  // メッセージ送信ハンドラ
  const handleSend = async (e: FormEvent) => {
    e.preventDefault();
    // 送信前に前回のエラーをクリア
    setSendErrors([]);
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
        }
      );
      setMessages((ms) => [...ms, res.data.message]);
      setNewMessage("");
      setNewImage(null);
      localStorage.removeItem("tradeMessageDraft");
      setTimeout(() => {
        document
          .querySelectorAll<HTMLTextAreaElement>(".message-container__message")
          .forEach(resizeTextarea);
      }, 0);
    } catch (err: any) {
      if (err.response?.status === 422 && err.response.data.errors) {
        // バックエンドのバリデーションメッセージを配列化してセット
        const errs = err.response.data.errors;
        const msgs = [...(errs.message ?? []), ...(errs.image ?? [])].map(
          (m: any) => String(m)
        );
        setSendErrors(msgs);
      } else {
        console.error("メッセージ送信エラー:", err);
        alert(err.response?.data?.message || "送信に失敗しました");
      }
    }
  };

  // 画像選択
  const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
    setNewImage(e.target.files?.[0] ?? null);
  };

  // 自動リサイズ＋ローカルストレージ
  const handleMessageChange = (e: ChangeEvent<HTMLTextAreaElement>) => {
    const ta = e.currentTarget;
    // ① scrollHeight に合わせて高さをリセット→セット
    ta.style.height = "auto"; // ❷ スタイル取得
    const style = window.getComputedStyle(ta);
    const padding =
      parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
    const border =
      parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);

    // ❸ scrollHeight から padding + border を引いて、本来必要な高さを算出
    const contentHeight = ta.scrollHeight;

    // ❹ 最終的にセット
    ta.style.height = `${contentHeight}px`;
    // ② state にも反映
    setNewMessage(ta.value);
    localStorage.setItem("tradeMessageDraft", ta.value);
  };

  // 編集キャンセル
  const handleCancelEdit = () => {
    setEditingId(null);
    setEditingText("");
  };

  // 編集確定（PATCH）
  const handleSubmitEdit = async (e: FormEvent) => {
    e.preventDefault();
    // 前回の編集エラーをクリア
    setEditErrors([]);
    if (editingId == null) return;
    try {
      await axios.put(
        `/api/trades/${tradeInfo!.id}/messages/${editingId}`,
        { updateMessage: editingText },
        {
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json", // ← 追加
            "X-Requested-With": "XMLHttpRequest",
          },
          withCredentials: true,
        }
      );
      setMessages((ms) =>
        ms.map((m) => (m.id === editingId ? { ...m, message: editingText } : m))
      );
      handleCancelEdit();
    } catch (err: any) {
      if (err.response?.status === 422 && err.response.data.errors) {
        const errs = err.response.data.errors;
        const msgs = (errs.updateMessage ?? []).map((m: any) => String(m));
        setEditErrors(msgs);
      } else {
        alert(err.response?.data?.message || "更新に失敗しました");
        handleCancelEdit();
      }
    }
  };

  // 削除（DELETE）
  const handleDelete = async (id: number) => {
    if (!window.confirm("本当に削除しますか？")) return;
    try {
      await axios.delete(`/api/trades/${tradeInfo!.id}/messages/${id}`, {
        withCredentials: true,
      });
      setMessages((ms) => ms.filter((m) => m.id !== id));
    } catch (err: any) {
      alert(err.response?.data?.message || "削除に失敗しました");
    }
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
                    {/* 編集モード */}
                    {editingId === msg.id ? (
                      <form
                        className="message-edit-form"
                        onSubmit={handleSubmitEdit}
                      >
                        {editErrors.length > 0 && (
                          <ul className="message-form__errors--ul">
                            {editErrors.map((e, i) => (
                              <li key={i} className="message-form__errors--li">
                                {e}
                              </li>
                            ))}
                          </ul>
                        )}
                        <div className="message-edit-form__controls">
                          <textarea
                            className="message-edit-form__textarea"
                            rows={1}
                            value={editingText}
                            onChange={(e) => setEditingText(e.target.value)}
                          />
                          <button
                            type="submit"
                            className="message-edit-form__submit"
                          >
                            <img
                              className="message-edit-form__submit--image"
                              src="/images/input-message.png"
                              alt="送信"
                            />
                          </button>
                          <button
                            type="button"
                            className="message-actions__button"
                            onClick={handleCancelEdit}
                          >
                            キャンセル
                          </button>
                        </div>
                      </form>
                    ) : (
                      /* 通常表示 */
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
                    )}

                    {/* 編集・削除ボタン */}
                    {!tradeInfo!.is_complete && editingId !== msg.id && (
                      <div className="message-actions">
                        <button
                          type="button"
                          className="message-actions__button"
                          onClick={() => handleStartEdit(msg)}
                        >
                          編集
                        </button>
                        <button
                          type="button"
                          className="message-actions__button"
                          onClick={() => handleDelete(msg.id)}
                        >
                          削除
                        </button>
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
          {/* バリデーションエラー表示 */}
          {sendErrors.length > 0 && (
            <ul className="message-form__errors--ul">
              {sendErrors.map((e, i) => (
                <li key={i} className="message-form__errors--li">
                  {e}
                </li>
              ))}
            </ul>
          )}
          <div className="message-form__controls">
            <textarea
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
              <form className="modal-form" onSubmit={handleRatingSubmit}>
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
                <button
                  type="submit"
                  className="modal-submit-button"
                  disabled={submittingRating}
                >
                  {submittingRating ? "送信中…" : "送信する"}
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
