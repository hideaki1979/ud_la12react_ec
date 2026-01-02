import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

interface CartItem {
    name: string;
    price: number;
    code: string;
    img: string;
    quantity: number;
}

interface User {
    id: number;
    name: string;
    email: string;
    zipcode: string;
    address: string;
}

interface CashOnDeliveryProps {
    user: User; // ログインユーザー情報
    cartInfo?: { [id: string]: CartItem };    // idをキーとしたCartItemのオブジェクト
    totalPrice?: number;    // 合計金額
    selectedPaymentMethodInfo: string;
}

export default function CashOnDelivery({ user, cartInfo, totalPrice, selectedPaymentMethodInfo }: CashOnDeliveryProps) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    ご注文の確認
                </h2>
            }
        >
            <Head title="決済方法選択" />

            <div className="py-4">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg px-8 py-4">
                        <p className='mb-6 text-lg'>
                            内容をご確認の上、「注文を確定する」ボタンをクリックしてください。
                        </p>
                        <button
                            onClick={() => { }}
                            className='bg-indigo-500 text-white font-semibold p-4 w-64 rounded-md hover:bg-indigo-400'
                        >
                            注文を確定する
                        </button>
                        <p className='text-lg my-4'>
                            決済方法：
                            {selectedPaymentMethodInfo === 'cash_on_delivery'
                                ? '代引き決済'
                                : 'Stripe決済'}
                        </p>
                        <p className='text-lg'>
                            配送先　〒{user.zipcode} {user.address}
                        </p>
                        <p className='text-lg'>
                            {user.name} 様
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
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
