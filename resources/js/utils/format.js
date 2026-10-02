// Tiện ích hiển thị dùng chung cho các trang khách hàng

export const formatCurrency = (value) =>
    new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' })
        .format(Number(value) || 0)
        .replace('₫', 'đ');

export const productImageUrl = (image) => {
    if (!image) return 'https://via.placeholder.com/300x200';
    if (image.startsWith('http') || image.startsWith('/')) return image;
    return image.startsWith('uploads/') ? `/${image}` : `/uploads/${image}`;
};

export const formatDateTime = (value) =>
    value ? new Date(value).toLocaleString('vi-VN') : '';
