import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Navbar from '@/Components/Navbar';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        full_name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <div className="min-h-screen flex flex-col bg-gray-50">
            <Head title="Đăng ký tài khoản" />

            {/* 1. NAVBAR */}
            <Navbar />

            <main className="flex-grow flex items-center justify-center py-12 px-4">
                <div className="max-w-xl w-full bg-white shadow-xl rounded-2xl overflow-hidden border border-pink-100">
                    <div className="p-8">
                        <div className="mb-8 text-center">
                            {/* Icon Người dùng mới bằng SVG */}
                            <div className="inline-flex items-center justify-center w-16 h-16 bg-pink-50 rounded-full mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <h2 className="text-2xl font-bold text-gray-800">Gia nhập SWEET BAKERY</h2>
                            <p className="text-gray-500 mt-2">Tạo tài khoản để nhận ưu đãi mỗi ngày</p>
                        </div>

                        <form onSubmit={submit} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Họ và tên */}
                            <div className="md:col-span-2">
                                <InputLabel htmlFor="full_name" value="Họ và Tên" />
                                <TextInput
                                    id="full_name"
                                    name="full_name"
                                    value={data.full_name}
                                    className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm"
                                    autoComplete="name"
                                    isFocused={true}
                                    onChange={(e) => setData('full_name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.full_name} className="mt-1" />
                            </div>

                            {/* Email */}
                            <div className="md:col-span-1">
                                <InputLabel htmlFor="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm"
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                <InputError message={errors.email} className="mt-1" />
                            </div>

                            {/* Số điện thoại */}
                            <div className="md:col-span-1">
                                <InputLabel htmlFor="phone" value="Số điện thoại" />
                                <TextInput
                                    id="phone"
                                    type="text"
                                    name="phone"
                                    value={data.phone}
                                    className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm"
                                    onChange={(e) => setData('phone', e.target.value)}
                                    required
                                />
                                <InputError message={errors.phone} className="mt-1" />
                            </div>

                            {/* Mật khẩu */}
                            <div className="md:col-span-1">
                                <InputLabel htmlFor="password" value="Mật khẩu" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm"
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                <InputError message={errors.password} className="mt-1" />
                            </div>

                            {/* Xác nhận mật khẩu */}
                            <div className="md:col-span-1">
                                <InputLabel htmlFor="password_confirmation" value="Xác nhận" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    value={data.password_confirmation}
                                    className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm"
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    required
                                />
                                <InputError message={errors.password_confirmation} className="mt-1" />
                            </div>

                            <div className="md:col-span-2 mt-4">
                                <PrimaryButton 
                                    className="w-full justify-center py-3 bg-pink-600 hover:bg-pink-700 rounded-lg text-base font-bold transition-all shadow-md" 
                                    disabled={processing}
                                >
                                    ĐĂNG KÝ NGAY
                                </PrimaryButton>
                            </div>
                        </form>

                        <div className="mt-8 pt-6 border-t border-gray-100 text-center text-sm text-gray-600">
                            Đã có tài khoản rồi?{' '}
                            <Link href={route('login')} className="text-pink-600 font-bold hover:underline">
                                Đăng nhập tại đây
                            </Link>
                        </div>
                    </div>
                </div>
            </main>

            {/* 3. FOOTER */}
            <footer className="bg-white border-t border-gray-200 py-8">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <p className="text-gray-500 text-sm">
                        &copy; 2026 SWEET BAKERY. Thơm ngon mỗi ngày.
                    </p>
                    <div className="mt-2 space-x-4">
                        <Link href="/" className="text-xs text-gray-400 hover:text-pink-500">Chính sách</Link>
                        <Link href="/" className="text-xs text-gray-400 hover:text-pink-500">Hỗ trợ</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}