// resources/js/Pages/Admin/Categories/Show.jsx
import { Head, router } from '@inertiajs/react';
import { Card, Button, Tag, Descriptions, Image } from 'antd';
import { ArrowLeftOutlined, EditOutlined, ShoppingOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

export default function CategoryShow({ auth, category }) {
    return (
        <AdminLayout user={auth.user}>
            <Head title={`Danh mục: ${category.name}`} />

            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Button icon={<ArrowLeftOutlined />} onClick={() => router.visit('/admin/categories')}>Quay lại</Button>
                <Button type="primary" icon={<EditOutlined />} onClick={() => router.visit(`/admin/categories/${category.id}/edit`)}>
                    Chỉnh sửa
                </Button>
            </div>

            <Card title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>{category.name}</span>}>
                <Descriptions column={1} bordered size="small">
                    <Descriptions.Item label="Hình ảnh">
                        {category.image ? <Image src={`/${category.image}`} width={120} /> : '—'}
                    </Descriptions.Item>
                    <Descriptions.Item label="Slug">{category.slug}</Descriptions.Item>
                    <Descriptions.Item label="Thứ tự hiển thị">{category.display_order ?? 0}</Descriptions.Item>
                    <Descriptions.Item label="Trạng thái">
                        <Tag color={category.status === 'active' ? 'green' : 'red'}>{category.status === 'active' ? 'Hoạt động' : 'Ẩn'}</Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Số sản phẩm">
                        <Button type="link" icon={<ShoppingOutlined />} style={{ padding: 0 }} onClick={() => router.visit(`/admin/products?category_id=${category.id}`)}>
                            {category.products_count ?? 0} sản phẩm
                        </Button>
                    </Descriptions.Item>
                    <Descriptions.Item label="Ngày tạo">{dayjs(category.created_at).format('DD/MM/YYYY HH:mm')}</Descriptions.Item>
                    <Descriptions.Item label="Mô tả">
                        <div style={{ whiteSpace: 'pre-wrap' }}>{category.description || '—'}</div>
                    </Descriptions.Item>
                </Descriptions>
            </Card>
        </AdminLayout>
    );
}
