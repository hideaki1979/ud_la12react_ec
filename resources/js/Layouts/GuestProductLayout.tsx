
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';


export default function GuestProductLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>
                        </div>
                        <div className="flex items-center gap-x-4">
                            <Link
                                href={route('login')}
                                className="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Log In
                            </Link>
                            <Link
                                href={route('register')}
                                className="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Register
                            </Link>
                        </div>
                    </div>
                </div>

                <div>
                    <div className="space-y-1 pb-3 pt-2"></div>

                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
