import React from 'react';
import { Link } from '@inertiajs/react';

export default function Footer() {
    return (
        <footer className="bg-white border-t border-pink-50 pt-16 pb-8">
            <div className="container px-4">
                <div className="row g-5">
                    {/* Cột 1: Brand & Bio */}
                    <div className="col-lg-4 col-md-6">
                        <Link href="/" className="flex flex-col no-underline hover:no-underline mb-4 group">
                            <span className="text-xl font-black tracking-tighter text-gray-900 group-hover:text-pink-600 transition-colors">
                                SWEET <span className="text-pink-600">BAKERY.</span>
                            </span>
                            <span className="text-[8px] tracking-[0.3em] uppercase text-gray-400 font-bold -mt-1">Handmade with love</span>
                        </Link>
                        <p className="text-gray-500 text-sm leading-relaxed pr-lg-5">
                            Chúng tôi tin rằng mỗi chiếc bánh là một câu chuyện tình yêu. Sử dụng nguyên liệu thủ công để mang đến hương vị hạnh phúc đích thực.
                        </p>
                        <div className="flex gap-4 mt-6">
                            {['facebook', 'instagram', 'tiktok'].map((social) => (
                                <a key={social} href="#" className="w-8 h-8 rounded-full border border-pink-100 flex items-center justify-center text-gray-400 hover:bg-pink-600 hover:text-white hover:border-pink-600 transition-all">
                                    <i className={`bi bi-${social}`}></i>
                                </a>
                            ))}
                        </div>
                    </div>

                    {/* Cột 2: Điều hướng */}
                    <div className="col-lg-2 col-md-6">
                        <h6 className="text-xs font-black uppercase tracking-[0.2em] text-gray-900 mb-4">Cửa hàng</h6>
                        <ul className="list-unstyled flex flex-col gap-2">
                            <li><Link href="/" className="text-gray-500 text-sm no-underline hover:text-pink-600 transition-colors">Trang chủ</Link></li>
                            <li><Link href="/products" className="text-gray-500 text-sm no-underline hover:text-pink-600 transition-colors">Sản phẩm</Link></li>
                            <li><Link href="/about" className="text-gray-500 text-sm no-underline hover:text-pink-600 transition-colors">Về chúng tôi</Link></li>
                        </ul>
                    </div>

                    {/* Cột 3: Hỗ trợ */}
                    <div className="col-lg-2 col-md-6">
                        <h6 className="text-xs font-black uppercase tracking-[0.2em] text-gray-900 mb-4">Hỗ trợ</h6>
                        <ul className="list-unstyled flex flex-col gap-2">
                            <li><Link href="#" className="text-gray-500 text-sm no-underline hover:text-pink-600 transition-colors">Điều khoản</Link></li>
                            <li><Link href="#" className="text-gray-500 text-sm no-underline hover:text-pink-600 transition-colors">Giao nhận</Link></li>
                            <li><Link href="#" className="text-gray-500 text-sm no-underline hover:text-pink-600 transition-colors">Bảo mật</Link></li>
                        </ul>
                    </div>

                    {/* Cột 4: Liên hệ */}
                    <div className="col-lg-4 col-md-6">
                        <h6 className="text-xs font-black uppercase tracking-[0.2em] text-gray-900 mb-4">Liên hệ</h6>
                        <div className="flex flex-col gap-3">
                            <p className="text-gray-500 text-sm mb-0 flex items-center gap-3">
                                <span className="w-8 h-[1px] bg-pink-200"></span>
                                123 Đường Bánh Ngọt, Quận 1, TP.HCM
                            </p>
                            <p className="text-gray-500 text-sm mb-0 flex items-center gap-3">
                                <span className="w-8 h-[1px] bg-pink-200"></span>
                                0123 456 789
                            </p>
                            <p className="text-gray-500 text-sm mb-0 flex items-center gap-3">
                                <span className="w-8 h-[1px] bg-pink-200"></span>
                                hello@sweetbakery.com
                            </p>
                        </div>
                    </div>
                </div>

                <div className="mt-16 pt-8 border-t border-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-0">
                        © 2026 SWEET BAKERY. ALL RIGHTS RESERVED.
                    </p>
                    <div className="flex gap-6">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/2560px-Visa_Inc._logo.svg.png" alt="visa" className="h-3 opacity-30 grayscale hover:grayscale-0 transition-all cursor-pointer" />
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="mastercard" className="h-5 opacity-30 grayscale hover:grayscale-0 transition-all cursor-pointer" />
                    </div>
                </div>
            </div>
        </footer>
    );
}