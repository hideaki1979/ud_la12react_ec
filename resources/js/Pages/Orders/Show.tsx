import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Order } from '@/types';

interface Props {
    order: Order;
}

export default function OrderShow({ order }: Props) {
    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getPaymentMethodLabel = (method: string) => {
        switch (method) {
            case 'cash_on_delivery':
                return '代金引換';
            case 'stripe':
                return 'クレジットカード';
            default:
                return method;
        }
    };

    const getStatusBadge = () => {
        if (order.payment_method === 'stripe') {
            switch (order.stripe_status) {
                case 'completed':
                    return (
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            決済完了
                        </span>
                    );
                case 'pending':
                    return (
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            決済待ち
                        </span>
                    );
                case 'failed':
                    return (
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            決済失敗
                        </span>
                    );
                default:
                    return null;
            }
        }
        return (
            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                注文確定
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    注文詳細
                </h2>
            }
        >
            <Head title={`注文詳細 #${order.id}`} />

            <div className="py-6">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-4">
                        <Link
                            href={route('orders.index')}
                            className="text-indigo-600 hover:text-indigo-500 text-sm"
                        >
                            ← 注文履歴に戻る
                        </Link>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* 注文ヘッダー */}
                            <div className="border-b pb-4 mb-6">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900">
                                            注文番号: #{order.id}
                                        </h3>
                                        <p className="text-sm text-gray-500 mt-1">
                                            {formatDate(order.created_at)}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        {getStatusBadge()}
                                    </div>
                                </div>
                            </div>

                            {/* 注文情報 */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500 mb-2">
                                        支払方法
                                    </h4>
                                    <p className="text-gray-900">
                                        {getPaymentMethodLabel(order.payment_method)}
                                    </p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500 mb-2">
                                        合計金額
                                    </h4>
                                    <p className="text-2xl font-bold text-gray-900">
                                        ¥{order.total_price.toLocaleString()}
                                    </p>
                                </div>
                            </div>

                            {/* 商品リスト */}
                            <div>
                                <h4 className="text-sm font-medium text-gray-500 mb-4">
                                    注文商品
                                </h4>
                                <div className="space-y-4">
                                    {order.items.map((item) => (
                                        <div
                                            key={item.id}
                                            className="flex items-center border-b pb-4 last:border-b-0 last:pb-0"
                                        >
                                            <img
                                                src={`/storage/img/${item.product.img}`}
                                                alt={item.product.name}
                                                className="w-20 h-20 object-cover rounded"
                                            />
                                            <div className="ml-4 flex-1">
                                                <h5 className="text-gray-900 font-medium">
                                                    {item.product.name}
                                                </h5>
                                                <p className="text-sm text-gray-500">
                                                    商品コード: {item.product.code}
                                                </p>
                                                <p className="text-sm text-gray-500">
                                                    数量: {item.quantity}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-gray-900 font-medium">
                                                    ¥{item.price.toLocaleString()}
                                                </p>
                                                <p className="text-sm text-gray-500">
                                                    小計: ¥{(item.price * item.quantity).toLocaleString()}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* 合計 */}
                            <div className="border-t mt-6 pt-4">
                                <div className="flex justify-between items-center">
                                    <span className="text-lg font-medium text-gray-900">
                                        合計
                                    </span>
                                    <span className="text-2xl font-bold text-gray-900">
                                        ¥{order.total_price.toLocaleString()}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
