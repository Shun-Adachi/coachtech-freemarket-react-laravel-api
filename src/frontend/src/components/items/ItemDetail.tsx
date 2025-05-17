import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

interface Item {
  id: number;
  name: string;
  description: string;
  price: number;
  image_path: string;
  user: {
    name: string;
  };
  condition: {
    name: string;
  };
}

interface Comment {
  id: number;
  comment: string;
  user: {
    name: string;
  };
  created_at: string;
}

interface Category {
  id: number;
  name: string;
}

const ItemDetail: React.FC = () => {
  const { itemId } = useParams<{ itemId: string }>();
  const [item, setItem] = useState<Item | null>(null);
  const [comments, setComments] = useState<Comment[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [isFavorite, setIsFavorite] = useState(false);
  const [favoritesCount, setFavoritesCount] = useState(0);
  const [newComment, setNewComment] = useState('');

  useEffect(() => {
    const fetchItemDetail = async () => {
      try {
        const response = await fetch(`/api/items/${itemId}`);
        const data = await response.json();
        setItem(data.item);
        setComments(data.comments);
        setCategories(data.itemCategories);
        setIsFavorite(data.isFavorite);
        setFavoritesCount(data.favoritesCount);
      } catch (error) {
        console.error('Error fetching item detail:', error);
      }
    };

    fetchItemDetail();
  }, [itemId]);

  const handleFavorite = async () => {
    try {
      const response = await fetch(`/api/items/${itemId}/favorite`, {
        method: 'POST',
      });
      const data = await response.json();
      setIsFavorite(!isFavorite);
      setFavoritesCount(data.favoritesCount);
    } catch (error) {
      console.error('Error toggling favorite:', error);
    }
  };

  const handleComment = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const response = await fetch(`/api/items/${itemId}/comment`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ comment: newComment }),
      });
      const data = await response.json();
      setComments([...comments, data.comment]);
      setNewComment('');
    } catch (error) {
      console.error('Error posting comment:', error);
    }
  };

  if (!item) {
    return <div>Loading...</div>;
  }

  return (
    <div className="bg-white">
      <div className="max-w-2xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:max-w-7xl lg:px-8">
        <div className="lg:grid lg:grid-cols-2 lg:gap-x-8 lg:items-start">
          <div>
            <div className="w-full aspect-w-1 aspect-h-1">
              <img
                src={item.image_path}
                alt={item.name}
                className="w-full h-full object-center object-cover rounded-lg"
              />
            </div>
          </div>
          <div className="mt-10 px-4 sm:px-0 sm:mt-16 lg:mt-0">
            <h1 className="text-3xl font-extrabold tracking-tight text-gray-900">
              {item.name}
            </h1>
            <div className="mt-3">
              <h2 className="sr-only">商品情報</h2>
              <p className="text-3xl text-gray-900">¥{item.price.toLocaleString()}</p>
            </div>
            <div className="mt-6">
              <h3 className="sr-only">説明</h3>
              <div className="text-base text-gray-700 space-y-6">
                <p>{item.description}</p>
              </div>
            </div>
            <div className="mt-6">
              <div className="flex items-center">
                <button
                  type="button"
                  onClick={handleFavorite}
                  className={`flex items-center justify-center rounded-md border border-transparent px-8 py-3 text-base font-medium ${
                    isFavorite
                      ? 'bg-red-100 text-red-700 hover:bg-red-200'
                      : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'
                  }`}
                >
                  {isFavorite ? 'お気に入り解除' : 'お気に入り'}
                  <span className="ml-2">({favoritesCount})</span>
                </button>
              </div>
            </div>
            <div className="mt-8">
              <h3 className="text-sm font-medium text-gray-900">出品者</h3>
              <div className="mt-1">
                <p className="text-sm text-gray-500">{item.user.name}</p>
              </div>
            </div>
            <div className="mt-8">
              <h3 className="text-sm font-medium text-gray-900">商品の状態</h3>
              <div className="mt-1">
                <p className="text-sm text-gray-500">{item.condition.name}</p>
              </div>
            </div>
            <div className="mt-8">
              <h3 className="text-sm font-medium text-gray-900">カテゴリー</h3>
              <div className="mt-1">
                {categories.map((category) => (
                  <span
                    key={category.id}
                    className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-2"
                  >
                    {category.name}
                  </span>
                ))}
              </div>
            </div>
          </div>
        </div>
        <div className="mt-16">
          <h2 className="text-lg font-medium text-gray-900">コメント</h2>
          <div className="mt-4">
            {comments.map((comment) => (
              <div key={comment.id} className="py-4 border-b border-gray-200">
                <div className="flex items-center">
                  <p className="text-sm font-medium text-gray-900">
                    {comment.user.name}
                  </p>
                  <p className="ml-2 text-sm text-gray-500">
                    {new Date(comment.created_at).toLocaleString()}
                  </p>
                </div>
                <p className="mt-1 text-sm text-gray-700">{comment.comment}</p>
              </div>
            ))}
          </div>
          <form onSubmit={handleComment} className="mt-4">
            <div>
              <label htmlFor="comment" className="sr-only">
                コメント
              </label>
              <textarea
                id="comment"
                name="comment"
                rows={3}
                value={newComment}
                onChange={(e) => setNewComment(e.target.value)}
                className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                placeholder="コメントを入力"
              />
            </div>
            <div className="mt-3">
              <button
                type="submit"
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                コメントを送信
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default ItemDetail;