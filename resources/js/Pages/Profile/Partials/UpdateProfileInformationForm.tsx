import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { FormEventHandler, useState } from 'react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}: {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}) {
    const user = usePage().props.auth.user;
    // useStateを追加して、ローカルエラーを管理
    const [zipcodeError, setZipcodeError] = useState<string>('');


    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            zipcode: user.zipcode,
            address: user.address,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    const handleZipcodeChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const zipcode = e.target.value;
        setData('zipcode', zipcode);
        setZipcodeError('');

        if (/^\d{7}$/.test(zipcode)) {
            try {
                const response = await axios.get('/api/zipcode/search', {
                    params: { zipcode },
                });

                if (response.data.results?.length > 0) {
                    const result = response.data.results[0];
                    setData('address', `${result.address1}${result.address2}${result.address3}`);
                } else {
                    setZipcodeError('住所が見つかりませんでした。');
                }
            } catch (error) {
                console.error('郵便番号検索に失敗しました。', error);
                if (axios.isAxiosError(error) && error.response?.data.message) {
                    setZipcodeError(error.response.data.message);
                } else {
                    setZipcodeError('郵便番号検索に失敗しました');
                }
            }
        }
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Profile Information
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Update your account's profile information and email address.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="name" value="Name" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div className='mt-4'>
                    <InputLabel htmlFor="zipcode" value="Zipcode" />

                    <TextInput
                        id="zipcode"
                        name="zipcode"
                        value={data.zipcode}
                        className="mt-1 block w-full"
                        autoComplete="zipcode"
                        onChange={(e) => handleZipcodeChange(e)}
                        required
                    />

                    <InputError message={zipcodeError || errors.zipcode} className='mt-2' />

                </div>

                <div className='mt-4'>
                    <InputLabel htmlFor="address" value="Address" />

                    <TextInput
                        id="address"
                        name="address"
                        value={data.address}
                        className="mt-1 block w-full"
                        autoComplete="address"
                        onChange={(e) => setData('address', e.target.value)}
                        required
                    />

                    <InputError message={errors.address} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
