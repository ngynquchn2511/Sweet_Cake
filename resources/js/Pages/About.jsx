import React from 'react';
import MainLayout from '../Layouts/MainLayout';
import { Head, Link } from '@inertiajs/react';

export default function About() {
    return (
        <MainLayout>
            <Head title="Câu chuyện của chúng tôi - SWEET BAKERY" />

            {/* 1. Hero Section */}
            <section className="py-20 bg-pink-50/30">
                <div className="container px-4 text-center">
                    <span className="text-pink-600 text-xs font-bold uppercase tracking-[0.5em] mb-4 d-block">Since 2010</span>
                    <h1 className="display-4 font-black text-gray-900 tracking-tighter mb-6">CÂU CHUYỆN VỀ SỰ NGỌT NGÀO</h1>
                    <div className="w-20 h-1 bg-pink-600 mx-auto"></div>
                </div>
            </section>

            {/* 2. Nội dung chính */}
            <section className="py-20 bg-white">
                <div className="container px-4">
                    <div className="row align-items-center g-5">
                        <div className="col-lg-6">
                            <div className="relative p-2">
                                <img 
                                    src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?q=80&w=1000&auto=format&fit=crop" 
                                    alt="Bakery Kitchen" 
                                    className="rounded-2xl shadow-2xl w-full object-cover aspect-[4/5]"
                                />
                                <div className="absolute -bottom-6 -right-6 bg-white p-6 rounded-2xl shadow-xl hidden md:block border border-pink-50">
                                    <p className="text-pink-600 font-black text-3xl mb-0">14+</p>
                                    <p className="text-gray-500 text-[10px] uppercase tracking-widest font-bold">Năm kinh nghiệm</p>
                                </div>
                            </div>
                        </div>
                        <div className="col-lg-6">
                            <h2 className="text-3xl font-black text-gray-900 tracking-tighter mb-6">TỪ ĐÔI BÀN TAY <br/> ĐẾN TRÁI TIM</h2>
                            <p className="text-gray-600 leading-relaxed mb-4">
                                Khởi đầu từ một căn bếp nhỏ với niềm đam mê bất tận dành cho những ổ bánh mì nóng hổi, 
                                <strong> Sweet Bakery</strong> đã hành trình hơn một thập kỷ để mang đến hương vị hạnh phúc cho mọi gia đình.
                            </p>
                            <p className="text-gray-600 leading-relaxed mb-6">
                                Chúng tôi tin rằng, một chiếc bánh ngon không chỉ đến từ nguyên liệu thượng hạng, 
                                mà còn đến từ sự tỉ mỉ, kiên nhẫn và tình yêu mà người nghệ nhân gửi gắm vào từng thớ bột.
                            </p>
                            
                            <div className="row g-4 pt-4 border-t border-gray-100">
                                <div className="col-6">
                                    <h6 className="font-black text-xs uppercase tracking-widest text-gray-900 mb-2">Sứ mệnh</h6>
                                    <p className="text-muted small">Gìn giữ hương vị thủ công thuần khiết nhất.</p>
                                </div>
                                <div className="col-6">
                                    <h6 className="font-black text-xs uppercase tracking-widest text-gray-900 mb-2">Tầm nhìn</h6>
                                    <p className="text-muted small">Trở thành biểu tượng bánh ngọt nghệ nhân Việt.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* 3. Giá trị cốt lõi (Minimal Grid) */}
            <section className="py-20 bg-gray-50">
                <div className="container px-4 text-center mb-16">
                    <h2 className="text-2xl font-black text-gray-900 tracking-tighter">GIÁ TRỊ CHÚNG TÔI THEO ĐUỔI</h2>
                </div>
                <div className="container px-4">
                    <div className="row g-4">
                        {[
                            { title: 'Nguyên liệu sạch', desc: 'Lựa chọn khắt khe từ những nguồn cung ứng bền vững.' },
                            { title: 'Sáng tạo không ngừng', desc: 'Kết hợp hương vị truyền thống và kỹ thuật hiện đại.' },
                            { title: 'Khách hàng là tâm tâm', desc: 'Lắng nghe và thấu hiểu để phục vụ tốt hơn mỗi ngày.' }
                        ].map((item, i) => (
                            <div className="col-md-4" key={i}>
                                <div className="bg-white p-8 rounded-2xl h-100 border border-pink-50 text-center transition-all hover:border-pink-200">
                                    <div className="text-pink-600 font-black mb-3">0{i+1}.</div>
                                    <h5 className="font-bold text-gray-900 mb-3">{item.title}</h5>
                                    <p className="text-muted small mb-0">{item.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* 4. CTA */}
            <section className="py-20 bg-white text-center">
                <div className="container px-4">
                    <h2 className="text-3xl font-black text-gray-900 tracking-tighter mb-8">THỬ NGAY HƯƠNG VỊ HÔM NAY</h2>
                    <Link 
                        href="/products" 
                        className="inline-block bg-gray-900 text-white px-10 py-3 rounded-full no-underline text-xs font-black tracking-widest hover:bg-pink-600 transition-all shadow-lg shadow-pink-100"
                    >
                        GHÉ THĂM CỬA HÀNG
                    </Link>
                </div>
            </section>
        </MainLayout>
    );
}