import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Card, Form, Input, Button, Switch, Space, message } from 'antd';
import { ArrowLeftOutlined, SaveOutlined, PlusOutlined } from '@ant-design/icons';
import Navbar from '@/Components/Navbar';

export default function ShippingAddressForm({ address = null }) {
    // Nếu có biến address truyền vào -> Đang ở chế độ Sửa (Edit)
    // Nếu không có -> Đang ở chế độ Thêm (Create)
    const isEdit = !!address;

    const { data, setData, post, put, processing, errors } = useForm({
        receiver_name: address?.receiver_name || '',
        phone: address?.phone || '',
        province: address?.province || '',
        district: address?.district || '',
        ward: address?.ward || '',
        address: address?.address || '',
        note: address?.note || '',
        is_default: address?.is_default === 1 || false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('shipping-addresses.update', address.id), {
                onSuccess: () => {
                    message.success('Cập nhật địa chỉ thành công!');
                },
                onError: () => {
                    message.error('Lỗi khi cập nhật!');
                }
            });
        } else {
            post(route('shipping-addresses.store'), {
                onSuccess: () => {
                    message.success('Thêm địa chỉ mới thành công!');
                },
                onError: () => {
                    message.error('Lỗi khi thêm địa chỉ!');
                }
            });
        }
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title={isEdit ? "Chỉnh sửa địa chỉ" : "Thêm địa chỉ mới"} />
            <Navbar />

            <div className="py-8">
                <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Breadcrumb */}
                    <nav className="text-sm text-gray-500 mb-6">
                        <Link href="/" className="hover:text-orange-500">Trang chủ</Link>
                        <span className="mx-2"> &gt; </span>
                        <Link href="/dashboard" className="hover:text-orange-500">Trang khách hàng</Link>
                        <span className="mx-2"> &gt; </span>
                        <Link href="/shipping-addresses" className="hover:text-orange-500">Sổ địa chỉ</Link>
                        <span className="mx-2"> &gt; </span>
                        <span className="text-orange-500 font-medium">
                            {isEdit ? 'Chỉnh sửa' : 'Thêm mới'}
                        </span>
                    </nav>

                    <div className="bg-white rounded-lg shadow-sm p-8 border border-gray-100">
                        <div className="mb-6 flex items-center gap-3">
                            <Link href="/shipping-addresses">
                                <Button icon={<ArrowLeftOutlined />} type="text" />
                            </Link>
                            <h2 className="text-2xl font-semibold text-gray-800">
                                {isEdit ? 'Chỉnh sửa địa chỉ giao hàng' : 'Thêm địa chỉ giao hàng mới'}
                            </h2>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-6 max-w-xl">
                            {/* Tên người nhận */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Tên người nhận <span className="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    className={`w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 outline-none transition ${
                                        errors.receiver_name ? 'border-red-500' : 'border-gray-300'
                                    }`}
                                    value={data.receiver_name}
                                    onChange={e => setData('receiver_name', e.target.value)}
                                    placeholder="Nhập tên người nhận"
                                />
                                {errors.receiver_name && <p className="text-red-500 text-sm mt-1">{errors.receiver_name}</p>}
                            </div>

                            {/* Số điện thoại */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Số điện thoại <span className="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    className={`w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 outline-none transition ${
                                        errors.phone ? 'border-red-500' : 'border-gray-300'
                                    }`}
                                    value={data.phone}
                                    onChange={e => setData('phone', e.target.value)}
                                    placeholder="Nhập số điện thoại"
                                />
                                {errors.phone && <p className="text-red-500 text-sm mt-1">{errors.phone}</p>}
                            </div>

                            {/* Tỉnh/Thành, Quận/Huyện, Phường/Xã */}
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Tỉnh/Thành <span className="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text"
                                        className={`w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 outline-none transition ${
                                            errors.province ? 'border-red-500' : 'border-gray-300'
                                        }`}
                                        value={data.province}
                                        onChange={e => setData('province', e.target.value)}
                                        placeholder="Tỉnh/Thành"
                                    />
                                    {errors.province && <p className="text-red-500 text-sm mt-1">{errors.province}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Quận/Huyện <span className="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text"
                                        className={`w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 outline-none transition ${
                                            errors.district ? 'border-red-500' : 'border-gray-300'
                                        }`}
                                        value={data.district}
                                        onChange={e => setData('district', e.target.value)}
                                        placeholder="Quận/Huyện"
                                    />
                                    {errors.district && <p className="text-red-500 text-sm mt-1">{errors.district}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Phường/Xã <span className="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text"
                                        className={`w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 outline-none transition ${
                                            errors.ward ? 'border-red-500' : 'border-gray-300'
                                        }`}
                                        value={data.ward}
                                        onChange={e => setData('ward', e.target.value)}
                                        placeholder="Phường/Xã"
                                    />
                                    {errors.ward && <p className="text-red-500 text-sm mt-1">{errors.ward}</p>}
                                </div>
                            </div>

                            {/* Địa chỉ chi tiết */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Địa chỉ chi tiết <span className="text-red-500">*</span>
                                </label>
                                <textarea 
                                    rows="3"
                                    className={`w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 outline-none transition ${
                                        errors.address ? 'border-red-500' : 'border-gray-300'
                                    }`}
                                    value={data.address}
                                    onChange={e => setData('address', e.target.value)}
                                    placeholder="Nhập địa chỉ chi tiết"
                                />
                                {errors.address && <p className="text-red-500 text-sm mt-1">{errors.address}</p>}
                            </div>

                            {/* Ghi chú */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Ghi chú
                                </label>
                                <textarea 
                                    rows="2"
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 outline-none transition"
                                    value={data.note}
                                    onChange={e => setData('note', e.target.value)}
                                    placeholder="Ghi chú thêm (tùy chọn)"
                                />
                            </div>

                            {/* Đặt làm mặc định */}
                            <div className="flex items-center gap-3">
                                <input 
                                    type="checkbox"
                                    className="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500"
                                    checked={data.is_default}
                                    onChange={e => setData('is_default', e.target.checked)}
                                />
                                <label className="text-sm font-medium text-gray-700">
                                    Đặt làm địa chỉ mặc định
                                </label>
                            </div>

                            {/* Buttons */}
                            <div className="flex gap-3 pt-4">
                                <Button 
                                    type="default"
                                    className="flex-1"
                                    onClick={() => window.history.back()}
                                >
                                    Hủy
                                </Button>
                                <Button 
                                    type="primary" 
                                    htmlType="submit" 
                                    loading={processing}
                                    className="flex-1 bg-orange-500 hover:bg-orange-600 border-orange-500"
                                    icon={isEdit ? <SaveOutlined /> : <PlusOutlined />}
                                >
                                    {isEdit ? 'Lưu thay đổi' : 'Thêm địa chỉ'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}