import '@/bootstrap';
import React, { useState } from 'react';
import MainLayout from '../../../Layouts/MainLayout';
import ProductCard from '../../../Components/ProductCard';
import { router } from '@inertiajs/react';

export default function Index({ products, categories, filters }) {
    const allProducts = products.data;
    const [search, setSearch] = useState(filters.search || '');
    const currentCategory = filters.category || null;

    // Hàm áp dụng lọc
    const applyFilters = (newFilters) => {
        router.get('/products', 
            { 
                search: search, 
                category: currentCategory,
                ...newFilters 
            }, 
            { 
                preserveState: true,
                replace: true,
                only: ['products', 'filters']
            }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        applyFilters({ search: search });
    };

    const handleCategoryClick = (catId) => {
        // Nếu nhấn lại cái đang chọn thì bỏ lọc (về null)
        const id = currentCategory == catId ? null : catId;
        applyFilters({ category: id });
    };

    return (
        <MainLayout>
            <div className="container py-5">
                {/* 1. THANH TÌM KIẾM */}
                <div className="row mb-4 justify-content-center">
                    <div className="col-md-6">
                        <form onSubmit={handleSearch} className="input-group shadow-sm border rounded-pill overflow-hidden bg-white">
                            <input
                                type="text"
                                className="form-control border-0 py-2 px-4 shadow-none"
                                placeholder="Bạn muốn tìm bánh gì hôm nay?..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <button className="btn btn-pink px-4 border-0" type="submit">
                                <i className="bi bi-search text-white"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {/* 2. THANH CÔNG CỤ LỌC LOẠI BÁNH */}
                <div className="mb-5">
                    <div className="d-flex flex-wrap justify-content-center gap-2">
                        <button
                            onClick={() => applyFilters({ category: null })}
                            className={`btn btn-sm rounded-pill px-4 fw-bold uppercase tracking-wider transition-all ${
                                !currentCategory ? 'btn-pink shadow-sm' : 'btn-outline-secondary border-gray-200'
                            }`}
                            style={{ fontSize: '11px' }}
                        >
                            Tất cả bánh
                        </button>
                        
                        {/* Kiểm tra nếu có categories từ backend thì mới map */}
                        {categories && categories.length > 0 && categories.filter(c => c.status === 'active').map((cat) => (
                            <button
                                key={cat.id}
                                onClick={() => handleCategoryClick(cat.id)}
                                className={`btn btn-sm rounded-pill px-4 fw-bold uppercase tracking-wider transition-all ${
                                    currentCategory == cat.id ? 'btn-pink shadow-sm' : 'btn-outline-secondary border-gray-200'
                                }`}
                                style={{ fontSize: '11px' }}
                            >
                                {cat.name}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="d-flex justify-content-between align-items-end mb-4">
                    <h2 className="fw-black text-gray-900 mb-0 tracking-tighter uppercase">Danh sách sản phẩm</h2>
                    <span className="text-muted small font-medium">Tìm thấy {products.total} sản phẩm</span>
                </div>
                
                <div className="row g-4">
                    {allProducts && allProducts.length > 0 ? (
                        allProducts.map((item) => (
                            <div className="col-lg-3 col-md-4 col-sm-6" key={item.id}>
                                <ProductCard product={item} />
                            </div>
                        ))
                    ) : (
                        <div className="col-12 text-center py-5 bg-light rounded-4 border border-dashed">
                            <i className="bi bi-search fs-1 text-gray-300 d-block mb-3"></i>
                            <p className="text-muted font-bold uppercase tracking-widest">Không có loại bánh này rồi...</p>
                        </div>
                    )}
                </div>

                {/* PHÂN TRANG */}
                <div className="mt-5 d-flex justify-content-center">
                    {products.links.map((link, index) => (
                        <button 
                            key={index}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`btn mx-1 rounded-3 px-3 ${link.active ? 'btn-pink' : 'btn-outline-secondary border-gray-200'}`}
                            onClick={() => link.url && router.get(link.url, { search: search, category: currentCategory }, { preserveState: true })}
                            disabled={!link.url}
                            style={{ fontSize: '13px' }}
                        />
                    ))}
                </div>
            </div>

            <style dangerouslySetInnerHTML={{ __html: `
                .btn-pink { background-color: #db2777 !important; color: white !important; border: none; }
                .btn-pink:hover { background-color: #be185d !important; }
                .fw-black { font-weight: 900; }
                .tracking-tighter { letter-spacing: -0.05em; }
            `}} />
        </MainLayout>
    );
}