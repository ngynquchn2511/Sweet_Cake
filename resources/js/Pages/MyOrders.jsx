import React from 'react';
import { Head, Link } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';

export default function MyOrders({ orders }) {
    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="Đơn hàng của tôi" />
            <Navbar />
            <div className="max-w-5xl mx-auto py-12 px-4">
                <h1 className="text-3xl font-bold mb-8 text-gray-800">Lịch sử đơn hàng</h1>
                
                <div className="space-y-4">
                    {orders.data.length > 0 ? orders.data.map((order) => (
                        <div key={order.id} className="bg-white p-6 rounded-2xl shadow-sm border flex justify-between items-center">
                            <div>
                                <p className="font-bold text-lg text-gray-800">#{order.order_code}</p>
                                <p className="text-sm text-gray-500">{new Date(order.created_at).toLocaleDateString('vi-VN')}</p>
                                <p className="text-pink-600 font-bold mt-1">{Number(order.total_amount).toLocaleString()}đ</p>
                            </div>
                            <div className="flex items-center gap-4">
                                <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase ${
                                    order.order_status === 'completed' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600'
                                }`}>
                                    {order.order_status}
                                </span>
                                <Link 
                                    href={route('orders.show', order.id)} 
                                    className="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition"
                                >
                                    Xem chi tiết
                                </Link>
                            </div>
                        </div>
                    )) : (
                        <div className="text-center py-20 bg-white rounded-2xl border">
                            <p className="text-gray-400">Bạn chưa có đơn hàng nào.</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}