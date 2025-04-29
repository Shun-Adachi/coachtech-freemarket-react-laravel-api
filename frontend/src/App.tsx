import { BrowserRouter, Routes, Route } from "react-router-dom";
import Header from "./components/Header";
import Login from "./pages/Login";
import ItemList from "./pages/ItemList";
import Register from "./pages/Register";
import ProfilePage from "./pages/ProfilePage";

function App() {
  return (
    <BrowserRouter>
      <Header /> {/* ← 全ページで表示 */}
      <main className="content">
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/" element={<ItemList />} />
          <Route path="/mypage" element={<ProfilePage />} />
          {/* 他のルート */}
        </Routes>
      </main>
    </BrowserRouter>
  );
}

export default App;
