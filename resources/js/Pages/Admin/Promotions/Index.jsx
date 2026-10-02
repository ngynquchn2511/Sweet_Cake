// resources/js/Pages/Admin/Promotions/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { 
    Card, Table, Button, Input, Select, Space, Tag, Popconfirm, 
    message, Row, Col, Switch, Tooltip, 
} from 'antd';
import {
    PlusOutlined, EditOutlined, DeleteOutlined, SearchOutlined, 
    FilterOutlined, GiftOutlined, CheckCircleOutlined, CloseCircleOutlined,
    EyeOutlined, CopyOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const { Search } = Input;
const { Option } = Select;

export default function PromotionIndex({ auth, promotions, filters = {} }) {
    const [searchText, setSearchText] = useState(filters?.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [selectedType, setSelectedType] = useState(filters?.discount_type || '');

    // Format currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(value);
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/promotions', {
            search: value,
            status: selectedStatus,
            discount_type: selectedType,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle filter change
    const handleFilterChange = (type, value) => {
        const params = {
            search: searchText,
            status: type === 'status' ? value : selectedStatus,
            discount_type: type === 'discount_type' ? value : selectedType,
        };

        if (type === 'status') setSelectedStatus(value);
        if (type === 'discount_type') setSelectedType(value);

        router.get('/admin/promotions', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle toggle status
    const handleToggleStatus = (id, currentStatus) => {
        router.post(`/admin/promotions/${id}/toggle-status`, {}, {
            preserveState: false,
            onSuccess: () => {
                message.success(
                    currentStatus === 'active' 
                        ? 'Đã vô hiệu hóa mã khuyến mãi!' 
                        : 'Đã kích hoạt mã khuyến mãi!'
                );
            },
        });
    };

    // Handle delete
    const handleDelete = (id) => {
        router.delete(`/admin/promotions/${id}`, {
            onSuccess: () => {
                message.success('Xóa mã khuyến mãi thành công!');
            },
            onError: (errors) => {
                message.error(errors.error || 'Xóa thất bại!');
            },
        });
    };

    // Get status color
    const getStatusColor = (status) => {
        return status === 'active' ? 'green' : 'red';
    };

    // Get discount type label
    const getDiscountTypeLabel = (type) => {
        return type === 'percent' ? 'Phần trăm' : 'Số tiền cố định';
    };

    // Check if expired
    const isExpired = (endDate) => {
        return dayjs(endDate).isBefore(dayjs());
    };

    // Table columns
    const columns = [
        {
            title: 'Mã khuyến mãi',
            dataIndex: 'code',
            key: 'code',
            width: 150,
            render: (text) => (
                <Space>
                    <GiftOutlined style={{ color: '#1890ff' }} />
                    <span style={{ fontWeight: 500, fontSize: 16, color: '#1890ff' }}>
                        {text}
                    </span>
                </Space>
            ),
        },
        {
            title: 'Mô tả',
            dataIndex: 'description',
            key: 'description',
            ellipsis: true,
        },
        {
            title: 'Loại giảm giá',
            dataIndex: 'discount_type',
            key: 'discount_type',
            width: 130,
            align: 'center',
            render: (type) => (
                <Tag color={type === 'percent' ? 'blue' : 'green'}>
                    {getDiscountTypeLabel(type)}
                </Tag>
            ),
        },
        {
            title: 'Giá trị',
            dataIndex: 'discount_value',
            key: 'discount_value',
            width: 120,
            align: 'right',
            render: (value, record) => (
                <span style={{ fontWeight: 500, color: '#f5222d' }}>
                    {record.discount_type === 'percent' 
                        ? `${value}%` 
                        : formatCurrency(value)
                    }
                </span>
            ),
        },
        {
            title: 'ĐH tối thiểu',
            dataIndex: 'min_order_value',
            key: 'min_order_value',
            width: 130,
            align: 'right',
            render: (value) => value ? formatCurrency(value) : '-',
        },
        {
            title: 'Giảm tối đa',
            dataIndex: 'max_discount',
            key: 'max_discount',
            width: 130,
            align: 'right',
            render: (value) => value ? formatCurrency(value) : '-',
        },
        {
            title: 'Lượt sử dụng',
            key: 'usage',
            width: 130,
            align: 'center',
            render: (_, record) => (
                <div>
                    <div style={{ fontWeight: 500 }}>
                        {record.used_count} / {record.usage_limit || '∞'}
                    </div>
                    {record.usage_limit && (
                        <div style={{ fontSize: 11, color: '#999' }}>
                            Còn: {record.usage_limit - record.used_count}
                        </div>
                    )}
                </div>
            ),
        },
        {
            title: 'Thời gian',
            key: 'dates',
            width: 200,
            render: (_, record) => (
                <div>
                    <div style={{ fontSize: 12 }}>
                        <span style={{ color: '#999' }}>Từ:</span> {dayjs(record.start_date).format('DD/MM/YYYY')}
                    </div>
                    <div style={{ fontSize: 12 }}>
                        <span style={{ color: '#999' }}>Đến:</span> {dayjs(record.end_date).format('DD/MM/YYYY')}
                    </div>
                    {isExpired(record.end_date) && (
                        <Tag color="red" style={{ marginTop: 4 }}>Đã hết hạn</Tag>
                    )}
                </div>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            width: 120,
            align: 'center',
            render: (status, record) => (
                <Switch
                    checked={status === 'active'}
                    onChange={() => handleToggleStatus(record.id, status)}
                    checkedChildren={<CheckCircleOutlined />}
                    unCheckedChildren={<CloseCircleOutlined />}
                />
            ),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 210,
            align: 'center',
            render: (_, record) => (
                <Space size="small">
                    <Tooltip title="Xem chi tiết & báo cáo sử dụng">
                        <Button icon={<EyeOutlined />} size="small" onClick={() => router.visit(`/admin/promotions/${record.id}`)} />
                    </Tooltip>
                    <Tooltip title="Nhân bản thành chương trình mới">
                        <Button
                            icon={<CopyOutlined />}
                            size="small"
                            onClick={async () => {
                                try {
                                    const res = await axios.post(`/admin/promotions/${record.id}/duplicate`);
                                    message.success(`${res.data.message}: ${res.data.data.code}`);
                                    router.visit(`/admin/promotions/${res.data.data.id}/edit`);
                                } catch (err) {
                                    message.error(err.response?.data?.message || 'Không nhân bản được mã khuyến mãi');
                                }
                            }}
                        />
                    </Tooltip>
                    <Button
                        type="primary"
                        icon={<EditOutlined />}
                        size="small"
                        onClick={() => router.visit(`/admin/promotions/${record.id}/edit`)}
                    >
                        Sửa
                    </Button>
                    <Popconfirm
                        title="Xóa mã khuyến mãi?"
                        description="Bạn có chắc muốn xóa mã khuyến mãi này?"
                        onConfirm={() => handleDelete(record.id)}
                        okText="Xóa"
                        cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                    >
                        <Button
                            danger
                            icon={<DeleteOutlined />}
                            size="small"
                        />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Quản lý mã khuyến mãi" />

            <Card
                title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>🎁 Quản lý mã khuyến mãi</span>}
                extra={
                    <Button
                        type="primary"
                        icon={<PlusOutlined />}
                        size="large"
                        onClick={() => router.visit('/admin/promotions/create')}
                    >
                        Thêm mã mới
                    </Button>
                }
            >
                {/* Filters */}
                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                    <Col xs={24} md={10}>
                        <Search
                            placeholder="Tìm theo mã hoặc mô tả..."
                            allowClear
                            size="large"
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onSearch={handleSearch}
                            prefix={<SearchOutlined />}
                        />
                    </Col>
                    <Col xs={24} md={7}>
                        <Select
                            placeholder="Lọc theo trạng thái"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedStatus || undefined}
                            onChange={(value) => handleFilterChange('status', value)}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="active">Đang hoạt động</Option>
                            <Option value="inactive">Vô hiệu hóa</Option>
                        </Select>
                    </Col>
                    <Col xs={24} md={7}>
                        <Select
                            placeholder="Loại giảm giá"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedType || undefined}
                            onChange={(value) => handleFilterChange('discount_type', value)}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="percent">Phần trăm</Option>
                            <Option value="fixed">Số tiền cố định</Option>
                        </Select>
                    </Col>
                </Row>

                {/* Table */}
                <Table
                    columns={columns}
                    dataSource={promotions?.data || []}
                    rowKey="id"
                    pagination={{
                        current: promotions?.current_page || 1,
                        pageSize: promotions?.per_page || 15,
                        total: promotions?.total || 0,
                        showSizeChanger: true,
                        showTotal: (total) => `Tổng ${total} mã khuyến mãi`,
                        onChange: (page, pageSize) => {
                            router.get('/admin/promotions', {
                                ...filters,
                                page,
                                per_page: pageSize,
                            }, {
                                preserveState: true,
                                preserveScroll: true,
                            });
                        },
                    }}
                    scroll={{ x: 1500 }}
                />
            </Card>
        </AdminLayout>
    );
}