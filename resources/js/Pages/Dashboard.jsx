import { Head, usePage, Link } from '@inertiajs/react';
import Navbar from '@/Components/Navbar'; // Đảm bảo đường dẫn này đúng với file Navbar bạn vừa sửa
import { User, Package, Lock, MapPin } from 'lucide-react';

export default function Dashboard() {
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="Trang khách hàng" />

            {/* Sử dụng Navbar của bạn thay cho AuthenticatedLayout */}
            <Navbar />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Breadcrumb */}
                    <nav className="text-sm text-gray-500 mb-6">
                        <Link href="/" className="hover:text-orange-500">Trang chủ</Link>
                        <span className="mx-2"> &gt; </span>
                        <span className="text-orange-500 font-medium">Trang khách hàng</span>
                    </nav>

                    <div className="flex flex-col md:flex-row gap-6">
                        {/* SIDEBAR BÊN TRÁI */}
                        <div className="w-full md:w-1/4 bg-white rounded-lg shadow-sm p-4 h-fit border border-gray-100">
                            <ul className="space-y-1">
                                <li className="bg-orange-50 text-orange-600 rounded-md">
                                    <Link className="flex items-center p-3 font-medium">
                                        <span className="mr-3">👤</span> Thông tin cá nhân
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/my-orders" className="flex items-center p-3 text-gray-600 hover:bg-gray-50 rounded-md transition">
                                        <span className="mr-3">📦</span> Đơn hàng của bạn
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={route('password.edit')}
                                        className="flex items-center p-3 text-gray-600 hover:bg-gray-50 rounded-md transition"
                                    >
                                        <span className="mr-3">🔒</span> Đổi mật khẩu
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/shipping-addresses" className="flex items-center p-3 text-gray-600 hover:bg-gray-50 rounded-md transition">
                                        <span className="mr-3">📍</span> Sổ địa chỉ
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        {/* NỘI DUNG BÊN PHẢI */}
                        <div className="w-full md:w-3/4 bg-white rounded-lg shadow-sm p-8 border border-gray-100">
                            <div className="border-b pb-4 mb-6">
                                <h2 className="text-2xl font-semibold text-gray-800">Thông tin cá nhân</h2>
                                <p className="text-gray-500 text-sm">Quản lý thông tin hồ sơ để bảo mật tài khoản</p>
                            </div>

                            <div className="space-y-6 max-w-xl">
                                <div className="flex items-center border-b border-gray-50 pb-4">
                                    <span className="w-40 font-medium text-gray-500">Họ tên:</span>
                                    <span className="text-gray-900 font-semibold">{user.full_name}</span>
                                </div>
                                <div className="flex items-center border-b border-gray-50 pb-4">
                                    <span className="w-40 font-medium text-gray-500">Email:</span>
                                    <span className="text-gray-900">{user.email}</span>
                                </div>
                                <div className="flex items-center border-b border-gray-50 pb-4">
                                    <span className="w-40 font-medium text-gray-500">Số điện thoại:</span>
                                    <span className="text-gray-900">{user.phone || 'Chưa cập nhật'}</span>
                                </div>

                                <button className="mt-4 bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md transition shadow-sm">
                                    Lưu thay đổi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}