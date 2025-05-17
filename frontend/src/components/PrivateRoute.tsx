// src/components/PrivateRoute.tsx
import { Navigate, Outlet, useLocation } from "react-router-dom";

export default function PrivateRoute() {
  const token = localStorage.getItem("authToken");
  const location = useLocation();

  if (!token) {
    /* 未ログイン → /login へ。現在の URL を保存しておく */
    return <Navigate to="/login" state={{ from: location }} replace />;
  }
  /* ログイン済みなら配下をそのまま描画 */
  return <Outlet />;
}
