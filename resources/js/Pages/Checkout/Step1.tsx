import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { CartItem, User } from '@/types';
import { Head, router } from '@inertiajs/react';

interface Step1Props {
    user: User;
    cartInfo?: { [id: string]: CartItem };
    totalPrice?: number;
}

export default function Step1({ user, cartInfo, totalPrice }: Step1Props) {
    const handlePaymentMethod = (method: 'cash_on_delivery' | 'stripe') => {
        router.post(route('checkout.confirm'), { method });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    決済方法選択
                </h2>
            }
        >
            <Head title="決済方法選択" />

            <div className="py-4">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg p-2">
                        <div className='grid grid-cols-1 gap-4 px-8 py-2'>
                            <p className='text-lg'>
                                {user.name}さん、決済方法を選択してください。
                            </p>
                            <button
                                onClick={() => handlePaymentMethod('cash_on_delivery')}
                                className='bg-indigo-500 text-white font-semibold p-4 w-64 rounded-md hover:bg-indigo-400'
                            >
                                代引き決済
                            </button>
                            <button
                                onClick={() => handlePaymentMethod('stripe')}
                                className='bg-green-500 text-white font-semibold p-4 w-64 rounded-md hover:bg-green-400'
                            >
                                Stripe決済
                            </button>
                            <p className='text-lg'>
                                配送先　〒{user.zipcode ?? '未設定'} {user.address ?? '未設定'}
                            </p>
                            <p className='text-lg'>
                                合計金額： ¥{(totalPrice ?? 0).toLocaleString()}
                            </p>
                            <p className='text-lg'>
                                ご注文商品
                            </p>
                            {cartInfo && Object.keys(cartInfo).length > 0 ? (
                                <div className=''>
                                    <ul>
                                        {Object.entries(cartInfo).map(([id, item]) => (
                                            <li key={id} className='p-2 border-b'>
                                                <div className='flex items-center'>
                                                    <img
                                                        src={`/storage/img/${item.img}`}
                                                        alt={item.name}
                                                        className='w-16 h-16 object-cover mr-4'
                                                    />
                                                    <div>
                                                        <p className='font-bold'>{item.name}</p>
                                                        <p>コード：{item.code}</p>
                                                        <p>価格： {item.price}</p>
                                                        <p>数量： {item.quantity}</p>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ) : null}

                            <button
                                onClick={() => router.visit('/products')}
                                className='bg-gray-500 text-white font-semibold p-4 w-64 rounded-md hover:bg-gray-400 mt-4'
                            >
                                商品一覧に戻る
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
