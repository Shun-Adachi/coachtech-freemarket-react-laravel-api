// src/utils/api.ts
import axios from "axios";

const api = axios.create({
  //baseURL: "", // ← ここを空文字に
  baseURL: process.env.REACT_APP_API_BASE_URL || "http://localhost",
  withCredentials: true,
});

export default api;
