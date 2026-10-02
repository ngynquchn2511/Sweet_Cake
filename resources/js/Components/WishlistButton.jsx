import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import Swal from 'sweetalert2';

// Nút trái tim thêm/xoá sản phẩm khỏi danh sách yêu thích
export default function WishlistButton({ productId, initial = false, className = '' }) {
    const { auth } = usePage().props;
    const [active, setActive] = useState(initial);
    const [busy, setBusy] = useState(false);

    const toggle = async (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (!auth.user) {
            router.get(route('login'));
            return;
        }

        setBusy(true);
        try {
            const res = await axios.post(route('wishlist.toggle'), { product_id: productId });
            setActive(res.data.is_in_wishlist);
            // Làm mới badge số lượng yêu thích trên Navbar
            router.reload({ only: ['wishlistCount'], preserveScroll: true });
            Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 })
                .fire({ icon: 'success', title: res.data.message });
        } catch (err) {
            Swal.fire('Lỗi', err.response?.data?.message || 'Không cập nhật được danh sách yêu thích', 'error');
        } finally {
            setBusy(false);
        }
    };

    return (
        <button
            type="button"
            onClick={toggle}
            disabled={busy}
            aria-pressed={active}
            aria-label={active ? 'Bỏ yêu thích' : 'Thêm vào yêu thích'}
            title={active ? 'Bỏ yêu thích' : 'Thêm vào yêu thích'}
            className={`w-9 h-9 rounded-full bg-white/90 shadow flex items-center justify-center border-0 transition hover:scale-110 ${className}`}
        >
            <i className={`bi ${active ? 'bi-heart-fill text-pink-600' : 'bi-heart text-gray-500'}`}></i>
        </button>
    );
}
