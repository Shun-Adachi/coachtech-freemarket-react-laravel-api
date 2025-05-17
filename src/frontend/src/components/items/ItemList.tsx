import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../../api/client';

interface Item {
  id: number;
  name: string;
  price: number;
  image_url: string;
  condition: {
    name: string;
  };
  category: {
    name: string;
  };
}

const ItemList: React.FC = () => {
  const [items, setItems] = useState<Item[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchItems = async () => {
      try {
        const response = await client.get<Item[]>('/items');
        setItems(response.data);
        setLoading(false);
      } catch (error) {
        setError('商品の取得に失敗しました');
        setLoading(false);
      }
    };

    fetchItems();
  }, []);

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="text-red-500">{error}</div>
      </div>
    );
  }

  return (
    <div className="bg-gray-100 min-h-screen">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {items.map((item) => (
            <Link
              key={item.id}
              to={`/items/${item.id}`}
              className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300"
            >
              <div className="aspect-w-16 aspect-h-9">
                <img
                  src={item.image_url}
                  alt={item.name}
                  className="object-cover w-full h-48"
                />
              </div>
              <div className="p-4">
                <h3 className="text-lg font-medium text-gray-900 mb-2">
                  {item.name}
                </h3>
                <div className="flex justify-between items-center">
                  <span className="text-lg font-bold text-indigo-600">
                    ¥{item.price.toLocaleString()}
                  </span>
                  <div className="flex space-x-2">
                    <span className="px-2 py-1 text-xs font-semibold text-gray-600 bg-gray-100 rounded">
                      {item.condition.name}
                    </span>
                    <span className="px-2 py-1 text-xs font-semibold text-gray-600 bg-gray-100 rounded">
                      {item.category.name}
                    </span>
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
};

export default ItemList;