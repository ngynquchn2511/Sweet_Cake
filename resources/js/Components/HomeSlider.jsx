import React from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';
import '../../css/app.css';

// Import CSS của Swiper
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';

// banners: danh sách banner vị trí homepage_main từ CSDL; nếu chưa cấu hình thì dùng ảnh mặc định
const HomeSlider = ({ banners = [] }) => {
    const bannerSlides = banners.map((b) => ({
        image: b.image?.startsWith('http') || b.image?.startsWith('/') ? b.image : `/${b.image}`,
        link: b.link,
        title: b.title,
    }));

    const slides = bannerSlides.length > 0 ? bannerSlides : [
        {
            image: '/images/slider_1.webp',
            // title: 'Bánh Ngọt Mỗi Ngày',
            // desc: 'Hương vị tình yêu trong từng miếng bánh.'
        },
        {
            image: '/images/slider_2.webp',
            // title: 'Giảm Giá 20% Thẻ Thành Viên',
            // desc: 'Áp dụng cho tất cả các loại bánh kem.'
        }
    ];

    return (
        <div className="w-full h-[595px] overflow-hidden">
            <Swiper
                modules={[Navigation, Pagination, Autoplay, EffectFade]}
                effect="fade" // Hiệu ứng mờ dần (sang trọng hơn lướt ngang)
                spaceBetween={0}
                slidesPerView={1}
                navigation
                pagination={{ clickable: true }}
                autoplay={{ delay: 3000, disableOnInteraction: false }}
                loop={true}
                className="h-full w-full"
            >
                {slides.map((slide, index) => (
                    <SwiperSlide key={index}>
                        <div 
                            className="relative w-full h-full flex items-center justify-center bg-cover bg-center"
                            style={{ backgroundImage: `url(${slide.image})` }}
                        >
                            {/* Lớp phủ tối để nổi bật chữ */}
                            {/* <div className="absolute inset-0 bg-black/30"></div> */}
                            
                            {slide.link && (
                                <a href={slide.link} className="absolute inset-0 z-20" aria-label={slide.title || 'Banner'}></a>
                            )}
                            <div className="relative z-10 text-center text-white px-4">
                                <h2 className="text-5xl font-bold mb-4 drop-shadow-lg">
                                    {slide.title}
                                </h2>
                                <p className="text-xl mb-6 drop-shadow-md">
                                    {slide.desc}
                                </p>
                                {/* <button className="bg-pink-500 hover:bg-pink-600 text-white px-8 py-3 rounded-full font-semibold transition">
                                    Mua ngay
                                </button> */}
                            </div>
                        </div>
                    </SwiperSlide>
                ))}
            </Swiper>
        </div>
    );
};

export default HomeSlider;