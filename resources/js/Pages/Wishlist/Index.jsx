import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import Swal from 'sweetalert2';
import MainLayout from '@/Layouts/MainLayout';
import { formatCurrency, productImageUrl } from '@/utils/format';

export default function WishlistIndex({ items = [] }) {
    const refresh = () => router.reload({ only: ['items', 'wishlistCount'], preserveScroll: true });

    const removeItem = async (productId) => {
        try {
            await axios.delete(route('wishlist.remove', productId));
            refresh();
        } catch (err) {
            Swal.fire('Lỗi', err.response?.data?.message || 'Không xoá được sản phẩm', 'error');
        }
    };

    const clearAll = () => {
        Swal.fire({
            title: 'Xoá toàn bộ danh sách yêu thích?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#db2777',
            confirmButtonText: 'Xoá tất cả',
            cancelButtonText: 'Huỷ',
        }).then(async (result) => {
            if (!result.isConfirmed) return;
            await axios.delete(route('wishlist.clear'));
            refresh();
        });
    };

    const addToCart = (productId) => {
        router.post(route('cart.add'), { product_id: productId, quantity: 1 }, { preserveScroll: true });
    };

    return (
        <MainLayout>
            <Head title="Sản phẩm yêu thích" />
            <div className="container py-5">
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <h1 className="text-3xl font-black text-gray-900 mb-0">Sản phẩm yêu thích ({items.length})</h1>
                    {items.length > 0 && (
                        <button type="button" onClick={clearAll} className="btn btn-outline-danger rounded-pill px-4">
                            Xoá tất cả
                        </button>
                    )}
                </div>

                {items.length === 0 ? (
                    <div className="text-center bg-white p-5 rounded-2xl border">
                        <i className="bi bi-heart fs-1 text-pink-300 d-block mb-3"></i>
                        <p className="text-gray-500">Bạn chưa có sản phẩm yêu thích nào.</p>
                        <Link href={route('products.index')} className="btn btn-dark rounded-pill px-4">Khám phá cửa hàng</Link>
                    </div>
                ) : (
                    <div className="row g-4">
                        {items.map(({ id, product }) => product && (
                            <div className="col-lg-3 col-md-4 col-sm-6" key={id}>
                                <div className="bg-white rounded-2xl border overflow-hidden h-100 d-flex flex-column">
                                    <Link href={route('products.show', product.slug || product.id)}>
                                        <img src={productImageUrl(product.image)} alt={product.name} className="w-full aspect-[4/3] object-cover" />
                                    </Link>
                                    <div className="p-3 d-flex flex-column flex-grow-1">
                                        <h3 className="text-sm font-semibold mb-1">{product.name}</h3>
                                        <p className="text-pink-600 font-bold mb-3">{formatCurrency(product.discount_price ?? product.price)}</p>
                                        <div className="mt-auto d-flex gap-2">
                                            <button
                                                type="button"
                                                className="btn btn-dark btn-sm rounded-pill flex-grow-1"
                                                disabled={product.status !== 'active' || Number(product.stock) <= 0}
                                                onClick={() => addToCart(product.id)}
                                            >
                                                Thêm vào giỏ
                                            </button>
                                            <button type="button" className="btn btn-outline-danger btn-sm rounded-pill" onClick={() => removeItem(product.id)}>
                                                Xoá
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </MainLayout>
    );
}
