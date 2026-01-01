import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestProductLayout from '@/Layouts/GuestProductLayout';
import { Head, usePage } from '@inertiajs/react';

interface Product {
    id: number;
    name: string;
    code: string;
    price: number;
    img: string;
    active: boolean;
}

interface ProductsProps {
    products: Product[];
}

export default function Products({ products }: ProductsProps) {
    const { auth } = usePage().props;
    const Layout = auth.user ? AuthenticatedLayout : GuestProductLayout;
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
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            商品一覧
                        </div>
                    </div>

                    <div className='m-4 max-w-7xl mx-auto sm:px-6 lg:px-8'>
                        <div className='bg-white overflow-hidden shadow-sm sm:rounded-lg p-2'>
                            <div className='grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4'>
                                {products.map((product) => {
                                    return (
                                        <div key={product.id} className='justify-center items-center shadow-md p-4 grid grid-cols-2'>
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
                                            <div className='pointer-events-auto rounded-md bg-indigo-700 px-4 py-2 text-[0.8125rem]/5 text-white hover:bg-indigo-500 text-center'>
                                                カートに入れる
                                            </div>
                                        </div>
                                    )
                                })}
                            </div>
                            {/* <table className='table-auto border border-gray-400 w-10/12 m-4'>
                                <thead>
                                    <tr className='bg-gray-100'>
                                        <th className='px-4 py-2 w-12'>ID</th>
                                        <th className='px-4 py-2 w-48'>商品</th>
                                        <th className='px-4 py-2 w-28'>コード</th>
                                        <th className='px-4 py-2 w-28 text-center'>価格</th>
                                        <th className='px-4 py-2 w-28 text-center'>画像</th>
                                        <th className='px-4 py-2 w-28 text-center'>アクティブ</th>
                                        <th className='px-4 py-2'></th>
                                        <th className='px-4 py-2'></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {products.map((product) => {
                                        return (
                                            <tr key={product.id}>
                                                <td className='border border-gray-400 px-4 py-2 text-center'>{product.id}</td>
                                                <td className='border border-gray-400 px-4 py-2'>{product.name}</td>
                                                <td className='border border-gray-400 px-4 py-2 text-center'>{product.code}</td>
                                                <td className='border border-gray-400 px-4 py-2 text-right'>{product.price}</td>
                                                <td className='border border-gray-400 px-4 py-2 text-right'>
                                                    <img
                                                        src={`/storage/img/${product.img}`}
                                                        alt={product.name}
                                                        className='w-16 h-16 object-cover mx-auto'
                                                    />
                                                </td>
                                                <td className='border border-gray-400 px-4 py-2 text-right'>{product.active ? '在庫無し' : '在庫あり'}</td>
                                                <td className='border border-gray-400 px-4 py-2 text-center'></td>
                                                <td className='border border-gray-400 px-4 py-2 text-center'></td>
                                            </tr>
                                        )
                                    })}
                                </tbody>
                            </table> */}
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
