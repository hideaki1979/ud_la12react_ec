import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestProductLayout from '@/Layouts/GuestProductLayout';
import { Head, Link, usePage } from '@inertiajs/react';

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

interface ProductsProps {
    products: PaginatedProducts;
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
                                            <div className='col-span-2 mt-4 pointer-events-auto rounded-md bg-indigo-700 px-4 py-2 text-[0.8125rem]/5 text-white hover:bg-indigo-500 text-center font-semibold'>
                                                カートに入れる
                                            </div>
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
                                        dangerouslySetInnerHTML={{ __html: link.label }}   // HTMLエンティティをレンダリング
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
