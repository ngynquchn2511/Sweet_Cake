import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import axios from 'axios';
import Swal from 'sweetalert2';

export default function Cart({ cartItems, totals, syncNotice }) {
    // Phí vận chuyển cố định
    const shipping = cartItems.length > 0 ? 30000 : 0;
    const formatCurrency = (value) => Math.round(Number(value) || 0).toLocaleString('vi-VN');

    // Mã khuyến mãi: kiểm tra trước, gửi kèm khi đặt hàng COD/MoMo
    const [promoInput, setPromoInput] = useState('');
    const [promo, setPromo] = useState(null); // { code, discount }
    const [promoMessage, setPromoMessage] = useState(null);
    const discount = promo?.discount || 0;
    const finalTotal = Math.max(0, (totals?.subtotal || 0) + shipping - discount);

    const applyPromotion = async () => {
        if (!promoInput.trim()) return;
        try {
            const res = await axios.post(route('checkout.check-promotion'), { code: promoInput.trim() });
            if (res.data.success) {
                setPromo({ code: promoInput.trim().toUpperCase(), discount: Number(res.data.discount_amount) || 0 });
                setPromoMessage({ ok: true, text: res.data.message });
            } else {
                setPromo(null);
                setPromoMessage({ ok: false, text: res.data.message });
            }
        } catch (err) {
            setPromo(null);
            setPromoMessage({ ok: false, text: err.response?.data?.message || 'Không kiểm tra được mã khuyến mãi' });
        }
    };

    const removePromotion = () => {
        setPromo(null);
        setPromoInput('');
        setPromoMessage(null);
    };

    // 1. Cập nhật số lượng
    const updateQuantity = (id, newQuantity) => {
        if (newQuantity < 1) return;
        router.put(route('cart.update', id), {
            quantity: newQuantity
        }, { preserveScroll: true });
    };

    // 2. Xóa sản phẩm (Sử dụng SweetAlert2)
    const removeFromCart = (id) => {
        Swal.fire({
            title: 'Bạn chắc chứ?',
            text: "Sản phẩm sẽ bị xóa khỏi giỏ hàng của bạn!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#db2777',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Vâng, xóa nó!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('cart.remove', id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire({
                            title: 'Đã xóa!',
                            text: 'Sản phẩm đã rời khỏi giỏ hàng.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    };

    // Xoá toàn bộ giỏ hàng
    const clearCart = () => {
        Swal.fire({
            title: 'Xoá toàn bộ giỏ hàng?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#db2777',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Xoá tất cả',
            cancelButtonText: 'Hủy',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('cart.clear'), { preserveScroll: true });
            }
        });
    };

    // 3. Thanh toán MoMo: server tạo đơn hàng từ giỏ rồi trả về payUrl của MoMo
    const handleMoMoPayment = async () => {
        try {
            const res = await axios.post(route('momo.payment'), { promotion_code: promo?.code }, {
                headers: { Accept: 'application/json' },
            });
            if (res.data?.payUrl) {
                window.location.href = res.data.payUrl;
            } else {
                Swal.fire('Lỗi', 'Không khởi tạo được thanh toán MoMo.', 'error');
            }
        } catch (err) {
            Swal.fire('Lỗi', err.response?.data?.message || 'Lỗi kết nối máy chủ MoMo', 'error');
            router.reload({ only: ['cartItems', 'totals', 'cartCount'] });
        }
    };

    // 4. Thanh toán COD (Đã thay confirm() bằng Swal.fire)
    const handleCODPayment = (event) => {
        event.preventDefault();
        event.stopPropagation();

        Swal.fire({
            title: 'Xác nhận đặt hàng?',
            text: "Bánh sẽ được giao đến bạn và thanh toán khi nhận hàng (COD)!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#db2777',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Xác nhận đặt',
            cancelButtonText: 'Kiểm tra lại',
        }).then((result) => {
            if (result.isConfirmed) {
                router.post(route('checkout.cod'), { promotion_code: promo?.code }, {
                    onStart: () => {
                        Swal.fire({
                            title: 'Đang xử lý...',
                            didOpen: () => { Swal.showLoading() },
                            allowOutsideClick: false
                        });
                    },
                    onSuccess: () => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Đặt hàng thành công!',
                            text: 'Cảm ơn bạn đã ủng hộ Sweet Bakery!',
                            confirmButtonColor: '#db2777',
                            timer: 3000
                        });
                    },
                    onError: (errors) => {
                        const missingShippingAddress = Boolean(errors.shipping_address_id);
                        const alertOptions = {
                            icon: 'error',
                            title: 'Rất tiếc...',
                            text: Object.values(errors)[0] || 'Có lỗi xảy ra khi đặt hàng.',
                            confirmButtonColor: '#db2777',
                        };

                        if (missingShippingAddress) {
                            Object.assign(alertOptions, {
                                showCancelButton: true,
                                confirmButtonText: 'Xác nhận',
                                cancelButtonText: 'Đóng',
                                cancelButtonColor: '#6b7280',
                            });
                        }

                        Swal.fire(alertOptions).then((result) => {
                            if (missingShippingAddress && result.isConfirmed) {
                                router.visit(route('shipping-addresses.create'));
                            }
                        });
                    }
                });
            }
        });
    };

    return (
        <div className="min-h-screen flex flex-col bg-gray-50">
            <Head title="Giỏ hàng của bạn" />
            <Navbar />

            <main className="flex-grow py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-gray-800 mb-8">Giỏ hàng của bạn</h1>

                    {syncNotice && (
                        <div className="mb-6 p-4 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm" role="status">
                            {syncNotice}
                        </div>
                    )}

                    {cartItems.length === 0 ? (
                        <div className="text-center bg-white p-16 rounded-2xl shadow-sm border">
                            <div className="text-6xl mb-4">🥐</div>
                            <h2 className="text-xl font-medium text-gray-600">Giỏ hàng đang trống rỗng...</h2>
                            <Link href={route('products.index')} className="mt-6 inline-block bg-pink-600 text-white px-8 py-3 rounded-full font-bold hover:bg-pink-700 transition">
                                ĐẾN CỬA HÀNG NGAY
                            </Link>
                        </div>
                    ) : (
                        <div className="flex flex-col lg:flex-row gap-8">
                            {/* DANH SÁCH SẢN PHẨM */}
                            <div className="lg:w-2/3">
                                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                    <table className="w-full text-left border-collapse">
                                        <thead className="bg-gray-50 border-b">
                                            <tr>
                                                <th className="p-4 font-bold text-gray-600 uppercase text-xs">Sản phẩm</th>
                                                <th className="p-4 font-bold text-gray-600 uppercase text-xs text-center">Số lượng</th>
                                                <th className="p-4 font-bold text-gray-600 uppercase text-xs text-right">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {cartItems.map((item) => (
                                                <tr key={item.id} className="hover:bg-gray-50 transition">
                                                    <td className="p-4">
                                                        <div className="flex items-center gap-4">
                                                            <img src={item.product.image?.startsWith('uploads/') ? `/${item.product.image}` : item.product.image} alt={item.product.name} className="w-20 h-20 object-cover rounded-lg border" />
                                                            <div>
                                                                <h4 className="font-bold text-gray-800">{item.product.name}</h4>
                                                                <p className="text-sm text-pink-500 font-medium">
                                                                    {formatCurrency(item.price)}đ
                                                                </p>
                                                                <button onClick={() => removeFromCart(item.id)} className="text-xs text-red-400 mt-2 hover:underline">Xóa</button>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="p-4 text-center">
                                                        <div className="inline-flex items-center border rounded-lg bg-white overflow-hidden">
                                                            <button onClick={() => updateQuantity(item.id, item.quantity - 1)} className="px-3 py-1 hover:bg-gray-100 border-r" aria-label="Giảm số lượng">-</button>
                                                            <span className="px-4 py-1 font-medium">{item.quantity}</span>
                                                            <button onClick={() => updateQuantity(item.id, Number(item.quantity) + 1)} className="px-3 py-1 hover:bg-gray-100 border-l" aria-label="Tăng số lượng">+</button>
                                                        </div>
                                                    </td>
                                                    <td className="p-4 text-right font-bold text-gray-800">
                                                        {formatCurrency(item.price * item.quantity)}đ
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="flex justify-between items-center mt-6">
                                    <Link href={route('products.index')} className="inline-block text-pink-600 font-medium hover:underline">
                                        ← Tiếp tục mua bánh
                                    </Link>
                                    <button type="button" onClick={clearCart} className="text-sm text-red-500 hover:underline bg-transparent border-0">
                                        Xoá toàn bộ giỏ hàng
                                    </button>
                                </div>
                            </div>

                            {/* TỔNG KẾT ĐƠN HÀNG */}
                            <div className="lg:w-1/3">
                                <div className="bg-white rounded-2xl shadow-md border border-pink-100 p-6 sticky top-24">
                                    <h3 className="text-xl font-bold text-gray-800 mb-6 border-b pb-4">Tóm tắt đơn hàng</h3>

                                    <div className="space-y-4 mb-8">
                                        <div className="flex justify-between text-gray-600">
                                            <span>Tạm tính ({totals.total_items} món):</span>
                                            <span className="font-medium text-gray-900">{formatCurrency(totals.subtotal)}đ</span>
                                        </div>
                                        <div className="flex justify-between text-gray-600">
                                            <span>Phí vận chuyển:</span>
                                            <span className="font-medium text-gray-900">{formatCurrency(shipping)}đ</span>
                                        </div>

                                        <div>
                                            <label htmlFor="promotion_code" className="block text-sm text-gray-600 mb-1">Mã khuyến mãi</label>
                                            {promo ? (
                                                <div className="flex justify-between items-center bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                                                    <span className="font-bold text-green-700">{promo.code}</span>
                                                    <button type="button" onClick={removePromotion} className="text-xs text-red-500 bg-transparent border-0">Bỏ mã</button>
                                                </div>
                                            ) : (
                                                <div className="flex gap-2">
                                                    <input
                                                        id="promotion_code"
                                                        value={promoInput}
                                                        onChange={(e) => setPromoInput(e.target.value)}
                                                        placeholder="Nhập mã giảm giá"
                                                        className="flex-grow border rounded-lg px-3 py-2 text-sm uppercase"
                                                    />
                                                    <button type="button" onClick={applyPromotion} className="px-4 rounded-lg bg-gray-800 text-white text-sm border-0">Áp dụng</button>
                                                </div>
                                            )}
                                            {promoMessage && (
                                                <p className={`text-xs mt-1 mb-0 ${promoMessage.ok ? 'text-green-600' : 'text-red-500'}`}>{promoMessage.text}</p>
                                            )}
                                        </div>

                                        {discount > 0 && (
                                            <div className="flex justify-between text-green-600">
                                                <span>Giảm giá:</span>
                                                <span className="font-medium">-{formatCurrency(discount)}đ</span>
                                            </div>
                                        )}

                                        <div className="border-t pt-4 flex justify-between">
                                            <span className="text-lg font-bold text-gray-800">Tổng cộng:</span>
                                            <span className="text-2xl font-bold text-pink-600">{formatCurrency(finalTotal)}đ</span>
                                        </div>
                                    </div>

                                    <div className="space-y-3">
                                        <button
                                            type="button"
                                            onClick={handleMoMoPayment}
                                            className="w-full bg-pink-600 hover:bg-pink-700 text-white py-3 rounded-xl font-bold shadow-md transition-all flex items-center justify-center gap-2"
                                        >
                                            <span className="bg-white text-pink-600 px-1.5 rounded-md text-[10px] font-black">MoMo</span>
                                            THANH TOÁN MOMO
                                        </button>

                                        <button
                                            type="button"
                                            onClick={handleCODPayment}
                                            className="w-full bg-gray-800 hover:bg-black text-white py-3 rounded-xl font-bold shadow-md transition-all flex items-center justify-center gap-2"
                                        >
                                            🚚 THANH TOÁN KHI NHẬN HÀNG
                                        </button>
                                    </div>

                                    <div className="mt-4 text-center">
                                        <p className="text-xs text-gray-400 italic">Cam kết bánh tươi ngon trong ngày ✨</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </main>

            <footer className="bg-white border-t border-gray-200 py-8">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <p className="text-gray-500 text-sm">&copy; 2026 SWEET BAKERY. Giao bánh tận tâm.</p>
                </div>
            </footer>
        </div>
    );
}
