import { BrowserRouter, Routes, Route } from "react-router-dom";
import Header from "./components/Header";
import Login from "./pages/Login";
import ItemList from "./pages/ItemList";
import Register from "./pages/Register";
import ProfilePage from "./pages/ProfilePage";
import EditProfile from "./pages/EditProfile";
import ItemDetail from "./pages/ItemDetail";
import PurchasePage from "./pages/PurchasePage";
import EditAddressPage from "./pages/EditAddressPage";
import CheckoutSuccessPage from "./pages/CheckoutSuccessPage";
import SellPage from "./pages/SellPage";
import TradeChatPage from "./pages/TradeChatPage";
import PrivateRoute from "./components/PrivateRoute";

function App() {
  return (
    <BrowserRouter>
      <Header />
      <main className="content">
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/" element={<ItemList />} />{" "}
          <Route path="/item/:itemId" element={<ItemDetail />} />{" "}
          {/* 認証必須エリア */}
          <Route element={<PrivateRoute />}>
            <Route path="/sell" element={<SellPage />} />
            <Route path="/mypage" element={<ProfilePage />} />
            <Route path="/mypage/profile" element={<EditProfile />} />
            <Route path="/purchase/:itemId" element={<PurchasePage />} />
            <Route
              path="/purchase/address/:itemId"
              element={<EditAddressPage />}
            />
            <Route
              path="/purchase/:itemId/complete"
              element={<CheckoutSuccessPage />}
            />
            <Route
              path="/trades/:tradeId/messages"
              element={<TradeChatPage />}
            />
          </Route>
        </Routes>
      </main>
    </BrowserRouter>
  );
}

export default App;
