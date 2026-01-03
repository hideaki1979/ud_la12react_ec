import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

interface Props {
    error?: string; // エラーメッセージを受け取る
}

export default function OrderComplete({ error }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    注文完了
                </h2>
            }
        >
            <Head title="注文完了" />

            <div className="py-4">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg px-8 py-4">
                        {/* エラーメッセージがある場合に表示 */}
                        {error ? (
                            <div className='alert alert-danger text-red-600 bg-red-100 p-4 mb-4 rounded'>
                                <strong>エラー：</strong>{error}
                            </div>
                        ) : (
                            <>
                                <h1 className='mb-6 text-2xl font-bold'>
                                    注文が完了しました！
                                </h1>
                                <p className='text-lg my-4'>
                                    このたびはご注文いただき誠にありがとうございます。
                                </p>
                                <p className='text-lg mb-4'>
                                    確認のためメールをお送りしましたのでご確認ください。
                                </p>
                            </>
                        )}
                        <Link
                            href="/products"
                            className='inline-block bg-indigo-500 text-white font-semibold p-4 w-64 rounded-md hover:bg-indigo-400 text-center'
                        >
                            商品一覧に戻る
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
