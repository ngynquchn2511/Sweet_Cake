import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Link, Head } from '@inertiajs/react';

export default function PaymentResult({ success, order, message }) {
    return (
        <MainLayout>
            <Head title="Kết quả thanh toán" />
            <div className="container py-5 text-center" style={{ minHeight: '60vh' }}>
                <div className="card shadow border-0 p-5 mx-auto" style={{ maxWidth: '600px' }}>
                    {success ? (
                        <div className="text-success">
                            <i className="bi bi-check-circle-fill" style={{ fontSize: '5rem' }}></i>
                            <h2 className="fw-bold mt-3">Thanh toán thành công!</h2>
                        </div>
                    ) : (
                        <div className="text-danger">
                            <i className="bi bi-x-circle-fill" style={{ fontSize: '5rem' }}></i>
                            <h2 className="fw-bold mt-3">Thanh toán thất bại</h2>
                        </div>
                    )}

                    <p className="text-muted fs-5 mt-3">{message}</p>
                    
                    {order && (
                        <div className="bg-light p-3 rounded-3 my-4 text-start">
                            <p className="mb-1"><strong>Mã đơn hàng:</strong> {order.order_code}</p>
                            <p className="mb-0"><strong>Tổng tiền:</strong> {new Intl.NumberFormat('vi-VN').format(order.total_price)}đ</p>
                        </div>
                    )}

                    <div className="d-grid gap-2 d-md-block mt-4">
                        <Link href="/" className="btn btn-outline-dark rounded-pill px-4 me-md-2">
                            Về trang chủ
                        </Link>
                        <Link href="/orders" className="btn btn-danger rounded-pill px-4">
                            Lịch sử đơn hàng
                        </Link>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}