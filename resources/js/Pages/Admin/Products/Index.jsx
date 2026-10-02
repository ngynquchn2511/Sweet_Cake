// resources/js/Pages/Admin/Products/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { 
    Card, Table, Button, Input, Select, Space, Tag, Image, 
    Popconfirm, message, Row, Col, Switch, Tooltip, 
} from 'antd';
import {
    PlusOutlined, EditOutlined, DeleteOutlined, SearchOutlined,
    FilterOutlined, EyeOutlined, CopyOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';

const { Search } = Input;
const { Option } = Select;

export default function ProductIndex({ auth, products, categories, filters }) {
    const [searchText, setSearchText] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    // Format currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(value);
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/products', {
            search: value,
            category_id: selectedCategory,
            status: selectedStatus,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle filter change
    const handleFilterChange = (type, value) => {
        const params = {
            search: searchText,
            category_id: type === 'category' ? value : selectedCategory,
            status: type === 'status' ? value : selectedStatus,
        };

        if (type === 'category') setSelectedCategory(value);
        if (type === 'status') setSelectedStatus(value);

        router.get('/admin/products', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Ẩn/hiện nhanh sản phẩm ngay trên bảng
    const handleToggleStatus = async (record, checked) => {
        try {
            await axios.put(`/admin/products/${record.id}/status`, { status: checked ? 'active' : 'inactive' });
            message.success(checked ? 'Đã mở bán sản phẩm' : 'Đã ngừng bán sản phẩm');
            router.reload({ only: ['products'], preserveScroll: true });
        } catch (err) {
            message.error(err.response?.data?.message || 'Không đổi được trạng thái');
        }
    };

    // Nhân bản sản phẩm (bản sao ở trạng thái ngừng bán để chỉnh sửa trước khi mở bán)
    const handleDuplicate = async (id) => {
        try {
            const res = await axios.post(`/admin/products/${id}/duplicate`);
            message.success(res.data.message);
            router.visit(`/admin/products/${res.data.data.id}/edit`);
        } catch (err) {
            message.error(err.response?.data?.message || 'Không nhân bản được sản phẩm');
        }
    };

    // Handle delete
    const handleDelete = (id) => {
        router.delete(`/admin/products/${id}`, {
            onSuccess: () => {
                message.success('Xóa sản phẩm thành công!');
            },
            onError: () => {
                message.error('Xóa sản phẩm thất bại!');
            },
        });
    };

 const columns = [
    {
    title: 'Hình ảnh',
    dataIndex: 'image',
    key: 'image',
    width: 80,
    render: (image, record) => (
        <div style={{ 
            width: 50, 
            height: 50, 
            borderRadius: 8,
            overflow: 'hidden',
            border: '1px solid #d9d9d9',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: '#f5f5f5',
        }}>
            <img
                src={image 
                    ? `/${image}` 
                    : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="50" height="50"%3E%3Crect fill="%23f0f0f0" width="50" height="50"/%3E%3Ctext fill="%23999" x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-size="10"%3ENo%3C/text%3E%3C/svg%3E'
                }
                alt={record.name}
                style={{ 
                    width: '100%',
                    height: '100%',
                    objectFit: 'cover',
                }}
                onError={(e) => {
                    e.target.style.display = 'none';
                    e.target.parentElement.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;font-size:10px;">❌</div>';
                }}
            />
        </div>
    ),
},
    {
        title: 'Tên sản phẩm',
        dataIndex: 'name',
        key: 'name',
        width: 200,  // ← THÊM width
        render: (text, record) => (
            <div>
                <div style={{ fontWeight: 500 }}>{text}</div>
                <div style={{ fontSize: 12, color: '#999' }}>SKU: {record.slug}</div>
            </div>
        ),
    },
    {
        title: 'Danh mục',
        dataIndex: 'category',
        key: 'category',
        width: 120,  // ← THÊM width
        render: (category) => category?.name || '-',
    },
    {
    title: 'Giá',
    dataIndex: 'price',
    key: 'price',
    align: 'right',
    width: 130,
    render: (price, record) => (
        <div>
            {/* Nếu có giá khuyến mãi */}
            {record.discount_price ? (
                <>
                    <div style={{ fontWeight: 500, color: '#ff4d4f' }}>
                        {formatCurrency(record.discount_price)}
                    </div>
                    <div style={{ fontSize: 12, color: '#999', textDecoration: 'line-through' }}>
                        {formatCurrency(price)}
                    </div>
                </>
            ) : (
                /* Không có khuyến mãi - hiện giá gốc */
                <div style={{ fontWeight: 500 }}>
                    {formatCurrency(price)}
                </div>
            )}
        </div>
    ),
},
    {
        title: 'Kho',  // ← Rút ngắn title
        dataIndex: 'stock',
        key: 'stock',
        align: 'center',
        width: 80,  // ← THÊM width
        render: (stock) => (
            <Tag color={stock === 0 ? 'red' : stock <= 10 ? 'orange' : 'green'}>
                {stock}
            </Tag>
        ),
    },
    {
        title: 'Trạng thái',
        dataIndex: 'status',
        key: 'status',
        align: 'center',
        width: 110,  // ← THÊM width
        render: (status, record) => (
            <Tooltip title={status === 'active' ? 'Đang bán - bấm để ngừng bán' : 'Ngừng bán - bấm để mở bán'}>
                <Switch
                    size="small"
                    checked={status === 'active'}
                    checkedChildren="Bán"
                    unCheckedChildren="Ngừng"
                    onChange={(checked) => handleToggleStatus(record, checked)}
                />
            </Tooltip>
        ),
    },
    {
        title: 'Thao tác',
        key: 'actions',
        align: 'center',
        width: 170,
        fixed: 'right', // luôn hiển thị nút thao tác kể cả khi bảng cuộn ngang
        render: (_, record) => (
            <Space size="small">
                <Tooltip title="Xem chi tiết">
                    <Button icon={<EyeOutlined />} size="small" onClick={() => router.visit(`/admin/products/${record.id}`)} />
                </Tooltip>
                <Button
                    type="primary"
                    icon={<EditOutlined />}
                    size="small"
                    onClick={() => router.visit(`/admin/products/${record.id}/edit`)}
                />
                <Tooltip title="Nhân bản">
                    <Button icon={<CopyOutlined />} size="small" onClick={() => handleDuplicate(record.id)} />
                </Tooltip>
                <Popconfirm
                    title="Xóa?"
                    onConfirm={() => handleDelete(record.id)}
                    okText="Xóa"
                    cancelText="Hủy"
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
            <Head title="Quản lý sản phẩm" />

            <Card
                title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>Quản lý sản phẩm</span>}
                extra={
                    <Button
                        type="primary"
                        icon={<PlusOutlined />}
                        size="large"
                        onClick={() => router.visit('/admin/products/create')}
                    >
                        Thêm sản phẩm
                    </Button>
                }
            >
                {/* Filters */}
                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                    <Col xs={24} md={8}>
                        <Search
                            placeholder="Tìm theo tên, SKU..."
                            allowClear
                            size="large"
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onSearch={handleSearch}
                            prefix={<SearchOutlined />}
                        />
                    </Col>
                    <Col xs={24} md={8}>
                        <Select
                            placeholder="Lọc theo danh mục"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedCategory || undefined}
                            onChange={(value) => handleFilterChange('category', value)}
                            suffixIcon={<FilterOutlined />}
                        >
                            {categories.map(cat => (
                                <Option key={cat.id} value={cat.id}>{cat.name}</Option>
                            ))}
                        </Select>
                    </Col>
                    <Col xs={24} md={8}>
                        <Select
                            placeholder="Lọc theo trạng thái"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedStatus || undefined}
                            onChange={(value) => handleFilterChange('status', value)}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="active">Đang bán</Option>
                            <Option value="inactive">Ngừng bán</Option>
                        </Select>
                    </Col>
                </Row>

                {/* Table */}
                <Table
                    columns={columns}
                    dataSource={products.data}
                    rowKey="id"
                    pagination={{
                        current: products.current_page,
                        pageSize: products.per_page,
                        total: products.total,
                        showSizeChanger: true,
                        showTotal: (total) => `Tổng ${total} sản phẩm`,
                        onChange: (page, pageSize) => {
                            router.get('/admin/products', {
                                ...filters,
                                page,
                                per_page: pageSize,
                            }, {
                                preserveState: true,
                                preserveScroll: true,
                            });
                        },
                    }}
                    scroll={{ x: 1200 }}
                />
            </Card>
        </AdminLayout>
    );
}