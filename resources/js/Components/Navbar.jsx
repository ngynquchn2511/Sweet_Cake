import React, { useState, useEffect, useRef } from 'react';
import { Link, usePage } from '@inertiajs/react';

const Navbar = () => {
    const { auth, cartCount, wishlistCount } = usePage().props;
    
    // 1. Quản lý trạng thái đóng/mở của Dropdown
    const [isUserOpen, setIsUserOpen] = useState(false);
    const dropdownRef = useRef(null);

    // 2. Xử lý khi click ra ngoài menu thì tự động đóng lại
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsUserOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <nav className="bg-white sticky top-0 z-50 transition-all duration-300 shadow-sm border-b border-pink-50">
            {/* Top Bar */}
            <div className="hidden lg:block bg-pink-600 text-white text-[11px] py-1.5 text-center font-medium tracking-widest uppercase">
                Giao bánh miễn phí cho đơn hàng từ 500k 🍰 Đặt ngay: 1900 636 546
            </div>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center h-20">
                    
                    {/* 1. LOGO & MENU CHÍNH */}
                    <div className="flex items-center gap-12">
                        <Link href="/" className="flex flex-col group no-underline hover:no-underline">
                            <span className="text-2xl font-black tracking-tighter text-gray-900 group-hover:text-pink-600 transition-colors">
                                SWEET <span className="text-pink-600">BAKERY.</span>
                            </span>
                            <span className="text-[9px] tracking-[0.3em] uppercase text-gray-400 font-bold -mt-1">Handmade with love</span>
                        </Link>

                        <div className="hidden lg:flex items-center gap-8 text-sm font-bold text-gray-600 uppercase tracking-widest">
                            <Link href="/" className="no-underline hover:no-underline hover:text-pink-600 transition-colors relative group">
                                Trang chủ
                                <span className="absolute -bottom-1 left-0 w-0 h-0.5 bg-pink-600 transition-all group-hover:w-full"></span>
                            </Link>
                            <Link href={route('products.index')} className="no-underline hover:no-underline hover:text-pink-600 transition-colors relative group">
                                Cửa hàng
                                <span className="absolute -bottom-1 left-0 w-0 h-0.5 bg-pink-600 transition-all group-hover:w-full"></span>
                            </Link>
                            <Link href="/about" className="no-underline hover:no-underline hover:text-pink-600 transition-colors relative group">
                                Chúng tôi
                                <span className="absolute -bottom-1 left-0 w-0 h-0.5 bg-pink-600 transition-all group-hover:w-full"></span>
                            </Link>
                            <Link href={route('contact')} className="no-underline hover:no-underline hover:text-pink-600 transition-colors relative group">
                                Liên hệ
                                <span className="absolute -bottom-1 left-0 w-0 h-0.5 bg-pink-600 transition-all group-hover:w-full"></span>
                            </Link>
                        </div>
                    </div>

                    {/* 2. ICON ACTIONS */}
                    <div className="flex items-center gap-2 sm:gap-5">
                        <button className="hidden md:block text-gray-400 hover:text-pink-600 transition-colors outline-none border-none bg-transparent">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        {/* Yêu thích */}
                        <Link href={auth?.user ? route('wishlist.index') : route('login')} aria-label="Sản phẩm yêu thích" className="relative group p-2 bg-pink-50 rounded-full hover:bg-pink-100 transition-all no-underline hover:no-underline">
                            <i className="bi bi-heart text-pink-600 text-xl leading-none d-block w-6 h-6 text-center"></i>
                            <span data-testid="wishlist-count" className="absolute -top-1 -right-1 bg-gray-900 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm">
                                {wishlistCount || 0}
                            </span>
                        </Link>

                        {/* Giỏ hàng */}
                        <Link href="/cart" className="relative group p-2 bg-pink-50 rounded-full hover:bg-pink-100 transition-all no-underline hover:no-underline">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span className="absolute -top-1 -right-1 bg-gray-900 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm">
                                {cartCount || 0}
                            </span>
                        </Link>

                        <div className="h-8 w-[1px] bg-gray-100 mx-1 hidden md:block"></div>

                        {/* Tài khoản - ĐÃ CẬP NHẬT LOGIC ĐIỀU KHIỂN */}
                        {auth?.user ? (
                            <div className="relative" ref={dropdownRef}>
                                <button 
                                    onClick={() => setIsUserOpen(!isUserOpen)}
                                    className="flex items-center gap-2 outline-none border-none bg-transparent cursor-pointer group"
                                >
                                    <div className="w-9 h-9 bg-pink-100 rounded-full flex items-center justify-center text-pink-600 font-bold text-sm border-2 border-transparent group-hover:border-pink-300 transition-all">
                                        {auth.user.full_name.charAt(0)}
                                    </div>
                                    <span className="hidden md:block text-sm font-bold text-gray-700">
                                        Hi, {auth.user.full_name.split(' ').pop()}
                                    </span>
                                    <i className={`bi bi-chevron-down text-[10px] transition-transform duration-200 ${isUserOpen ? 'rotate-180' : ''}`}></i>
                                </button>
                                
                                {/* Dropdown Menu */}
                                {isUserOpen && (
                                    <div className="absolute right-0 w-52 mt-3 py-2 bg-white rounded-2xl shadow-2xl border border-pink-50 z-50 animate-in fade-in zoom-in duration-150 origin-top-right">
                                        <div className="px-4 py-2 border-b border-gray-50 mb-1">
                                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0">Tài khoản</p>
                                            <p className="text-sm font-bold text-gray-800 truncate mb-0">{auth.user.full_name}</p>
                                        </div>

                                        <Link className="block px-4 py-2.5 text-sm text-gray-600 hover:bg-pink-50 hover:text-pink-600 no-underline" href="/my-orders">
                                            <i className="bi bi-clock-history me-2"></i> Lịch sử đơn hàng
                                        </Link>
                                        <Link className="block px-4 py-2.5 text-sm text-gray-600 hover:bg-pink-50 hover:text-pink-600 no-underline" href={route('wishlist.index')}>
                                            <i className="bi bi-heart me-2"></i> Sản phẩm yêu thích
                                        </Link>
                                        <Link className="block px-4 py-2.5 text-sm text-gray-600 hover:bg-pink-50 hover:text-pink-600 no-underline" href={route('messages.mine')}>
                                            <i className="bi bi-chat-dots me-2"></i> Tin nhắn của tôi
                                        </Link>
                                        <Link className="block px-4 py-2.5 text-sm text-gray-600 hover:bg-pink-50 hover:text-pink-600 no-underline" href="/dashboard">
                                            <i className="bi bi-person-gear me-2"></i> Thông tin cá nhân
                                        </Link>
                                        
                                        <hr className="my-1 border-gray-50" />
                                        
                                        <Link 
                                            method="post" 
                                            as="button" 
                                            className="w-full text-left px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 no-underline border-none bg-transparent cursor-pointer" 
                                            href={route('logout')}
                                        >
                                            <i className="bi bi-box-arrow-right me-2"></i> Đăng xuất
                                        </Link>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <Link
                                href={route('login')}
                                className="bg-gray-900 text-white px-6 py-2.5 rounded-full text-xs font-bold hover:bg-pink-600 transition-all shadow-lg shadow-gray-200 no-underline hover:no-underline"
                            >
                                ĐĂNG NHẬP
                            </Link>
                        )}
                    </div>
                </div>
            </div>
        </nav>
    );
};

export default Navbar;