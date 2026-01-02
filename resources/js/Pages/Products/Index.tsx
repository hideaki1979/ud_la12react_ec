import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestProductLayout from '@/Layouts/GuestProductLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import DOMPurify from 'dompurify';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';


interface Product {
    id: number;
    name: string;
    code: string;
    price: number;
    img: string;
    active: boolean;
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

interface ProductsProps {
    products: PaginatedProducts;
    successMessage?: string;
    errorMessage?: string;
    cartInfo?: { [id: number]: CartItem };    // idをキーとしたCartItemのオブジェクト
    totalPrice?: number;
}

export default function Products({ products, successMessage, errorMessage, cartInfo, totalPrice }: ProductsProps) {
    const { auth } = usePage().props;
    const [showModal, setShowModal] = useState(false);
    const Layout = auth.user ? AuthenticatedLayout : GuestProductLayout;
    const form = useForm({});

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

    return (
        <Layout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Products
                </h2>
            }
        >
            <Head title="Products" />

            <div className="py-4">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg p-2">
                        {/* メッセージの表示 */}
                        {successMessage && (
                            <div className='bg-green-100 border border-green-400 text-green-800 p-4 rounded m-2'>
                                {successMessage}
                            </div>
                        )}
                        {errorMessage && (
                            <div className='bg-red-100 border border-red-400 text-red-800 p-4 rounded m-2'>
                                {errorMessage}
                            </div>
                        )}
                        <div className="p-4 text-gray-900">
                            <span className='mr-6'>商品一覧</span>
                            <PrimaryButton onClick={openModal}>
                                カート
                            </PrimaryButton>
                        </div>
                    </div>

                    <div className='m-4 max-w-7xl mx-auto sm:px-6 lg:px-8'>
                        <div className='bg-white overflow-hidden shadow-sm sm:rounded-lg p-2'>
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
                                            <div className='text-3xl'>¥{product.price}</div>
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

                            {/* ページネーションリンクの追加 */}
                            <div className='flex justify-center mt-4'>
                                {products.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}  // urlがnullの場合は無効なリンクとして扱う
                                        className={`px-4 py-2 mx-1 rounded-md ${link.active
                                            ? 'bg-indigo-500 text-white'
                                            : 'bg-gray-300 text-gray-700 hover:bg-gray-400'
                                            } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                        dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(link.label) }}   // HTMLエンティティをレンダリング
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={showModal} onClose={closeModal}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        カートの中身
                    </h2>

                    {/* カートの中身をUIに表示する例 */}
                    <p className='text-lg'>
                        合計金額： ¥{totalPrice?.toLocaleString()}
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
                                                <p>価格：{item.price}円</p>
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
