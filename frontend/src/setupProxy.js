// src/setupProxy.js
const { createProxyMiddleware } = require("http-proxy-middleware");

module.exports = function (app) {
  app.use(
    "/api",
    createProxyMiddleware({
      target: "http://nginx:80", // Docker ネットワーク内の nginx コンテナ名:ポート
      changeOrigin: true,
      ws: false, // HMR 用の WebSocket はプロキシしない
    })
  );
};
