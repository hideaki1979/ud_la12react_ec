import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import DOMPurify from 'dompurify';
import { Order, Paginated } from '@/types';

interface Props {
    orders: Paginated<Order>;
}

export default function OrdersIndex({ orders }: Props) {
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

    const getStatusBadge = (order: Order) => {
        if (order.payment_method === 'stripe') {
            switch (order.stripe_status) {
                case 'completed':
                    return (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            決済完了
                        </span>
                    );
                case 'pending':
                    return (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            決済待ち
                        </span>
                    );
                case 'failed':
                    return (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            決済失敗
                        </span>
                    );
                default:
                    return null;
            }
        }
        return (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                注文確定
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    注文履歴
                </h2>
            }
        >
            <Head title="注文履歴" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {orders.data.length === 0 ? (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <p className="text-gray-500 text-center">
                                注文履歴がありません。
                            </p>
                            <div className="text-center mt-4">
                                <Link
                                    href={route('products.index')}
                                    className="text-indigo-600 hover:text-indigo-500"
                                >
                                    商品一覧へ
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {orders.data.map((order) => (
                                <div
                                    key={order.id}
                                    className="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                                >
                                    <div className="p-6">
                                        <div className="flex justify-between items-start mb-4">
                                            <div>
                                                <p className="text-sm text-gray-500">
                                                    注文番号: #{order.id}
                                                </p>
                                                <p className="text-sm text-gray-500">
                                                    {formatDate(order.created_at)}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                {getStatusBadge(order)}
                                                <p className="mt-2 text-lg font-semibold text-gray-900">
                                                    ¥{order.total_price.toLocaleString()}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="border-t pt-4">
                                            <p className="text-sm text-gray-600 mb-2">
                                                支払方法: {getPaymentMethodLabel(order.payment_method)}
                                            </p>
                                            <div className="flex items-center space-x-4 overflow-x-auto pb-2">
                                                {order.items.slice(0, 4).map((item) => (
                                                    <div
                                                        key={item.id}
                                                        className="flex-shrink-0"
                                                    >
                                                        <img
                                                            src={`/storage/img/${item.product.img}`}
                                                            alt={item.product.name}
                                                            className="w-16 h-16 object-cover rounded"
                                                        />
                                                    </div>
                                                ))}
                                                {order.items.length > 4 && (
                                                    <span className="text-sm text-gray-500">
                                                        +{order.items.length - 4}点
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="mt-4 text-right">
                                            <Link
                                                href={route('orders.show', order.id)}
                                                className="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
                                            >
                                                詳細を見る →
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {/* ページネーション */}
                            <div className="flex justify-center mt-6">
                                {orders.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-4 py-2 mx-1 rounded-md ${
                                            link.active
                                                ? 'bg-indigo-500 text-white'
                                                : 'bg-gray-300 text-gray-700 hover:bg-gray-400'
                                        } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                        dangerouslySetInnerHTML={{
                                            __html: DOMPurify.sanitize(link.label),
                                        }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
