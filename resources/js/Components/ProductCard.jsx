import React from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import WishlistButton from '@/Components/WishlistButton';
import { productImageUrl } from '@/utils/format';

export default function ProductCard({ product }) {
    const { auth } = usePage().props;

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' })
            .format(value).replace('₫', 'đ');
    };

    const handleAddToCart = () => {
        if (!auth.user) {
            Swal.fire({
                title: 'Chào bạn!',
                text: 'Hãy đăng nhập để thêm bánh vào giỏ nhé',
                icon: 'info',
                confirmButtonColor: '#db2777'
            }).then(() => router.get(route('login')));
            return;
        }

        router.post(route('cart.add'), {
            product_id: product.id,
            quantity: 1
        }, {
            preserveScroll: true,
            onSuccess: () => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                });
                Toast.fire({
                    icon: 'success',
                    title: `Đã thêm ${product.name}`
                });
            }
        });
    };

    return (
        <div className="modern-card group border-0 bg-white rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-xl">
            {/* Image Wrapper - bấm vào để xem chi tiết sản phẩm */}
            <div className="relative overflow-hidden aspect-[4/3]">
                <Link href={route('products.show', product.slug || product.id)} aria-label={`Xem chi tiết ${product.name}`}>
                    <img
                        src={productImageUrl(product.image)}
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        alt={product.name}
                    />
                </Link>

                <WishlistButton productId={product.id} initial={Boolean(product.is_in_wishlist)} className="absolute top-3 right-3" />
                
                {product.discount_price && (
                    <div className="absolute top-3 left-3 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">
                        Sale
                    </div>
                )}
            </div>

            {/* Content */}
            <div className="p-4 flex flex-col items-center">
                <span className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">
                    {product.category_name}
                </span>
                
                <h3 className="text-sm font-semibold text-gray-800 mb-2 h-10 line-clamp-2 text-center">
                    <Link href={route('products.show', product.slug || product.id)} className="text-gray-800 no-underline hover:text-pink-600">
                        {product.name}
                    </Link>
                </h3>

                <div className="flex items-center gap-2 mb-4">
                    {product.discount_price ? (
                        <>
                            <span className="text-xs text-gray-400 line-through">
                                {formatCurrency(product.price)}
                            </span>
                            <span className="text-pink-600 font-bold">
                                {formatCurrency(product.discount_price)}
                            </span>
                        </>
                    ) : (
                        <span className="text-gray-900 font-bold">{formatCurrency(product.price)}</span>
                    )}
                </div>

                <button
                    onClick={handleAddToCart}
                    className="w-full bg-gray-900 text-white text-xs py-2.5 rounded-full font-bold transition-all hover:bg-pink-600 active:scale-95 flex items-center justify-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>THÊM VÀO GIỎ HÀNG</span>
                </button>
            </div>
        </div>
    );
}