import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestProductLayout from '@/Layouts/GuestProductLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import DOMPurify from 'dompurify';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { FormEvent, useMemo, useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import { router } from '@inertiajs/react';

interface Product {
    id: number;
    name: string;
    code: string;
    price: number;
    img: string;
    active: boolean;
}

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedProducts {
    data: Product[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
}

interface CartItem {
    name: string;
    price: number;
    code: string;
    img: string;
    quantity: number;
}

interface ProductFilters {
    search: string;
    min_price: string;
    max_price: string;
    category: string;
    sort: string;
    direction: string;
}

interface ProductsProps {
    products: PaginatedProducts;
    successMessage?: string;
    errorMessage?: string;
    cartInfo?: { [id: number]: CartItem };
    totalPrice?: number;
    categories?: Category[];
    filters?: ProductFilters;
}

function SearchFilter({ filters, categories }: { filters: ProductFilters; categories: Category[] }) {
    const { data, setData, get, processing } = useForm({
        search: filters.search || '',
        min_price: filters.min_price || '',
        max_price: filters.max_price || '',
        category: filters.category || '',
        sort: filters.sort || 'created_at',
        direction: filters.direction || 'desc',
    });

    // 価格範囲バリデーション
    const priceValidationError = useMemo(() => {
        const min = data.min_price ? parseInt(data.min_price, 10) : null;
        const max = data.max_price ? parseInt(data.max_price, 10) : null;

        if (min !== null && max !== null && min > max) {
            return '最低価格は最高価格以下にしてください';
        }
        return null;
    }, [data.min_price, data.max_price]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (priceValidationError) return;
        get(route('products.index'), { preserveState: true });
    };

    const handleReset = () => {
        router.get(route('products.index'));
    };

    return (
        <form onSubmit={handleSubmit} className="mb-6 p-4 bg-gray-100 rounded-lg">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {/* キーワード検索 */}
                <div>
                    <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
                        商品名で検索
                    </label>
                    <input
                        type="text"
                        id="search"
                        value={data.search}
                        onChange={(e) => setData('search', e.target.value)}
                        placeholder="商品名を入力"
                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                {/* カテゴリ */}
                <div>
                    <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-1">
                        カテゴリ
                    </label>
                    <select
                        id="category"
                        value={data.category}
                        onChange={(e) => setData('category', e.target.value)}
                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">すべて</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                </div>

                {/* 価格範囲 */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        価格範囲
                    </label>
                    <div className="flex items-center space-x-2">
                        <input
                            type="number"
                            value={data.min_price}
                            onChange={(e) => setData('min_price', e.target.value)}
                            placeholder="最低"
                            min="0"
                            className={`w-full rounded-md shadow-sm focus:ring-indigo-500 ${
                                priceValidationError
                                    ? 'border-red-500 focus:border-red-500'
                                    : 'border-gray-300 focus:border-indigo-500'
                            }`}
                        />
                        <span className="text-gray-500">〜</span>
                        <input
                            type="number"
                            value={data.max_price}
                            onChange={(e) => setData('max_price', e.target.value)}
                            placeholder="最高"
                            min="0"
                            className={`w-full rounded-md shadow-sm focus:ring-indigo-500 ${
                                priceValidationError
                                    ? 'border-red-500 focus:border-red-500'
                                    : 'border-gray-300 focus:border-indigo-500'
                            }`}
                        />
                    </div>
                    {priceValidationError && (
                        <p className="text-red-500 text-sm mt-1">{priceValidationError}</p>
                    )}
                </div>

                {/* 並び替え */}
                <div>
                    <label htmlFor="sort" className="block text-sm font-medium text-gray-700 mb-1">
                        並び替え
                    </label>
                    <select
                        id="sort"
                        value={`${data.sort}-${data.direction}`}
                        onChange={(e) => {
                            const [sort, direction] = e.target.value.split('-');
                            setData((prev) => ({ ...prev, sort, direction }));
                        }}
                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="created_at-desc">新着順</option>
                        <option value="created_at-asc">古い順</option>
                        <option value="price-asc">価格が安い順</option>
                        <option value="price-desc">価格が高い順</option>
                        <option value="name-asc">名前順 (A-Z)</option>
                        <option value="name-desc">名前順 (Z-A)</option>
                    </select>
                </div>
            </div>

            <div className="mt-4 flex justify-end space-x-4">
                <button
                    type="button"
                    onClick={handleReset}
                    className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                    リセット
                </button>
                <button
                    type="submit"
                    disabled={processing || !!priceValidationError}
                    className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    検索
                </button>
            </div>
        </form>
    );
}

export default function Products({ products, successMessage, errorMessage, cartInfo, totalPrice, categories = [], filters }: ProductsProps) {
    const { auth } = usePage().props;
    const [showModal, setShowModal] = useState(false);
    const Layout = auth.user ? AuthenticatedLayout : GuestProductLayout;
    const form = useForm({});

    const defaultFilters: ProductFilters = {
        search: '',
        min_price: '',
        max_price: '',
        category: '',
        sort: 'created_at',
        direction: 'desc',
    };

    const openModal = () => {
        setShowModal(true);
    }

    const closeModal = () => {
        setShowModal(false);
    }

    const addToCart = (id: number) => {
        form.post(route('products.add', id), {
            onError: () => alert('カートへの追加に失敗しました。'),
        });
    };

    const addCartPlus = (id: number) => {
        form.post(route('products.plus', id), {
            onError: () => alert('数量を増やすことに失敗しました。'),
        });
    };

    const cartMinus = (id: number) => {
        form.post(route('products.minus', id), {
            onError: () => alert('数量を減らすことに失敗しました。'),
        });
    };

    const removeCart = (id: number) => {
        form.post(route('products.remove', id), {
            onError: () => alert('カートからの削除に失敗しました。'),
        });
    };

    const checkout = () => {
        if (auth?.user) {
            router.visit(route('checkout.step1'));
        } else {
            router.visit(route('register'));
        }
    }

    return (
        <Layout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    商品一覧
                </h2>
            }
        >
            <Head title="商品一覧" />

            <div className="py-4">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg p-4">
                        {/* メッセージの表示 */}
                        {successMessage && (
                            <div className='bg-green-100 border border-green-400 text-green-800 p-4 rounded mb-4'>
                                {successMessage}
                            </div>
                        )}
                        {errorMessage && (
                            <div className='bg-red-100 border border-red-400 text-red-800 p-4 rounded mb-4'>
                                {errorMessage}
                            </div>
                        )}

                        {/* 検索フィルター */}
                        <SearchFilter filters={filters || defaultFilters} categories={categories} />

                        <div className="flex justify-between items-center mb-4">
                            <span className="text-gray-600">
                                {products.total}件中 {products.from || 0}〜{products.to || 0}件を表示
                            </span>
                            <PrimaryButton onClick={openModal}>
                                カート ({Object.keys(cartInfo || {}).length})
                            </PrimaryButton>
                        </div>
                    </div>

                    <div className='mt-4'>
                        <div className='bg-white overflow-hidden shadow-sm sm:rounded-lg p-4'>
                            {products.data.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">
                                    条件に一致する商品が見つかりませんでした。
                                </p>
                            ) : (
                                <div className='grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4'>
                                    {products.data.map((product) => {
                                        return (
                                            <div key={product.id} className='items-center shadow-md p-4 grid grid-cols-2 gap-x-4'>
                                                <div>
                                                    <img
                                                        src={`/storage/img/${product.img}`}
                                                        alt={product.name}
                                                        className='w-24 h-24 object-cover mx-auto'
                                                    />
                                                </div>
                                                <div className='text-blue-700 text-2xl'>{product.name}</div>
                                                <div className='text-teal-700'>{product.code}</div>
                                                <div className='text-3xl'>¥{product.price.toLocaleString()}</div>
                                                {!product.active ? (
                                                    <button
                                                        type='button'
                                                        className='col-span-2 mt-4 pointer-events-auto rounded-md bg-indigo-700 px-4 py-2 text-[0.8125rem]/5 text-white hover:bg-indigo-500 text-center font-semibold cursor-pointer'
                                                        onClick={() => addToCart(product.id)}
                                                    >
                                                        カートに入れる
                                                    </button>
                                                ) : (
                                                    <div className="text-red-500 my-2 text-sm font-semibold">
                                                        在庫なし
                                                    </div>
                                                )}
                                            </div>
                                        )
                                    })}
                                </div>
                            )}

                            {/* ページネーションリンクの追加 */}
                            {products.last_page > 1 && (
                                <div className='flex justify-center mt-4'>
                                    {products.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-4 py-2 mx-1 rounded-md ${link.active
                                                ? 'bg-indigo-500 text-white'
                                                : 'bg-gray-300 text-gray-700 hover:bg-gray-400'
                                                } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(link.label) }}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={showModal} onClose={closeModal}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        カートの中身
                    </h2>

                    <p className='text-lg'>
                        合計金額： ¥{(totalPrice ?? 0).toLocaleString()}
                    </p>
                    <p className='text-lg'>
                        ご注文商品
                    </p>
                    {cartInfo && Object.keys(cartInfo).length > 0 ? (
                        <div>
                            <ul>
                                {Object.entries(cartInfo).map(([id, item]) => (
                                    <li key={id} className='p-3 border-b'>
                                        <div className='flex items-center'>
                                            <img
                                                src={`/storage/img/${item.img}`}
                                                alt={item.name}
                                                className='w-16 h-16 object-cover mr-4'
                                            />
                                            <div>
                                                <p className='font-bold'>{item.name}</p>
                                                <p>コード：{item.code}</p>
                                                <p>価格：{item.price.toLocaleString()}円</p>
                                                <p>
                                                    数量：{item.quantity}個
                                                    <button
                                                        type='button'
                                                        onClick={() => addCartPlus(Number(id))}
                                                        className='pointer-events-auto rounded-md bg-indigo-500 px-2 py-1 mx-2 text-[0.8125rem]/5 font-semibold text-white hover:bg-indigo-400 text-center'
                                                    >
                                                        +
                                                    </button>

                                                    {item.quantity > 1 && (
                                                        <button
                                                            type='button'
                                                            onClick={() => cartMinus(Number(id))}
                                                            className='pointer-events-auto rounded-md bg-indigo-500 px-2 py-1 mx-2 text-[0.8125rem]/5 font-semibold text-white hover:bg-indigo-400 text-center'
                                                        >
                                                            -
                                                        </button>
                                                    )}

                                                    <button
                                                        type='button'
                                                        onClick={() => removeCart(Number(id))}
                                                        className="pointer-events-auto rounded-md bg-red-500 px-2 py-2 ml-2 text-[0.8125rem]/5 font-semibold text-white hover:bg-red-400 text-center"
                                                    >
                                                        カート削除
                                                    </button>
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                            <div className='text-center'>
                                <button
                                    onClick={() => checkout()}
                                    className='pointer-events-auto rounded-md bg-indigo-500 p-2 font-semibold text-white hover:bg-indigo-400 w-2/3 text-center'
                                >
                                    決済する
                                </button>
                            </div>
                        </div>
                    ) : (
                        <p className='mt-4 px-4 text-gray-600'>カートは空です。</p>
                    )}

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closeModal}>
                            閉じる
                        </SecondaryButton>
                    </div>
                </div>
            </Modal>
        </Layout>
    );
}
