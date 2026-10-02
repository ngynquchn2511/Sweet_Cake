// import React from 'react';
// import { Head, Link, router } from '@inertiajs/react';
// import Navbar from '@/Components/Navbar';
// import axios from 'axios';
// import Swal from 'sweetalert2'; // <--- PHẢI CÓ DÒNG NÀY

// export default function Cart({ cartItems, totals }) {
//     const shipping = cartItems.length > 0 ? 30000 : 0;
//     const finalTotal = (totals?.subtotal || 0) + shipping;

//     const updateQuantity = (id, newQuantity) => {
//         if (newQuantity < 1) return;
//         router.put(route('cart.update', id), {
//             quantity: newQuantity
//         }, { preserveScroll: true });
//     };

//     // 1. Xóa sản phẩm dùng SweetAlert2 thay vì confirm()
//     const removeFromCart = (id) => {
//         Swal.fire({
//             title: 'Bạn có chắc chắn?',
//             text: "Sản phẩm này sẽ rời khỏi giỏ hàng của bạn!",
//             icon: 'warning',
//             showCancelButton: true,
//             confirmButtonColor: '#db2777',
//             cancelButtonColor: '#6b7280',
//             confirmButtonText: 'Đúng, xóa nó!',
//             cancelButtonText: 'Giữ lại'
//         }).then((result) => {
//             if (result.isConfirmed) {
//                 router.delete(route('cart.remove', id), { preserveScroll: true });
//             }
//         });
//     };

//     const handleMoMoPayment = async () => {
//         try {
//             const res = await axios.post(route('momo.payment'), { amount: finalTotal });
//             if (res.data?.payUrl) {
//                 window.location.href = res.data.payUrl;
//             } else {
//                 Swal.fire('Lỗi', 'Không khởi tạo được thanh toán MoMo.', 'error');
//             }
//         } catch (err) {
//             Swal.fire('Lỗi', 'Lỗi kết nối MoMo', 'error');
//         }
//     };

//     // 2. Thanh toán COD dùng SweetAlert2 (Xóa bỏ confirm() ở đây)
//     const handleCODPayment = () => {
//         Swal.fire({
//             title: 'Xác nhận đặt hàng?',
//             text: "Bánh sẽ được giao đến bạn và thanh toán khi nhận hàng!",
//             icon: 'question',
//             showCancelButton: true,
//             confirmButtonColor: '#db2777',
//             cancelButtonColor: '#6b7280',
//             confirmButtonText: 'Xác nhận đặt',
//             cancelButtonText: 'Kiểm tra lại',
//         }).then((result) => {
//             if (result.isConfirmed) {
//                 router.post(route('checkout.cod'), {}, {
//                     onStart: () => {
//                         Swal.fire({
//                             title: 'Đang xử lý...',
//                             didOpen: () => { Swal.showLoading() },
//                             allowOutsideClick: false
//                         });
//                     },
//                     onSuccess: () => {
//                         Swal.fire({
//                             icon: 'success',
//                             title: 'Đặt hàng thành công!',
//                             text: 'Đơn hàng của bạn đang được chuẩn bị. Cảm ơn bạn!',
//                             confirmButtonColor: '#db2777',
//                             timer: 3000
//                         });
//                     },
//                     onError: (errors) => {
//                         Swal.fire({
//                             icon: 'error',
//                             title: 'Lỗi',
//                             text: errors.message || 'Vui lòng thử lại!',
//                         });
//                     },
//                     preserveScroll: true,
//                 });
//             }
//         });
//     };

//     return (
//         <div className="min-h-screen flex flex-col bg-gray-50">
//             <Head title="Giỏ hàng của bạn" />
//             <Navbar />
//             <main className="flex-grow py-12">
//                 <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
//                     <h1 className="text-3xl font-bold text-gray-800 mb-8">Giỏ hàng của bạn</h1>

//                     {cartItems.length === 0 ? (
//                         <div className="text-center bg-white p-16 rounded-2xl shadow-sm border">
//                             <div className="text-6xl mb-4">🥐</div>
//                             <h2 className="text-xl font-medium text-gray-600">Giỏ hàng đang trống...</h2>
//                             <Link href={route('products.index')} className="mt-6 inline-block bg-pink-600 text-white px-8 py-3 rounded-full font-bold">
//                                 ĐẾN CỬA HÀNG NGAY
//                             </Link>
//                         </div>
//                     ) : (
//                         <div className="flex flex-col lg:flex-row gap-8">
//                             <div className="lg:w-2/3">
//                                 <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
//                                     <table className="w-full text-left">
//                                         <thead className="bg-gray-50">
//                                             <tr>
//                                                 <th className="p-4 font-bold text-gray-600 uppercase text-xs">Sản phẩm</th>
//                                                 <th className="p-4 font-bold text-gray-600 uppercase text-xs text-center">Số lượng</th>
//                                                 <th className="p-4 font-bold text-gray-600 uppercase text-xs text-right">Thành tiền</th>
//                                             </tr>
//                                         </thead>
//                                         <tbody className="divide-y divide-gray-100">
//                                             {cartItems.map((item) => (
//                                                 <tr key={item.id}>
//                                                     <td className="p-4">
//                                                         <div className="flex items-center gap-4">
//                                                             <img src={item.product.image} className="w-20 h-20 object-cover rounded-lg" />
//                                                             <div>
//                                                                 <h4 className="font-bold text-gray-800">{item.product.name}</h4>
//                                                                 <p className="text-pink-500 font-medium">{Number(item.price).toLocaleString()}đ</p>
//                                                                 <button onClick={() => removeFromCart(item.id)} className="text-xs text-red-400 mt-2">Xóa</button>
//                                                             </div>
//                                                         </div>
//                                                     </td>
//                                                     <td className="p-4 text-center">
//                                                         <div className="inline-flex items-center border rounded-lg overflow-hidden">
//                                                             <button onClick={() => updateQuantity(item.id, item.quantity - 1)} className="px-3 py-1">-</button>
//                                                             <span className="px-4 font-medium">{item.quantity}</span>
//                                                             <button onClick={() => updateQuantity(item.id, item.quantity + 1)} className="px-3 py-1">+</button>
//                                                         </div>
//                                                     </td>
//                                                     <td className="p-4 text-right font-bold">
//                                                         {(item.price * item.quantity).toLocaleString()}đ
//                                                     </td>
//                                                 </tr>
//                                             ))}
//                                         </tbody>
//                                     </table>
//                                 </div>
//                             </div>

//                             <div className="lg:w-1/3">
//                                 <div className="bg-white rounded-2xl shadow-md border p-6 sticky top-24">
//                                     <h3 className="text-xl font-bold mb-6">Tóm tắt đơn hàng</h3>
//                                     <div className="space-y-4 mb-8">
//                                         <div className="flex justify-between">
//                                             <span>Tạm tính:</span>
//                                             <span className="font-medium">{totals.subtotal.toLocaleString()}đ</span>
//                                         </div>
//                                         <div className="flex justify-between">
//                                             <span>Phí vận chuyển:</span>
//                                             <span className="font-medium">{shipping.toLocaleString()}đ</span>
//                                         </div>
//                                         <div className="border-t pt-4 flex justify-between">
//                                             <span className="text-lg font-bold">Tổng cộng:</span>
//                                             <span className="text-2xl font-bold text-pink-600">{finalTotal.toLocaleString()}đ</span>
//                                         </div>
//                                     </div>

//                                     <div className="space-y-3">
//                                         <button onClick={handleMoMoPayment} className="w-full bg-pink-600 text-white py-3 rounded-xl font-bold">
//                                             THANH TOÁN MOMO
//                                         </button>
//                                         <button onClick={handleCODPayment} className="w-full bg-gray-800 text-white py-3 rounded-xl font-bold">
//                                             🚚 THANH TOÁN KHI NHẬN HÀNG
//                                         </button>
//                                     </div>
//                                 </div>
//                             </div>
//                         </div>
//                     )}
//                 </div>
//             </main>
//         </div>
//     );
// }