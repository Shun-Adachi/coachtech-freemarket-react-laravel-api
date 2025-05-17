// src/pages/EditProfile.tsx
import React, { useState, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";

import "../styles/common/user-form.css";

interface Errors {
  image?: string;
  name?: string;
  current_post_code?: string;
  current_address?: string;
  current_building?: string;
  general?: string;
}

interface UserShowResponse {
  id: number;
  name: string;
  thumbnail_url: string | null;
  current_post_code: string | null;
  current_address: string | null;
  current_building: string | null;
}

const EditProfile: React.FC = () => {
  const navigate = useNavigate();
  const [preview, setPreview] = useState<string>("/images/default-profile.png");
  const [thumbnailFile, setThumbnailFile] = useState<File | null>(null);
  const [name, setName] = useState<string>("");
  const [currentPostCode, setCurrentPostCode] = useState<string>("");
  const [currentAddress, setCurrentAddress] = useState<string>("");
  const [currentBuilding, setCurrentBuilding] = useState<string>("");
  const [errors, setErrors] = useState<Errors>({});
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    axios
      .get<UserShowResponse>("/api/mypage/profile", {
        headers: { Accept: "application/json" },
      })
      .then((res) => {
        const user = res.data;
        setName(user.name);
        setCurrentPostCode(user.current_post_code || "");
        setCurrentAddress(user.current_address || "");
        setCurrentBuilding(user.current_building || "");
        // 画像プレビュー設定
        if (user.thumbnail_url) {
          const url = user.thumbnail_url.startsWith("http")
            ? user.thumbnail_url
            : `${process.env.REACT_APP_API_BASE_URL}/storage/${user.thumbnail_url}`;
          setPreview(url);
        }
      })
      .catch((err) => console.error("プロフィール取得エラー:", err));
  }, []);

  const handleThumbnailClick = () => fileInputRef.current?.click();
  const handleThumbnailChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!file.type.startsWith("image/")) {
      alert("画像ファイルを選択してください。");
      return;
    }
    const reader = new FileReader();
    reader.onload = (ev) => ev.target && setPreview(ev.target.result as string);
    reader.readAsDataURL(file);
    setThumbnailFile(file);

    setErrors((prev) => ({ ...prev, image: undefined }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    const formData = new FormData();
    if (thumbnailFile) formData.append("image", thumbnailFile);
    formData.append("name", name);
    formData.append("current_post_code", currentPostCode);
    formData.append("current_address", currentAddress);
    formData.append("current_building", currentBuilding);
    formData.append("_method", "PATCH");
    try {
      // update アクションに対応
      await axios.post("/api/mypage/profile", formData, {
        headers: {
          Accept: "application/json",
        },
      });
      navigate("/mypage", {
        state: { message: "プロフィールが更新されました" },
      });
    } catch (err: any) {
      if (err.response?.status === 422) {
        // フォームリクエストのエラーを反映
        console.log("バリデーションエラー:", err.response.data.errors);
        const resp = err.response.data.errors;
        const fieldErrors: Errors = {};
        Object.keys(resp).forEach((key) => {
          fieldErrors[key as keyof Errors] = resp[key][0];
        });
        setErrors(fieldErrors);
      } else {
        setErrors({ general: "更新中にエラーが発生しました。" });
      }
    }
  };

  return (
    <div className="user-form">
      <h1 className="user-form__heading">プロフィール設定</h1>
      <form className="user-form__form" onSubmit={handleSubmit}>
        {errors.general && (
          <p className="user-form__error-message">{errors.general}</p>
        )}

        <div className="user-form__image-group">
          <img
            className="user-form__image-preview"
            src={preview}
            alt="プレビュー"
          />
          <button
            type="button"
            className="user-form__file-upload-button"
            onClick={handleThumbnailClick}
          >
            画像を選択
          </button>
          <input
            type="file"
            accept="image/*"
            className="user-form__hidden-file-input"
            ref={fileInputRef}
            onChange={handleThumbnailChange}
          />
          {errors.image && (
            <p className="user-form__error-message">{errors.image}</p>
          )}
        </div>

        <div className="user-form__group">
          <label htmlFor="name" className="user-form__label">
            ユーザー名
          </label>
          <input
            id="name"
            className="user-form__input"
            value={name}
            onChange={(e) => setName(e.target.value)}
          />
          {errors.name && (
            <p className="user-form__error-message">{errors.name}</p>
          )}
        </div>

        <div className="user-form__group">
          <label htmlFor="current_post_code" className="user-form__label">
            郵便番号
          </label>
          <input
            id="current_post_code"
            className="user-form__input"
            value={currentPostCode}
            onChange={(e) => setCurrentPostCode(e.target.value)}
          />
          {errors.current_post_code && (
            <p className="user-form__error-message">
              {errors.current_post_code}
            </p>
          )}
        </div>

        <div className="user-form__group">
          <label htmlFor="current_address" className="user-form__label">
            住所
          </label>
          <input
            id="current_address"
            className="user-form__input"
            value={currentAddress}
            onChange={(e) => setCurrentAddress(e.target.value)}
          />
          {errors.current_address && (
            <p className="user-form__error-message">{errors.current_address}</p>
          )}
        </div>

        <div className="user-form__group">
          <label htmlFor="current_building" className="user-form__label">
            建物名
          </label>
          <input
            id="current_building"
            className="user-form__input"
            value={currentBuilding}
            onChange={(e) => setCurrentBuilding(e.target.value)}
          />
          {errors.current_building && (
            <p className="user-form__error-message">
              {errors.current_building}
            </p>
          )}
        </div>

        <div className="user-form__group">
          <button type="submit" className="user-form__button">
            更新する
          </button>
        </div>
      </form>
    </div>
  );
};

export default EditProfile;
