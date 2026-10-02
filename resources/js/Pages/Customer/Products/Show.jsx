import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import MainLayout from '@/Layouts/MainLayout';
import ProductCard from '@/Components/ProductCard';
import WishlistButton from '@/Components/WishlistButton';
import { formatCurrency, formatDateTime, productImageUrl } from '@/utils/format';

function Stars({ value }) {
    const rounded = Math.round(Number(value) || 0);
    return (
        <span className="text-yellow-400" aria-label={`${value} trên 5 sao`}>
            {[1, 2, 3, 4, 5].map((i) => (
                <i key={i} className={`bi ${i <= rounded ? 'bi-star-fill' : 'bi-star'} me-1`}></i>
            ))}
        </span>
    );
}

export default function Show({ product, relatedProducts = [], isInWishlist = false }) {
    const { auth } = usePage().props;
    const [quantity, setQuantity] = useState(1);
    const stock = Number(product.stock) || 0;
    const reviews = product.reviews || [];

    const addToCart = () => {
        if (!auth.user) {
            router.get(route('login'));
            return;
        }

        router.post(route('cart.add'), { product_id: product.id, quantity }, {
            preserveScroll: true,
            onSuccess: (page) => {
                const error = page.props.flash?.error;
                Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 })
                    .fire({ icon: error ? 'error' : 'success', title: error || `Đã thêm ${product.name} vào giỏ` });
            },
        });
    };

    return (
        <MainLayout>
            <Head title={product.name} />

            <div className="container py-5">
                <nav className="text-sm text-gray-500 mb-4">
                    <Link href="/" className="text-gray-500 no-underline hover:text-pink-600">Trang chủ</Link>
                    <span className="mx-2">/</span>
                    <Link href={route('products.index')} className="text-gray-500 no-underline hover:text-pink-600">Cửa hàng</Link>
                    <span className="mx-2">/</span>
                    <span className="text-gray-800">{product.name}</span>
                </nav>

                <div className="row g-4 g-lg-5">
                    <div className="col-md-6">
                        <div className="relative rounded-2xl overflow-hidden border bg-white">
                            <img src={productImageUrl(product.image)} alt={product.name} className="w-full aspect-square object-cover" />
                            <WishlistButton productId={product.id} initial={isInWishlist} className="absolute top-4 right-4" />
                        </div>
                    </div>

                    <div className="col-md-6">
                        <span className="text-xs uppercase tracking-widest text-pink-600 font-bold">{product.category?.name}</span>
                        <h1 className="text-3xl font-black text-gray-900 mt-2 mb-3">{product.name}</h1>

                        <div className="d-flex align-items-center gap-2 mb-4 text-sm text-gray-600">
                            <Stars value={product.avg_rating} />
                            <span>{Number(product.avg_rating || 0).toFixed(1)}/5</span>
                            <span>·</span>
                            <span>{product.review_count || 0} đánh giá</span>
                            <span>·</span>
                            <span>{product.view_count || 0} lượt xem</span>
                        </div>

                        <div className="mb-4">
                            {product.discount_price ? (
                                <>
                                    <span className="text-3xl font-bold text-pink-600 me-3">{formatCurrency(product.discount_price)}</span>
                                    <span className="text-lg text-gray-400 line-through">{formatCurrency(product.price)}</span>
                                </>
                            ) : (
                                <span className="text-3xl font-bold text-gray-900">{formatCurrency(product.price)}</span>
                            )}
                        </div>

                        {product.description && (
                            <p className="text-gray-600 leading-relaxed mb-4" style={{ whiteSpace: 'pre-line' }}>{product.description}</p>
                        )}

                        <p className={`text-sm mb-4 ${stock > 0 ? 'text-green-600' : 'text-red-500'}`}>
                            {stock > 0 ? `Còn ${stock} ${product.unit || 'sản phẩm'}` : 'Tạm hết hàng'}
                        </p>

                        <div className="d-flex align-items-center gap-3">
                            <div className="inline-flex items-center border rounded-lg bg-white overflow-hidden">
                                <button type="button" className="px-3 py-2 border-0 bg-white hover:bg-gray-100" onClick={() => setQuantity((q) => Math.max(1, q - 1))} aria-label="Giảm số lượng">-</button>
                                <span className="px-4 font-medium" data-testid="quantity">{quantity}</span>
                                <button type="button" className="px-3 py-2 border-0 bg-white hover:bg-gray-100" onClick={() => setQuantity((q) => Math.min(stock, q + 1))} aria-label="Tăng số lượng">+</button>
                            </div>
                            <button
                                type="button"
                                disabled={stock <= 0}
                                onClick={addToCart}
                                data-testid="detail-add-to-cart"
                                className="flex-grow bg-gray-900 hover:bg-pink-600 disabled:bg-gray-300 text-white py-3 rounded-full font-bold border-0 transition"
                            >
                                THÊM VÀO GIỎ HÀNG
                            </button>
                        </div>
                    </div>
                </div>

                <section className="mt-5">
                    <h2 className="text-xl font-black text-gray-900 mb-4">Đánh giá từ khách hàng ({reviews.length})</h2>
                    {reviews.length === 0 ? (
                        <p className="text-gray-500">Chưa có đánh giá nào cho sản phẩm này.</p>
                    ) : (
                        <div className="d-flex flex-column gap-3">
                            {reviews.map((review) => (
                                <div key={review.id} className="bg-white border rounded-2xl p-4">
                                    <div className="d-flex justify-content-between mb-2">
                                        <strong>{review.user?.full_name || 'Khách hàng'}</strong>
                                        <span className="text-xs text-gray-400">{formatDateTime(review.created_at)}</span>
                                    </div>
                                    <Stars value={review.rating} />
                                    {review.comment && <p className="mt-2 mb-0 text-gray-700">{review.comment}</p>}
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                {relatedProducts.length > 0 && (
                    <section className="mt-5">
                        <h2 className="text-xl font-black text-gray-900 mb-4">Sản phẩm cùng loại</h2>
                        <div className="row g-4">
                            {relatedProducts.map((item) => (
                                <div className="col-lg-3 col-md-4 col-sm-6" key={item.id}>
                                    <ProductCard product={item} />
                                </div>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </MainLayout>
    );
}
