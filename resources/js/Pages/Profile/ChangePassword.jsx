import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button, message, Input } from 'antd';
import { LockOutlined, SaveOutlined } from '@ant-design/icons';
import Navbar from '@/Components/Navbar';

export default function ChangePassword() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = e => {
        e.preventDefault();
        console.log(data);          // xem data.password và data.password_confirmation
        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => {
                message.success('Đổi mật khẩu thành công!');
                reset();
            },
            onError: () => message.error('Vui lòng kiểm tra lại thông tin!'),
        });
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="Đổi mật khẩu" />
            <Navbar />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row gap-6">
                        
                        {/* SIDEBAR (Copy nguyên từ trang Index Địa chỉ sang) */}
                        <div className="w-full md:w-1/4">
                            <div className="bg-white rounded-lg shadow-sm p-4 border border-gray-100 sticky top-24">
                                <ul className="space-y-1">
                                    <li>
                                        <Link href="/dashboard" className="flex items-center p-3 text-gray-600 hover:bg-gray-50 rounded-md transition">
                                            <span className="mr-3">👤</span> Thông tin cá nhân
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href="/my-orders" className="flex items-center p-3 text-gray-600 hover:bg-gray-50 rounded-md transition">
                                            <span className="mr-3">📦</span> Đơn hàng của bạn
                                        </Link>
                                    </li>
                                    <li className="bg-orange-50 text-orange-600 rounded-md">
                                        <Link href="/change-password" className="flex items-center p-3 font-medium">
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
                        </div>

                        {/* FORM ĐỔI MẬT KHẨU */}
                        <div className="w-full md:w-3/4">
                            <div className="bg-white rounded-lg shadow-sm p-8 border border-gray-100">
                                <div className="mb-6 border-b pb-4">
                                    <h2 className="text-2xl font-semibold text-gray-800">Đổi mật khẩu</h2>
                                    <p className="text-gray-500 text-sm">Để bảo mật tài khoản, vui lòng không chia sẻ mật khẩu cho người khác</p>
                                </div>

                                <form onSubmit={submit} className="max-w-md space-y-6">
                                    {/* Mật khẩu hiện tại */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Mật khẩu hiện tại</label>
                                        <Input.Password 
                                            prefix={<LockOutlined className="text-gray-400" />}
                                            size="large"
                                            value={data.current_password}
                                            onChange={e => setData('current_password', e.target.value)}
                                            status={errors.current_password ? 'error' : ''}
                                        />
                                        {errors.current_password && <p className="text-red-500 text-xs mt-1">{errors.current_password}</p>}
                                    </div>

                                    {/* Mật khẩu mới */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                                        <Input.Password 
                                            prefix={<LockOutlined className="text-gray-400" />}
                                            size="large"
                                            value={data.password}
                                            onChange={e => setData('password', e.target.value)}
                                            status={errors.password ? 'error' : ''}
                                        />
                                        {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
                                    </div>

                                    {/* Xác nhận mật khẩu mới */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
                                        <Input.Password 
                                            prefix={<LockOutlined className="text-gray-400" />}
                                            size="large"
                                            value={data.password_confirmation}
                                            onChange={e => setData('password_confirmation', e.target.value)}
                                            status={errors.password_confirmation ? 'error' : ''}
                                        />
                                        {errors.password_confirmation && <p className="text-red-500 text-xs mt-1">{errors.password_confirmation}</p>}
                                    </div>

                                    <Button 
                                        type="primary" 
                                        htmlType="submit" 
                                        loading={processing}
                                        className="bg-orange-500 hover:bg-orange-600 border-orange-500 h-11 px-8"
                                        icon={<SaveOutlined />}
                                    >
                                        Cập nhật mật khẩu
                                    </Button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}