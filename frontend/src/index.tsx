import React from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import axios from "axios";
import "./styles/layout/header.css";
import "./styles/common/item-list.css";

axios.defaults.baseURL =
  process.env.REACT_APP_API_BASE_URL || "http://localhost";
axios.defaults.withCredentials = true; // サンクトムCookie運用なら必要

// localStorage に token が残っていれば、Authorization ヘッダを再設定
const token = localStorage.getItem("authToken");
if (token) {
  axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
}

const container = document.getElementById("root");
if (!container) throw new Error("Root container not found");

const root = createRoot(container);
root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
