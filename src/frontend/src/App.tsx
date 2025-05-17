import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/auth/ProtectedRoute';
import AuthForm from './components/auth/AuthForm';
import ItemList from './components/items/ItemList';
import ItemDetail from './components/items/ItemDetail';
import SellForm from './components/sell/SellForm';

const App: React.FC = () => {
  return (
    <div className="min-h-screen bg-gray-100">
      <AuthProvider>
        <Router>
          <Routes>
            {/* 公開ルート */}
            <Route path="/" element={<ItemList />} />
            <Route path="/items/:itemId" element={<ItemDetail />} />
            <Route path="/login" element={<AuthForm mode="login" />} />
            <Route path="/register" element={<AuthForm mode="register" />} />

            {/* 認証が必要なルート */}
            <Route
              path="/sell"
              element={
                <ProtectedRoute>
                  <SellForm />
                </ProtectedRoute>
              }
            />

            {/* 404ページ */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </Router>
      </AuthProvider>
    </div>
  );
};

export default App;
