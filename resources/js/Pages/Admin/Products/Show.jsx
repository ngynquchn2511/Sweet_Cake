// resources/js/Pages/Admin/Products/Show.jsx
import { Head, router } from '@inertiajs/react';
import { Card, Button, Space, Tag, Descriptions, Image, Row, Col } from 'antd';
import { ArrowLeftOutlined, EditOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const formatCurrency = (value) =>
    new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(value) || 0);

export default function ProductShow({ auth, product }) {
    const stock = Number(product.stock) || 0;

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Sản phẩm: ${product.name}`} />

            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Button icon={<ArrowLeftOutlined />} onClick={() => router.visit('/admin/products')}>Quay lại</Button>
                <Button type="primary" icon={<EditOutlined />} onClick={() => router.visit(`/admin/products/${product.id}/edit`)}>
                    Chỉnh sửa
                </Button>
            </div>

            <Card title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>{product.name}</span>}>
                <Row gutter={24}>
                    <Col xs={24} md={8}>
                        {product.image ? (
                            <Image src={`/${product.image}`} alt={product.name} style={{ borderRadius: 8 }} />
                        ) : (
                            <div style={{ height: 200, background: '#f5f5f5', borderRadius: 8, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#999' }}>
                                Chưa có ảnh
                            </div>
                        )}
                    </Col>
                    <Col xs={24} md={16}>
                        <Descriptions column={1} bordered size="small">
                            <Descriptions.Item label="Slug / SKU">{product.slug}</Descriptions.Item>
                            <Descriptions.Item label="Danh mục">{product.category?.name || '—'}</Descriptions.Item>
                            <Descriptions.Item label="Giá gốc">{formatCurrency(product.price)}</Descriptions.Item>
                            <Descriptions.Item label="Giá khuyến mãi">{product.discount_price ? formatCurrency(product.discount_price) : '—'}</Descriptions.Item>
                            <Descriptions.Item label="Tồn kho">
                                <Tag color={stock === 0 ? 'red' : stock <= 10 ? 'orange' : 'green'}>{stock} {product.unit}</Tag>
                            </Descriptions.Item>
                            <Descriptions.Item label="Trạng thái">
                                <Tag color={product.status === 'active' ? 'green' : 'red'}>{product.status === 'active' ? 'Đang bán' : 'Ngừng bán'}</Tag>
                            </Descriptions.Item>
                            <Descriptions.Item label="Đã bán / Lượt xem">
                                <Space>{product.sold_count || 0} đã bán · {product.view_count || 0} lượt xem</Space>
                            </Descriptions.Item>
                            <Descriptions.Item label="Ngày tạo">{dayjs(product.created_at).format('DD/MM/YYYY HH:mm')}</Descriptions.Item>
                            <Descriptions.Item label="Mô tả">
                                <div style={{ whiteSpace: 'pre-wrap' }}>{product.description || '—'}</div>
                            </Descriptions.Item>
                        </Descriptions>
                    </Col>
                </Row>
            </Card>
        </AdminLayout>
    );
}
