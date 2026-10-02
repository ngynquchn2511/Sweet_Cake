import React from 'react';
import MainLayout from '../Layouts/MainLayout';
import { Head, Link } from '@inertiajs/react';
import HomeSlider from '@/Components/HomeSlider';
import ProductCard from '@/Components/ProductCard';

// Dải banner cho các vị trí phụ (homepage_sub, sidebar, footer)
function BannerStrip({ items = [], position }) {
    if (items.length === 0) return null;
    return (
        <section className="container py-4" data-banner-position={position}>
            <div className="row g-4">
                {items.map((b) => {
                    const src = b.image?.startsWith('http') || b.image?.startsWith('/') ? b.image : `/${b.image}`;
                    const img = <img src={src} alt={b.title} className="w-full h-full object-cover rounded-2xl" style={{ maxHeight: 260 }} />;
                    return (
                        <div key={b.id} className={items.length === 1 ? 'col-12' : 'col-md-6'}>
                            {b.link ? <a href={b.link}>{img}</a> : img}
                        </div>
                    );
                })}
            </div>
        </section>
    );
}

export default function Home({ products, banners = {} }) {
    const allProducts = Array.isArray(products) ? products : (products?.data || []);
    const bannersAt = (position) => (banners && banners[position]) || [];
    return (
        <MainLayout>
            <Head title="SWEET BAKERY - Handmade with love" />

            {/* 1. Slider - Đảm bảo sát mép Navbar */}
            <div className="w-full">
                <HomeSlider banners={bannersAt('homepage_main')} />
            </div>

            {/* 2. Section Cam kết - Làm nhẹ nhàng hơn, bỏ Shadow dày */}
            <section className="py-12 bg-white border-b border-pink-50">
                <div className="container px-4">
                    <div className="row g-4">
                        {[
                            { icon: 'bi-patch-check', title: 'Chất lượng cao', desc: 'Nguyên liệu nhập khẩu 100%', color: 'text-pink-600' },
                            { icon: 'bi-truck', title: 'Giao hàng nhanh', desc: 'Miễn phí đơn từ 500k', color: 'text-gray-900' },
                            { icon: 'bi-heart', title: 'Làm bằng tâm hồn', desc: 'Bánh mới ra lò mỗi ngày', color: 'text-pink-600' }
                        ].map((item, index) => (
                            <div className="col-md-4" key={index}>
                                <div className="text-center p-6 border border-pink-50 rounded-2xl transition-all hover:bg-pink-50/30">
                                    <i className={`bi ${item.icon} fs-2 ${item.color} mb-3 d-block`}></i>
                                    <h6 className="fw-black text-xs uppercase tracking-widest mb-2 text-gray-900">{item.title}</h6>
                                    <p className="text-muted small mb-0 font-medium">{item.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <BannerStrip items={bannersAt('homepage_sub')} position="homepage_sub" />

            {/* 3. Danh sách sản phẩm mới - Minimal Header */}
            <div className="container py-16">
                <div className="flex justify-between items-end mb-10">
                    <div>
                        <span className="text-pink-600 text-[10px] fw-bold uppercase tracking-[0.3em] mb-2 d-block">Freshly Baked</span>
                        <h2 className="text-3xl font-black text-gray-900 tracking-tighter mb-0">SẢN PHẨM MỚI NHẤT</h2>
                    </div>
                    <Link
                        href="/products"
                        className="text-xs fw-bold text-gray-900 no-underline hover:text-pink-600 border-b-2 border-gray-900 hover:border-pink-600 pb-1 transition-all uppercase tracking-widest"
                    >
                        Xem tất cả
                    </Link>
                </div>

                <div className="row g-4 lg:g-6">
                    {allProducts.length > 0 ? (
                        allProducts.slice(0, 4).map((item) => (
                            <div className="col-lg-3 col-md-4 col-sm-6" key={item.id}>
                                <ProductCard product={item} />
                            </div>
                        ))
                    ) : (
                        <div className="col-12 text-center py-20 bg-gray-50 rounded-2xl">
                            <p className="text-gray-400 text-sm tracking-widest uppercase">Đang cập nhật bánh mới...</p>
                        </div>
                    )}
                </div>
            </div>

            <BannerStrip items={bannersAt('sidebar')} position="sidebar" />

            {/* 4. Banner phụ - Hiện đại, không dùng màu đen thuần */}
            <section className="pb-20">
                <div className="container">
                    <div className="relative rounded-[2rem] overflow-hidden">
                        <div className="bg-[#1a1a1a] p-10 md:p-20 text-center text-white">
                            <span className="text-pink-500 text-xs font-bold uppercase tracking-[0.5em] mb-4 d-block">The Art of Baking</span>
                            <h2 className="display-4 font-black mb-6 tracking-tighter">HƯƠNG VỊ NGHỆ NHÂN</h2>
                            <p className="text-gray-400 max-w-xl mx-auto mb-10 font-medium leading-relaxed">
                                Mỗi chiếc bánh tại Sweet Bakery không chỉ là thực phẩm, mà là một tác phẩm tâm huyết
                                được nhào nặn từ đôi bàn tay nghệ nhân lâu năm.
                            </p>
                            <Link
                                href="/about"
                                className="inline-block bg-white text-gray-900 px-10 py-4 rounded-full no-underline text-xs font-black tracking-widest hover:bg-pink-600 hover:text-white transition-all shadow-xl"
                            >
                                CÂU CHUYỆN CỦA CHÚNG TÔI
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <BannerStrip items={bannersAt('footer')} position="footer" />
        </MainLayout>
    );
}