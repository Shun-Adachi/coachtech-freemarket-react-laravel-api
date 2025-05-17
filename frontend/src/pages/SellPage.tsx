// src/pages/SellPage.tsx
import React, { useEffect, useState, useRef } from "react";
import axios from "axios";
import "../styles/sell.css";
import { useNavigate } from "react-router-dom";

// 型定義
type Category = { id: number; name: string };
type Condition = { id: number; name: string };

type CreateRes = {
  user: { id: number; name: string; thumbnail_url: string | null };
  categories: Category[];
  conditions: Condition[];
};

type ValidationErrors = Record<string, string[]>;

type StoreRes = {
  item: { id: number };
};

const SellPage: React.FC = () => {
  const navigate = useNavigate();
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [categories, setCategories] = useState<Category[]>([]);
  const [conditions, setConditions] = useState<Condition[]>([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});

  const [imageFile, setImageFile] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | undefined>(undefined);

  const [form, setForm] = useState({
    categories: [] as number[],
    condition: "",
    name: "",
    description: "",
    price: "",
  });

  // 初期データ取得
  useEffect(() => {
    (async () => {
      try {
        // ジェネリクスでレスポンスタイプを指定
        const { data } = await axios.get<CreateRes>("/api/sell", {
          withCredentials: true,
        });
        setCategories(data.categories);
        setConditions(data.conditions);
      } catch (err) {
        console.error("出品フォーム取得エラー:", err);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const handleFileButtonClick = () => {
    fileInputRef.current?.click();
  };

  // 画像選択時のハンドラ
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      // ここでアップロード用ステートにもセットする
      setImageFile(file);

      const url = URL.createObjectURL(file);
      setPreviewUrl(url);
    } else {
      setPreviewUrl(undefined);
    }
  };

  const handleCheckboxChange = (id: number) => {
    setForm((prev) => {
      const cats = prev.categories.includes(id)
        ? prev.categories.filter((c) => c !== id)
        : [...prev.categories, id];
      return { ...prev, categories: cats };
    });
  };

  const handleChange = (
    e: React.ChangeEvent<
      HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
    >
  ) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});
    const formData = new FormData();
    if (imageFile) formData.append("image", imageFile);
    form.categories.forEach((c) => formData.append("categories[]", `${c}`));
    formData.append("condition_id", form.condition);
    formData.append("name", form.name);
    formData.append("description", form.description);
    formData.append("price", form.price);
    console.log("送信前 condition:", form.condition);
    Array.from(formData.entries()).forEach(([key, value]) => {
      console.log(key, value);
    });
    try {
      // POST もジェネリクスで型指定
      const { data } = await axios.post<StoreRes>("/api/sell", formData, {
        withCredentials: true,
        headers: { "Content-Type": "multipart/form-data" },
      });
      // 出品後はマイページへ遷移
      navigate("/mypage");
    } catch (err: any) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        console.error("出品エラー:", err);
        alert("出品に失敗しました。");
      }
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) return <p>読み込み中…</p>;

  return (
    <div className="sell-form">
      <h1 className="sell-form__main-heading">商品の出品</h1>
      <form className="sell-form__form" onSubmit={handleSubmit}>
        {/* 商品画像 */}
        <div className="sell-form__group">
          <label className="sell-form__label" htmlFor="image">
            商品画像
          </label>
          <p className="sell-form__error-message">{errors.image?.[0]}</p>
          <div className="sell-form__image-upload-container">
            <button
              type="button"
              className="sell-form__file-upload-button"
              onClick={handleFileButtonClick}
              disabled={submitting}
            >
              画像を選択する
            </button>
            <input
              ref={fileInputRef}
              className="sell-form__hidden-file-input"
              type="file"
              name="image"
              accept=".jpg,.jpeg,.png"
              style={{ display: "none" }}
              onChange={handleFileChange}
            />
            {previewUrl && (
              <img
                className="sell-form__image-preview"
                src={previewUrl}
                alt="プレビュー"
              />
            )}
          </div>
        </div>

        {/* 商品の詳細 */}
        <h2 className="sell-form__sub-heading">商品の詳細</h2>

        {/* カテゴリ */}
        <div className="sell-form__group">
          <label className="sell-form__label">カテゴリー</label>
          <p className="sell-form__error-message">{errors.categories?.[0]}</p>
          <div className="sell-form__label-container">
            {categories.map((cat) => (
              <React.Fragment key={cat.id}>
                <input
                  className="sell-form__checkbox"
                  type="checkbox"
                  id={`checkbox${cat.id}`}
                  checked={form.categories.includes(cat.id)}
                  onChange={() => handleCheckboxChange(cat.id)}
                  disabled={submitting}
                />
                <label
                  className="sell-form__category-label"
                  htmlFor={`checkbox${cat.id}`}
                >
                  {cat.name}
                </label>
              </React.Fragment>
            ))}
          </div>
        </div>

        {/* 商品の状態 */}
        <div className="sell-form__group">
          <label className="sell-form__label" htmlFor="condition">
            商品の状態
          </label>
          <p className="sell-form__error-message">{errors.condition?.[0]}</p>
          <select
            className="sell-form__select"
            name="condition"
            id="condition"
            value={form.condition}
            onChange={handleChange}
            disabled={submitting}
          >
            <option value="">選択してください</option>
            {conditions.map((cond) => (
              <option key={cond.id} value={cond.id}>
                {cond.name}
              </option>
            ))}
          </select>
        </div>

        {/* 商品名 */}
        <h2 className="sell-form__sub-heading">商品名と説明</h2>
        <div className="sell-form__group">
          <label className="sell-form__label" htmlFor="name">
            商品名
          </label>
          <p className="sell-form__error-message">{errors.name?.[0]}</p>
          <input
            className="sell-form__input"
            type="text"
            name="name"
            id="name"
            value={form.name}
            onChange={handleChange}
            disabled={submitting}
          />
        </div>

        {/* 商品説明 */}
        <div className="sell-form__group">
          <label className="sell-form__label" htmlFor="description">
            商品の説明
          </label>
          <p className="sell-form__error-message">{errors.description?.[0]}</p>
          <textarea
            className="sell-form__textarea"
            name="description"
            id="description"
            value={form.description}
            onChange={handleChange}
            disabled={submitting}
          />
        </div>

        {/* 販売価格 */}
        <div className="sell-form__group">
          <label className="sell-form__label" htmlFor="price">
            販売価格
          </label>
          <p className="sell-form__error-message">{errors.price?.[0]}</p>
          <div className="sell-form__price-group">
            <label className="sell-form__label--yen-mark" htmlFor="price">
              ¥
            </label>
            <input
              className="sell-form__input--price"
              type="number"
              min="50"
              name="price"
              id="price"
              value={form.price}
              onChange={handleChange}
              disabled={submitting}
            />
          </div>
        </div>

        {/* 出品ボタン */}
        <div className="sell-form__group">
          <button
            type="submit"
            className="sell-form__button"
            disabled={submitting}
          >
            {submitting ? "出品中…" : "出品する"}
          </button>
        </div>
      </form>
    </div>
  );
};

export default SellPage;
