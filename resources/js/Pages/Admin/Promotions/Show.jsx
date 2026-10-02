// resources/js/Pages/Admin/Promotions/Show.jsx
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { Card, Button, Space, Tag, Descriptions, Row, Col, Statistic, Table, message } from 'antd';
import { ArrowLeftOutlined, EditOutlined, CopyOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const formatCurrency = (value) =>
    new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(value) || 0);

export default function PromotionShow({ auth, promotion, usage }) {
    const duplicate = async () => {
        try {
            const res = await axios.post(`/admin/promotions/${promotion.id}/duplicate`);
            message.success(`${res.data.message}: ${res.data.data.code}`);
            router.visit(`/admin/promotions/${res.data.data.id}/edit`);
        } catch (err) {
            message.error(err.response?.data?.message || 'Không nhân bản được mã khuyến mãi');
        }
    };

    const discountText = promotion.discount_type === 'percent'
        ? `${Number(promotion.discount_value)}%${promotion.max_discount ? ` (tối đa ${formatCurrency(promotion.max_discount)})` : ''}`
        : formatCurrency(promotion.discount_value);

    const orderColumns = [
        { title: 'Mã đơn', dataIndex: 'order_code', key: 'order_code' },
        { title: 'Khách hàng', key: 'user', render: (_, o) => o.user?.full_name || '—' },
        { title: 'Tổng tiền', dataIndex: 'total_amount', key: 'total_amount', align: 'right', render: formatCurrency },
        { title: 'Được giảm', dataIndex: 'discount_amount', key: 'discount_amount', align: 'right', render: formatCurrency },
        { title: 'Trạng thái', dataIndex: 'order_status', key: 'order_status', render: (s) => <Tag>{s}</Tag> },
        { title: 'Ngày đặt', dataIndex: 'created_at', key: 'created_at', render: (d) => dayjs(d).format('DD/MM/YYYY HH:mm') },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Khuyến mãi: ${promotion.code}`} />

            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Button icon={<ArrowLeftOutlined />} onClick={() => router.visit('/admin/promotions')}>Quay lại</Button>
                <Space>
                    <Button icon={<CopyOutlined />} onClick={duplicate}>Nhân bản</Button>
                    <Button type="primary" icon={<EditOutlined />} onClick={() => router.visit(`/admin/promotions/${promotion.id}/edit`)}>Chỉnh sửa</Button>
                </Space>
            </div>

            <Card title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>{promotion.code}</span>} style={{ marginBottom: 16 }}>
                <Descriptions column={{ xs: 1, md: 2 }} bordered size="small">
                    <Descriptions.Item label="Mức giảm">{discountText}</Descriptions.Item>
                    <Descriptions.Item label="Đơn tối thiểu">{formatCurrency(promotion.min_order_value)}</Descriptions.Item>
                    <Descriptions.Item label="Hiệu lực">
                        {dayjs(promotion.start_date).format('DD/MM/YYYY')} → {dayjs(promotion.end_date).format('DD/MM/YYYY')}
                    </Descriptions.Item>
                    <Descriptions.Item label="Trạng thái">
                        <Tag color={promotion.status === 'active' ? 'green' : 'default'}>{promotion.status}</Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Lượt dùng">{promotion.used_count}{promotion.usage_limit ? ` / ${promotion.usage_limit}` : ' (không giới hạn)'}</Descriptions.Item>
                    <Descriptions.Item label="Mô tả">{promotion.description || '—'}</Descriptions.Item>
                </Descriptions>
            </Card>

            <Card title="Báo cáo sử dụng">
                <Row gutter={16} style={{ marginBottom: 16 }}>
                    <Col xs={24} md={12}><Statistic title="Số đơn đã áp dụng" value={usage?.total_orders || 0} /></Col>
                    <Col xs={24} md={12}><Statistic title="Tổng tiền đã giảm cho khách" value={usage?.total_discount_given || 0} formatter={(v) => formatCurrency(v)} /></Col>
                </Row>
                <Table columns={orderColumns} dataSource={usage?.orders || []} rowKey="id" pagination={{ pageSize: 10 }} locale={{ emptyText: 'Chưa có đơn hàng nào dùng mã này' }} />
            </Card>
        </AdminLayout>
    );
}
