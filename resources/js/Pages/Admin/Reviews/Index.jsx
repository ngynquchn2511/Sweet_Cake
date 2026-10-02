// resources/js/Pages/Admin/Reviews/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import {
    Card, Table, Button, Input, Select, Space, Tag, Rate,
    Popconfirm, message, Row, Col, Statistic, Modal, Descriptions, Image
} from 'antd';
import {
    SearchOutlined, FilterOutlined, DeleteOutlined, 
    CheckOutlined, CloseOutlined, StarOutlined, EyeOutlined
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const { Search } = Input;
const { Option } = Select;

export default function ReviewsIndex({ auth, reviews, stats, filters = {} }) {
    const [searchText, setSearchText] = useState(filters?.search || '');
    const [selectedRating, setSelectedRating] = useState(filters?.rating || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');

    const handleSearch = (value) => {
        router.get('/admin/reviews', {
            search: value,
            rating: selectedRating,
            status: selectedStatus,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleRatingFilter = (value) => {
        setSelectedRating(value);
        router.get('/admin/reviews', {
            search: searchText,
            rating: value,
            status: selectedStatus,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleStatusFilter = (value) => {
        setSelectedStatus(value);
        router.get('/admin/reviews', {
            search: searchText,
            rating: selectedRating,
            status: value,
        }, { preserveState: true, preserveScroll: true });
    };

    // Duyệt nhanh / Từ chối nhanh ngay trên bảng (route approve/reject riêng)
    const handleUpdateStatus = (id, status) => {
        const action = status === 'approved' ? 'approve' : 'reject';
        router.put(`/admin/reviews/${id}/${action}`, {}, {
            preserveScroll: true,
            onSuccess: () => message.success(status === 'approved' ? 'Đã duyệt đánh giá' : 'Đã từ chối đánh giá'),
        });
    };

    // Xem chi tiết 1 đánh giá (kèm ảnh đính kèm, người duyệt)
    const [detail, setDetail] = useState(null);
    const openDetail = async (id) => {
        try {
            const res = await axios.get(`/admin/reviews/${id}`);
            setDetail(res.data.data);
        } catch {
            message.error('Không tải được chi tiết đánh giá');
        }
    };

    const handleDelete = (id) => {
        router.delete(`/admin/reviews/${id}`, {
            onSuccess: () => message.success('Đã xóa đánh giá!'),
        });
    };

    const statusColor = { pending: 'orange', approved: 'green', rejected: 'red' };
    const statusLabel = { pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối' };

    const columns = [
        {
            title: 'Sản phẩm',
            key: 'product',
            width: 250,
            render: (_, record) => (
                <div>
                    <div style={{ fontWeight: 500 }}>{record.product?.name || '—'}</div>
                    <div style={{ fontSize: 12, color: '#999' }}>
                        {record.user?.full_name || 'Khách ẩn danh'}
                    </div>
                </div>
            ),
        },
        {
            title: 'Đánh giá',
            dataIndex: 'rating',
            key: 'rating',
            width: 120,
            align: 'center',
            render: (rating) => <Rate disabled value={rating} style={{ fontSize: 16 }} />,
        },
        {
            title: 'Nội dung',
            dataIndex: 'comment',
            key: 'comment',
            render: (text) => (
                <div style={{ maxWidth: 300 }}>
                    {text ? (text.length > 80 ? text.slice(0, 80) + '...' : text) : '—'}
                </div>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            width: 110,
            align: 'center',
            render: (status) => <Tag color={statusColor[status]}>{statusLabel[status]}</Tag>,
        },
        {
            title: 'Ngày đánh giá',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 130,
            align: 'center',
            render: (date) => dayjs(date).format('DD/MM/YYYY'),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 230,
            align: 'center',
            render: (_, record) => (
                <Space size="small">
                    <Button size="small" icon={<EyeOutlined />} aria-label="Xem chi tiết" onClick={() => openDetail(record.id)} />
                    {record.status !== 'approved' && (
                        <Button
                            type="primary"
                            size="small"
                            icon={<CheckOutlined />}
                            onClick={() => handleUpdateStatus(record.id, 'approved')}
                        >
                            Duyệt
                        </Button>
                    )}
                    {record.status !== 'rejected' && (
                        <Button
                            size="small"
                            icon={<CloseOutlined />}
                            onClick={() => handleUpdateStatus(record.id, 'rejected')}
                        >
                            Từ chối
                        </Button>
                    )}
                    <Popconfirm
                        title="Xóa đánh giá này?"
                        onConfirm={() => handleDelete(record.id)}
                        okText="Xóa" cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                    >
                        <Button danger icon={<DeleteOutlined />} size="small" />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Đánh giá sản phẩm" />

            <Modal
                open={Boolean(detail)}
                title="Chi tiết đánh giá"
                onCancel={() => setDetail(null)}
                width={640}
                footer={detail && [
                    detail.status !== 'rejected' && (
                        <Button key="reject" icon={<CloseOutlined />} onClick={() => { handleUpdateStatus(detail.id, 'rejected'); setDetail(null); }}>Từ chối</Button>
                    ),
                    detail.status !== 'approved' && (
                        <Button key="approve" type="primary" icon={<CheckOutlined />} onClick={() => { handleUpdateStatus(detail.id, 'approved'); setDetail(null); }}>Duyệt</Button>
                    ),
                ]}
            >
                {detail && (
                    <Descriptions column={1} bordered size="small">
                        <Descriptions.Item label="Sản phẩm">{detail.product?.name || '—'}</Descriptions.Item>
                        <Descriptions.Item label="Khách hàng">{detail.user?.full_name || '—'} ({detail.user?.email || '—'})</Descriptions.Item>
                        <Descriptions.Item label="Đơn hàng">{detail.order?.order_code || '—'}</Descriptions.Item>
                        <Descriptions.Item label="Số sao"><Rate disabled value={Number(detail.rating)} /></Descriptions.Item>
                        <Descriptions.Item label="Nội dung"><div style={{ whiteSpace: 'pre-wrap' }}>{detail.comment || '—'}</div></Descriptions.Item>
                        <Descriptions.Item label="Ảnh đính kèm">
                            {detail.images?.length ? (
                                <Image.PreviewGroup>
                                    {detail.images.map((img) => <Image key={img} src={`/${img}`} width={80} style={{ marginRight: 8 }} />)}
                                </Image.PreviewGroup>
                            ) : '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="Trạng thái"><Tag color={statusColor[detail.status]}>{statusLabel[detail.status]}</Tag></Descriptions.Item>
                        <Descriptions.Item label="Người duyệt">
                            {detail.reviewer ? `${detail.reviewer.full_name} · ${dayjs(detail.reviewed_at).format('DD/MM/YYYY HH:mm')}` : '—'}
                        </Descriptions.Item>
                    </Descriptions>
                )}
            </Modal>

            {/* Statistics */}
            <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                <Col xs={12} md={6}>
                    <Card>
                        <Statistic
                            title="Tổng đánh giá"
                            value={stats.total}
                            prefix={<StarOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={12} md={6}>
                    <Card>
                        <Statistic
                            title="Chờ duyệt"
                            value={stats.pending}
                            valueStyle={{ color: '#fa8c16' }}
                        />
                    </Card>
                </Col>
                <Col xs={12} md={6}>
                    <Card>
                        <Statistic
                            title="Đã duyệt"
                            value={stats.approved}
                            valueStyle={{ color: '#52c41a' }}
                        />
                    </Card>
                </Col>
                <Col xs={12} md={6}>
                    <Card>
                        <Statistic
                            title="Đánh giá TB"
                            value={stats.avg_rating}
                            precision={1}
                            suffix="⭐"
                            valueStyle={{ color: '#faad14' }}
                        />
                    </Card>
                </Col>
            </Row>

            <Card
                title={
                    <span style={{ fontSize: 20, fontWeight: 'bold' }}>
                        ⭐ Đánh giá sản phẩm
                    </span>
                }
            >
                {/* Filters */}
                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                    <Col xs={24} md={12}>
                        <Search
                            placeholder="Tìm theo tên sản phẩm, khách hàng..."
                            allowClear
                            size="large"
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onSearch={handleSearch}
                            prefix={<SearchOutlined />}
                        />
                    </Col>
                    <Col xs={12} md={6}>
                        <Select
                            placeholder="Lọc theo sao"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedRating || undefined}
                            onChange={handleRatingFilter}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="5">5 sao ⭐⭐⭐⭐⭐</Option>
                            <Option value="4">4 sao ⭐⭐⭐⭐</Option>
                            <Option value="3">3 sao ⭐⭐⭐</Option>
                            <Option value="2">2 sao ⭐⭐</Option>
                            <Option value="1">1 sao ⭐</Option>
                        </Select>
                    </Col>
                    <Col xs={12} md={6}>
                        <Select
                            placeholder="Lọc trạng thái"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedStatus || undefined}
                            onChange={handleStatusFilter}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="pending">Chờ duyệt</Option>
                            <Option value="approved">Đã duyệt</Option>
                            <Option value="rejected">Từ chối</Option>
                        </Select>
                    </Col>
                </Row>

                {/* Table */}
                <Table
                    columns={columns}
                    dataSource={reviews?.data || []}
                    rowKey="id"
                    pagination={{
                        current: reviews?.current_page || 1,
                        pageSize: reviews?.per_page || 15,
                        total: reviews?.total || 0,
                        showSizeChanger: true,
                        showTotal: (total) => `Tổng ${total} đánh giá`,
                        onChange: (page, pageSize) => {
                            router.get('/admin/reviews', {
                                ...filters,
                                page,
                                per_page: pageSize,
                            }, { preserveState: true, preserveScroll: true });
                        },
                    }}
                />
            </Card>
        </AdminLayout>
    );
}