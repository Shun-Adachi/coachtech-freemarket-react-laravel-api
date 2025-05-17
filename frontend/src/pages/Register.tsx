import React, { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import axios from "axios";

import "../styles/common/user-form.css";

interface Errors {
  name?: string;
  email?: string;
  password?: string;
  password_confirmation?: string;
  general?: string;
}

interface RegisterResponse {
  message: string;
}

const Register: React.FC = () => {
  const navigate = useNavigate();
  const [name, setName] = useState<string>("");
  const [email, setEmail] = useState<string>("");
  const [password, setPassword] = useState<string>("");
  const [passwordConfirmation, setPasswordConfirmation] = useState<string>("");
  const [errors, setErrors] = useState<Errors>({});
  const [loading, setLoading] = useState<boolean>(false);

  // フロント側バリデーション
  const validate = (): Errors => {
    const errs: Errors = {};
    if (!name.trim()) {
      errs.name = "ユーザー名を入力してください";
    }
    if (!email.trim()) {
      errs.email = "メールアドレスを入力してください";
    } else {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(email)) {
        errs.email = "ユーザー名@ドメイン形式で入力してください";
      }
    }
    if (!password) {
      errs.password = "パスワードを入力してください";
    } else if (password.length < 8) {
      errs.password = "パスワードは8文字以上で入力してください";
    }
    if (!passwordConfirmation) {
      errs.password_confirmation = "確認用パスワードを入力してください";
    } else if (passwordConfirmation !== password) {
      errs.password_confirmation = "パスワードが一致しません";
    }
    return errs;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const frontErrors = validate();
    if (Object.keys(frontErrors).length > 0) {
      setErrors(frontErrors);
      return;
    }
    setErrors({});
    setLoading(true);
    try {
      const res = await axios.post<RegisterResponse>(
        "/api/register",
        { name, email, password, password_confirmation: passwordConfirmation },
        { headers: { Accept: "application/json" } }
      );
      // 登録成功後はログインページへ
      navigate("/login", { state: { message: res.data.message } });
    } catch (err: any) {
      const status = err.response?.status;
      if (status === 422 && err.response.data.errors) {
        setErrors(err.response.data.errors);
      } else {
        setErrors({ general: "登録中にエラーが発生しました。" });
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="user-form">
      <h2 className="user-form__heading">会員登録</h2>
      <form className="user-form__form" onSubmit={handleSubmit}>
        {errors.general && (
          <p className="user-form__error-message">{errors.general}</p>
        )}

        <div className="user-form__group">
          <label htmlFor="name" className="user-form__label">
            ユーザー名
          </label>
          <input
            id="name"
            name="name"
            className="user-form__input"
            value={name}
            onChange={(e) => setName(e.target.value)}
            disabled={loading}
          />
          {errors.name && (
            <p className="user-form__error-message">{errors.name}</p>
          )}
        </div>

        <div className="user-form__group">
          <label htmlFor="email" className="user-form__label">
            メールアドレス
          </label>
          <input
            id="email"
            name="email"
            className="user-form__input"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            disabled={loading}
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
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            disabled={loading}
          />
          {errors.password && (
            <p className="user-form__error-message">{errors.password}</p>
          )}
        </div>

        <div className="user-form__group">
          <label htmlFor="password_confirmation" className="user-form__label">
            確認用パスワード
          </label>
          <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            className="user-form__input"
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
            disabled={loading}
          />
          {errors.password_confirmation && (
            <p className="user-form__error-message">
              {errors.password_confirmation}
            </p>
          )}
        </div>

        <div className="user-form__group">
          <button
            type="submit"
            className="user-form__button"
            disabled={loading}
          >
            {loading ? "登録中…" : "登録する"}
          </button>
        </div>

        <Link to="/login" className="user-form__link">
          ログインはこちら
        </Link>
      </form>
    </div>
  );
};

export default Register;
