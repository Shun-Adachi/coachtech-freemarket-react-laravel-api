// src/pages/Login.tsx
import React, { useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import axios from "axios";

import "../styles/common/user-form.css";

interface Errors {
  email?: string;
  password?: string;
  code?: string;
  general?: string;
}

interface RequestCodeResponse {
  message: string;
}

interface VerifyCodeResponse {
  user: Record<string, any>;
  token: string;
}

const Login: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState<string>("");
  const [password, setPassword] = useState<string>("");
  const [code, setCode] = useState<string>("");
  const [errors, setErrors] = useState<Errors>({});
  const [codeSent, setCodeSent] = useState<boolean>(false);
  const [loading, setLoading] = useState<boolean>(false);

  /**
   * フロントエンド側バリデーション
   * email 必須/形式, password 必須/8文字以上,
   * codeSent 時に code 必須/6桁数字
   */
  const validate = (): Errors => {
    const errs: Errors = {};
    // email
    if (!email.trim()) {
      errs.email = "メールアドレスを入力してください";
    } else {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(email)) {
        errs.email = "ユーザー名@ドメイン形式で入力してください";
      }
    }
    // password
    if (!password) {
      errs.password = "パスワードを入力してください";
    } else if (password.length < 8) {
      errs.password = "パスワードは8文字以上で入力してください";
    }
    // code
    if (codeSent) {
      if (!code.trim()) {
        errs.code = "認証コードを入力してください";
      } else if (!/^\d{6}$/.test(code)) {
        errs.code = "認証コードは6桁の数字で入力してください";
      }
    }
    return errs;
  };

  // 認証コード送信 (メール送信)
  const handleSendCode = async (e: React.FormEvent) => {
    e.preventDefault();
    const validation = validate();
    // 初回は email/password のみ
    if (validation.email || validation.password) {
      setErrors({ email: validation.email, password: validation.password });
      return;
    }
    setErrors({});
    setLoading(true);
    try {
      const res = await axios.post<RequestCodeResponse>(
        "/api/request-login-code",
        { email, password },
        { headers: { Accept: "application/json" } }
      );
      setCodeSent(true);
      navigate(location.pathname, {
        state: { message: res.data.message },
        replace: true,
      });
    } catch (err: any) {
      // ここで status / data をチェック
      const status = err.response?.status;
      if (status === 404) {
        setErrors({ email: "登録されていないメールアドレスです" });
      } // ② 認証失敗（パスワード不一致など）
      else if (status === 401) {
        // バックエンドから返ってきた message をそのまま general にセット
        setErrors({
          general: err.response?.data?.message ?? "認証に失敗しました。",
        });
      } else if (status === 422) {
        const data = err.response.data;
        if (data.errors) {
          setErrors(data.errors);
        } else if (data.message) {
          setErrors({ general: data.message });
        } else {
          setErrors({ general: "認証コード検証中にエラーが発生しました。" });
        }
      } else {
        setErrors({
          general: "認証コード送信中にエラーが発生しました。",
        });
      }
    } finally {
      setLoading(false);
    }
  };

  // コード検証 & ログイン完了
  const handleVerifyCode = async (e: React.FormEvent) => {
    e.preventDefault();
    const validation = validate();
    // codeSent 時は code + password
    if (validation.password || validation.code) {
      setErrors({ password: validation.password, code: validation.code });
      return;
    }
    setErrors({});
    setLoading(true);
    try {
      const res = await axios.post<VerifyCodeResponse>(
        "/api/verify-login-code",
        { email, password, code },
        { headers: { Accept: "application/json" } }
      );
      const token = res.data.token;
      localStorage.setItem("authToken", token);
      axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
      navigate("/");
    } catch (err: any) {
      const status = err.response?.status;
      if (status === 404)
        setErrors({ email: "登録されていないメールアドレスです" });
      else if (status === 422) {
        const resp = err.response.data;
        setErrors({
          general: resp.message || "認証コード検証中にエラーが発生しました。",
        });
      } else
        setErrors({
          general:
            err.response?.data?.message ||
            "認証コード検証中にエラーが発生しました。",
        });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="user-form">
      <h2 className="user-form__heading">
        {codeSent ? "認証コード入力" : "ログイン"}
      </h2>
      <form
        className="user-form__form"
        onSubmit={codeSent ? handleVerifyCode : handleSendCode}
      >
        {errors.general && (
          <p className="user-form__error-message">{errors.general}</p>
        )}

        <div className="user-form__group">
          <label htmlFor="email" className="user-form__label">
            メールアドレス
          </label>
          <input
            id="email"
            name="email"
            className="user-form__input"
            placeholder="メールアドレスを入力"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            disabled={codeSent || loading}
          />
          {errors.email && (
            <p className="user-form__error-message">{errors.email}</p>
          )}
        </div>

        <div className="user-form__group">
          <label htmlFor="password" className="user-form__label">
            パスワード
          </label>
          <input
            type="password"
            id="password"
            name="password"
            className="user-form__input"
            placeholder="パスワードを入力"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            disabled={loading}
          />
          {errors.password && (
            <p className="user-form__error-message">{errors.password}</p>
          )}
        </div>

        {codeSent && (
          <div className="user-form__group">
            <label htmlFor="code" className="user-form__label">
              認証コード
            </label>
            <input
              type="text"
              id="code"
              name="code"
              className="user-form__input"
              placeholder="6桁の認証コード"
              value={code}
              onChange={(e) => setCode(e.target.value)}
              disabled={loading}
            />
            {errors.code && (
              <p className="user-form__error-message">{errors.code}</p>
            )}
          </div>
        )}

        <div className="user-form__group">
          <button
            type="submit"
            className="user-form__button"
            disabled={loading}
          >
            {codeSent ? "ログイン" : "認証コードを送信"}
          </button>
        </div>

        {!codeSent && (
          <a href="/register" className="user-form__link">
            会員登録はこちら
          </a>
        )}
      </form>
    </div>
  );
};

export default Login;
