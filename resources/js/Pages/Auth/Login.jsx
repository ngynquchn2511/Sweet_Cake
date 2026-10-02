import '@/bootstrap';      // @ tương đương với resources/js
import '@/../css/app.css';
import { Head, Link, useForm } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import { message } from 'antd';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    // Ô nhập bị lỗi được viền đỏ để người dùng nhận ra ngay trường cần sửa
    const inputClass = (hasError) =>
        'block w-full mt-1 focus:ring focus:ring-opacity-50 rounded-xl transition-all ' +
        (hasError
            ? 'border-red-500 focus:border-red-500 focus:ring-red-100'
            : 'border-gray-200 focus:border-pink-300 focus:ring-pink-100');

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
            onError: () => message.error('Thông tin đăng nhập không chính xác'),
        });
    };

    return (
        // 1. Nền Gradient dùng Tailwind
        <div className="min-h-screen flex flex-col bg-gradient-to-br from-[#fff5f7] via-[#fce7f3] to-[#f3f4f6] relative overflow-hidden">
            <Head title="Đăng nhập" />
            <Navbar />

            {/* 2. Các khối trang trí lơ lửng (Decorative Shapes) */}
            <div className="absolute -top-24 -right-24 w-96 h-96 bg-pink-200/30 rounded-full blur-3xl animate-pulse"></div>
            <div className="absolute -bottom-24 -left-24 w-80 h-80 bg-pink-100/40 rounded-full blur-3xl"></div>

            <main className="flex-grow flex items-center justify-center py-12 px-4 relative z-10">
                {/* 3. Card Đăng nhập với hiệu ứng Glassmorphism nhẹ */}
                <div className="max-w-md w-full bg-white/80 backdrop-blur-md shadow-[0_20px_50px_rgba(219,39,119,0.1)] rounded-[2.5rem] overflow-hidden border border-white p-8 transition-all duration-300 hover:shadow-[0_25px_60px_rgba(219,39,119,0.15)]">
                    
                    <div className="mb-8 text-center">
                        <div className="inline-block p-4 bg-pink-50 rounded-full mb-4">
                            <span className="text-4xl">🍰</span>
                        </div>
                        <h2 className="text-3xl font-extrabold text-gray-800 tracking-tight">Chào mừng!</h2>
                        <p className="text-pink-500 font-medium mt-1">Đăng nhập để đặt bánh ngay</p>
                    </div>

                    {status && (
                        <div className="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-xl text-center">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <InputLabel htmlFor="email" value="Địa chỉ Email" className="text-gray-700 ml-1" />
                            <TextInput
                                id="email"
                                type="email"
                                value={data.email}
                                isFocused={true}
                                autoComplete="username"
                                aria-invalid={Boolean(errors.email)}
                                className={inputClass(errors.email)}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="name@example.com"
                            />
                            <InputError message={errors.email} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password" value="Mật khẩu" className="text-gray-700 ml-1" />
                            <TextInput
                                id="password"
                                type="password"
                                value={data.password}
                                autoComplete="current-password"
                                aria-invalid={Boolean(errors.password)}
                                className={inputClass(errors.password)}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="••••••••"
                            />
                            <InputError message={errors.password} className="mt-1" />
                        </div>

                        <div className="flex items-center justify-between px-1">
                            <label className="flex items-center cursor-pointer">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded text-pink-600 focus:ring-pink-500"
                                />
                                <span className="ms-2 text-sm text-gray-600">Ghi nhớ tôi</span>
                            </label>
                            {canResetPassword && (
                                <Link href={route('password.request')} className="text-sm text-pink-600 font-semibold hover:text-pink-700 transition-colors">
                                    Quên mật khẩu?
                                </Link>
                            )}
                        </div>

                        {/* 4. Nút bấm Sweet Button */}
                        <PrimaryButton 
                            className="w-full justify-center py-4 bg-pink-600 hover:bg-pink-700 active:bg-pink-800 text-white font-bold rounded-2xl shadow-lg shadow-pink-200 transition-all transform hover:-translate-y-0.5 active:translate-y-0" 
                            disabled={processing}
                        >
                            {processing ? (
                                <span className="flex items-center">
                                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    ĐANG XỬ LÝ...
                                </span>
                            ) : 'ĐĂNG NHẬP NGAY'}
                        </PrimaryButton>
                    </form>

                    <div className="mt-8 text-center border-t border-gray-100 pt-6">
                        <p className="text-gray-600 text-sm">
                            Bạn là thành viên mới? 
                            <Link href={route('register')} className="ml-1 text-pink-600 font-bold hover:underline">
                                Đăng ký miễn phí
                            </Link>
                        </p>
                    </div>
                </div>
            </main>
        </div>
    );
}