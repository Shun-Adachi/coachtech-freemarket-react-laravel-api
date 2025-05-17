import React, { useState, useEffect } from "react";
import { useNavigate, useLocation, Link } from "react-router-dom";
import axios from "axios";

import "../styles/layout/header.css";

const Header: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const params = new URLSearchParams(location.search);
  const successMessage = (location.state as any)?.message as string | undefined;
  const [keyword, setKeyword] = useState<string>(params.get("keyword") || "");
  const [tab, setTab] = useState<string>(params.get("tab") || "");
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(
    !!localStorage.getItem("authToken")
  );

  useEffect(() => {
    setIsAuthenticated(!!localStorage.getItem("authToken"));
  }, [location.pathname]);

  useEffect(() => {
    const handleStorage = () => {
      setIsAuthenticated(!!localStorage.getItem("authToken"));
    };
    window.addEventListener("storage", handleStorage);
    return () => window.removeEventListener("storage", handleStorage);
  }, []);

  // ログイン・会員登録ページでは検索・リンクを非表示
  const hideSearchAndLinks = ["/login", "/register"].includes(
    location.pathname
  );

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const searchParams = new URLSearchParams();
    if (keyword) searchParams.set("keyword", keyword);
    if (tab) searchParams.set("tab", tab);
    navigate(`/?${searchParams.toString()}`);
  };

  // ログアウト処理
  const handleLogout = async () => {
    const token = localStorage.getItem("authToken");
    if (token) {
      try {
        await axios.post(
          "/api/logout",
          {},
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          }
        );
      } catch (error: any) {
        // すでに無効なトークンの場合は 401 が返るので無視
        if (error.response?.status !== 401) {
          console.error("ログアウトAPIエラー:", error);
        }
      }
    }
    // 認証情報クリア
    localStorage.removeItem("authToken");
    delete axios.defaults.headers.common["Authorization"];
    setIsAuthenticated(false);
    navigate("/"); // ItemList へ戻る
  };
  return (
    <header>
      <div className="header__content">
        <Link to="/" className="header-logo__link">
          <img
            className="header-logo__image"
            src={`${process.env.PUBLIC_URL}/images/logo.svg`}
            alt="Logo"
          />
        </Link>
        {!hideSearchAndLinks && (
          <div className="header-search">
            <form className="header-search-form__form" onSubmit={handleSearch}>
              <input type="hidden" name="tab" value={tab} />
              <input
                className="header-search-form__input"
                placeholder="なにをお探しですか？"
                name="keyword"
                value={keyword}
                onChange={(e) => setKeyword(e.target.value)}
              />
            </form>
          </div>
        )}
        {!hideSearchAndLinks && (
          <div className="header-link">
            {isAuthenticated ? (
              <button
                type="button"
                className="header-link__link"
                onClick={handleLogout}
              >
                ログアウト
              </button>
            ) : (
              <Link to="/login" className="header-link__link">
                ログイン
              </Link>
            )}
            <Link to="/mypage" className="header-link__link">
              マイページ
            </Link>
            <Link to="/sell" className="header-link__link--sell">
              出品
            </Link>
          </div>
        )}
      </div>
      {/* ヘッダーの直下にアラートメッセージを表示 */}
      {successMessage && <p className="alert__message">{successMessage}</p>}
    </header>
  );
};

export default Header;
