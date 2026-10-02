import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Card, Button, Radio, Input, Divider, message, Space, Badge } from 'antd';
import { ArrowLeftOutlined, CheckCircleOutlined, TagOutlined, TruckOutlined } from '@ant-design/icons';
import Navbar from '@/Components/Navbar';

export default function Checkout({ cart, addresses, shippingFee = 30000 }) {
    const { data, setData, post, processing, errors } = useForm({
        shipping_address_id: addresses.find(a => a.is_default)?.id || addresses[0]?.id || '',
        payment_method: 'cod',
        promotion_code: '',
        note: '',
    });

    // Tính toán tiền bạc
    const subtotal = cart.items.reduce((sum, item) => sum + (item.quantity * item.price), 0);
    const total = subtotal + shippingFee;

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('orders.store'), {
            onSuccess: () => message.success('Đặt hàng thành công!'),
            onError: (err) => message.error(Object.values(err)[0] || 'Có lỗi xảy ra'),
        });
    };

    return (
        <div className="min-h-screen bg-gray-50 pb-12">
            <Head title="Xác nhận đặt hàng" />
            <Navbar />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Breadcrumb tương tự form cũ */}
                <nav className="text-sm text-gray-500 mb-6">
                    <Link href="/cart" className="hover:text-orange-500">Giỏ hàng</Link>
                    <span className="mx-2"> &gt; </span>
                    <span className="text-orange-500 font-medium">Thanh toán</span>
                </nav>

                <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    {/* CỘT TRÁI: THÔNG TIN GIAO HÀNG & THANH TOÁN */}
                    <div className="lg:col-span-2 space-y-6">
                        
                        {/* 1. Địa chỉ giao hàng */}
                        <Card title={<><TruckOutlined /> Địa chỉ nhận hàng</>} className="shadow-sm border-gray-100">
                            {addresses.length > 0 ? (
                                <Radio.Group 
                                    className="w-full" 
                                    onChange={e => setData('shipping_address_id', e.target.value)} 
                                    value={data.shipping_address_id}
                                >
                                    <div className="space-y-4">
                                        {addresses.map(addr => (
                                            <div key={addr.id} className={`p-4 border rounded-lg transition ${data.shipping_address_id === addr.id ? 'border-orange-500 bg-orange-50' : 'border-gray-200'}`}>
                                                <Radio value={addr.id}>
                                                    <span className="font-semibold text-gray-800">{addr.receiver_name}</span>
                                                    <span className="mx-2 text-gray-400">|</span>
                                                    <span className="text-gray-600">{addr.phone}</span>
                                                    <div className="mt-1 text-sm text-gray-500">
                                                        {addr.address}, {addr.ward}, {addr.district}, {addr.province}
                                                    </div>
                                                </Radio>
                                            </div>
                                        ))}
                                    </div>
                                </Radio.Group>
                            ) : (
                                <div className="text-center py-4">
                                    <p className="text-gray-500 mb-4">Bạn chưa có địa chỉ giao hàng nào.</p>
                                    <Link href={route('shipping-addresses.create')}>
                                        <Button type="dashed" danger>+ Thêm địa chỉ mới</Button>
                                    </Link>
                                </div>
                            )}
                            {errors.shipping_address_id && <p className="text-red-500 text-sm mt-2">{errors.shipping_address_id}</p>}
                        </Card>

                        {/* 2. Phương thức thanh toán */}
                        <Card title={<><CheckCircleOutlined /> Phương thức thanh toán</>} className="shadow-sm border-gray-100">
                            <Radio.Group 
                                onChange={e => setData('payment_method', e.target.value)} 
                                value={data.payment_method}
                                className="grid grid-cols-2 gap-4 w-full"
                            >
                                <Radio.Button value="cod" className="h-12 flex items-center justify-center">Tiền mặt (COD)</Radio.Button>
                                <Radio.Button value="momo" className="h-12 flex items-center justify-center">Ví MoMo</Radio.Button>
                                <Radio.Button value="bank_transfer" className="h-12 flex items-center justify-center">Chuyển khoản</Radio.Button>
                                <Radio.Button value="vnpay" className="h-12 flex items-center justify-center">VNPAY</Radio.Button>
                            </Radio.Group>
                        </Card>

                        {/* 3. Ghi chú đơn hàng */}
                        <Card title="Ghi chú cho cửa hàng" className="shadow-sm border-gray-100">
                            <Input.TextArea 
                                rows={3} 
                                placeholder="Lời nhắn cho shipper hoặc yêu cầu đặc biệt về bánh..."
                                value={data.note}
                                onChange={e => setData('note', e.target.value)}
                            />
                        </Card>
                    </div>

                    {/* CỘT PHẢI: TỔNG KẾT ĐƠN HÀNG */}
                    <div className="lg:col-span-1">
                        <div className="sticky top-8 space-y-6">
                            <Card title="Chi tiết đơn hàng" className="shadow-sm border-gray-100">
                                <div className="max-h-60 overflow-y-auto mb-4 space-y-3 pr-2">
                                    {cart.items.map(item => (
                                        <div key={item.id} className="flex justify-between text-sm">
                                            <span className="text-gray-600">
                                                <Badge count={item.quantity} size="small" offset={[10, 0]} color="#f97316">
                                                    <span className="pr-4">{item.product.name}</span>
                                                </Badge>
                                            </span>
                                            <span className="font-medium">{(item.price * item.quantity).toLocaleString()}đ</span>
                                        </div>
                                    ))}
                                </div>
                                
                                <Divider className="my-3" />
                                
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2">
                                        <Input 
                                            prefix={<TagOutlined className="text-gray-400" />} 
                                            placeholder="Mã giảm giá"
                                            value={data.promotion_code}
                                            onChange={e => setData('promotion_code', e.target.value)}
                                        />
                                        <Button>Áp dụng</Button>
                                    </div>
                                    
                                    <div className="flex justify-between text-gray-600 pt-4">
                                        <span>Tạm tính:</span>
                                        <span>{subtotal.toLocaleString()}đ</span>
                                    </div>
                                    <div className="flex justify-between text-gray-600">
                                        <span>Phí vận chuyển:</span>
                                        <span>{shippingFee.toLocaleString()}đ</span>
                                    </div>
                                    <Divider />
                                    <div className="flex justify-between items-end">
                                        <span className="font-bold text-lg">Tổng cộng:</span>
                                        <span className="text-2xl font-bold text-orange-600">
                                            {total.toLocaleString()}đ
                                        </span>
                                    </div>
                                </div>

                                <Button 
                                    type="primary" 
                                    size="large" 
                                    block 
                                    htmlType="submit"
                                    loading={processing}
                                    className="mt-6 bg-orange-500 hover:bg-orange-600 h-14 text-lg font-bold"
                                    disabled={addresses.length === 0}
                                >
                                    XÁC NHẬN ĐẶT HÀNG
                                </Button>
                                
                                <div className="mt-4 text-center">
                                    <Link href="/cart" className="text-gray-400 hover:text-gray-600 text-sm">
                                        <ArrowLeftOutlined /> Quay lại giỏ hàng
                                    </Link>
                                </div>
                            </Card>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
}