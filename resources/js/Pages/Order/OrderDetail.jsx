import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link } from '@inertiajs/react';

export default function OrderDetail({ order }) {
    return (
        <MainLayout>
            <Head title={`Chi tiết đơn hàng #${order.order_code}`} />
            
            <div className="max-w-4xl mx-auto py-12 px-4">
                <div className="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-100">
                    {/* Header: Trạng thái đơn hàng */}
                    <div className="bg-pink-50 p-6 border-b border-pink-100 flex justify-between items-center">
                        <div>
                            <h1 className="text-xl font-bold text-gray-800">Đơn hàng #{order.order_code}</h1>
                            <p className="text-sm text-gray-500">Đặt ngày: {new Date(order.created_at).toLocaleString('vi-VN')}</p>
                        </div>
                        <div className="text-right">
                            <span className="bg-pink-600 text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase shadow-sm">
                                {order.order_status}
                            </span>
                        </div>
                    </div>

                    <div className="p-8">
                        {/* 1. Thông tin giao hàng & Thanh toán */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                            <div>
                                <h3 className="text-gray-400 text-xs uppercase font-black tracking-widest mb-3">Thông tin nhận hàng</h3>
                                <div className="text-gray-700 space-y-1">
                                    <p className="font-bold">{order.shipping_address?.receiver_name || 'Khách hàng'}</p>
                                    <p>{order.shipping_address?.phone}</p>
                                    <p className="text-sm">{order.shipping_address?.address_detail}</p>
                                    {order.shipping_address?.note && (
                                        <p className="text-sm italic text-gray-500">Lưu ý: {order.shipping_address.note}</p>
                                    )}
                                </div>
                            </div>
                            <div>
                                <h3 className="text-gray-400 text-xs uppercase font-black tracking-widest mb-3">Thanh toán</h3>
                                <div className="text-gray-700 space-y-1">
                                    <p>Phương thức: <span className="font-medium">{order.payment_method || 'Thanh toán khi nhận hàng (COD)'}</span></p>
                                    <p>Trạng thái: 
                                        <span className={`ml-2 font-bold ${order.payment_status === 'paid' ? 'text-green-600' : 'text-orange-500'}`}>
                                            {order.payment_status === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* 2. Danh sách sản phẩm */}
                        <div className="mb-8">
                            <h3 className="text-gray-400 text-xs uppercase font-black tracking-widest mb-4">Sản phẩm đã đặt</h3>
                            <div className="border rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead className="bg-gray-50 border-b">
                                        <tr>
                                            <th className="p-4 text-xs font-bold text-gray-600">Bánh</th>
                                            <th className="p-4 text-xs font-bold text-gray-600 text-center">Số lượng</th>
                                            <th className="p-4 text-xs font-bold text-gray-600 text-right">Đơn giá</th>
                                            <th className="p-4 text-xs font-bold text-gray-600 text-right">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {order.order_items?.map((item) => (
                                            <tr key={item.id} className="hover:bg-gray-50 transition">
                                                <td className="p-4 font-medium text-gray-800">
                                                    {item.product_name || item.product?.name}
                                                </td>
                                                <td className="p-4 text-center text-gray-600">{item.quantity}</td>
                                                <td className="p-4 text-right text-gray-600">{Number(item.price).toLocaleString()}đ</td>
                                                <td className="p-4 text-right font-bold text-gray-800">
                                                    {(item.price * item.quantity).toLocaleString()}đ
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* 3. Tổng kết chi phí */}
                        <div className="flex justify-end">
                            <div className="w-full md:w-64 space-y-3 pt-4 border-t-2 border-dashed">
                                <div className="flex justify-between text-gray-600">
                                    <span>Tạm tính:</span>
                                    <span>{Number(order.subtotal || (order.total_amount - order.shipping_fee)).toLocaleString()}đ</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>Phí vận chuyển:</span>
                                    <span>{Number(order.shipping_fee || 30000).toLocaleString()}đ</span>
                                </div>
                                {order.discount_amount > 0 && (
                                    <div className="flex justify-between text-green-600 font-medium">
                                        <span>Giảm giá:</span>
                                        <span>-{Number(order.discount_amount).toLocaleString()}đ</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-xl text-pink-600 pt-2">
                                    <span>Tổng cộng:</span>
                                    <span>{Number(order.total_amount || order.total_price).toLocaleString()}đ</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gray-50 p-6 border-t flex justify-between items-center">
                        <Link href={route('dashboard')} className="text-gray-500 hover:text-gray-800 flex items-center gap-2 transition">
                            ← Quay lại đơn hàng của tôi
                        </Link>
                        <button 
                            onClick={() => window.print()}
                            className="text-sm bg-white border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100 transition"
                        >
                            🖨️ In hóa đơn
                        </button>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}