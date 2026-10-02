// resources/js/Pages/Admin/Categories/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import {
    Card, Table, Button, Input, Select, Space, Tag,
    Popconfirm, message, Row, Col, Tooltip,
} from 'antd';
import {
    PlusOutlined, EditOutlined, DeleteOutlined, SearchOutlined,
    FilterOutlined, AppstoreOutlined, EyeOutlined, ArrowUpOutlined, ArrowDownOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';

const { Search } = Input;
const { Option } = Select;

export default function CategoryIndex({ auth, categories, filters }) {
    const [searchText, setSearchText] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/categories', {
            search: value,
            status: selectedStatus,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle filter change
    const handleFilterChange = (value) => {
        setSelectedStatus(value);
        router.get('/admin/categories', {
            search: searchText,
            status: value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle delete
    const handleDelete = (id) => {
        router.delete(`/admin/categories/${id}`, {
            onSuccess: () => {
                message.success('Xóa danh mục thành công!');
            },
            onError: (errors) => {
                message.error(errors.error || 'Xóa danh mục thất bại!');
            },
        });
    };

    // Sắp xếp lại thứ tự hiển thị: đổi chỗ với danh mục liền trên/dưới rồi lưu display_order mới
    const handleMove = async (index, direction) => {
        const list = [...categories.data];
        const target = index + direction;
        if (target < 0 || target >= list.length) return;
        [list[index], list[target]] = [list[target], list[index]];

        const base = (categories.from || 1) - 1;
        try {
            await axios.put('/admin/categories/update-order', {
                categories: list.map((c, i) => ({ id: c.id, display_order: base + i })),
            });
            message.success('Đã lưu thứ tự hiển thị mới');
            router.get('/admin/categories', { ...filters, sort_by: 'display_order', sort_order: 'asc' }, { preserveScroll: true });
        } catch (err) {
            message.error(err.response?.data?.message || 'Không lưu được thứ tự');
        }
    };

    // Table columns
    const columns = [
        {
            title: 'Thứ tự',
            dataIndex: 'display_order',
            key: 'display_order',
            align: 'center',
            width: 110,
            render: (order, record, index) => (
                <Space size={4}>
                    <Button size="small" icon={<ArrowUpOutlined />} aria-label="Lên" disabled={index === 0} onClick={() => handleMove(index, -1)} />
                    <span style={{ minWidth: 20, display: 'inline-block' }}>{order ?? 0}</span>
                    <Button size="small" icon={<ArrowDownOutlined />} aria-label="Xuống" disabled={index === categories.data.length - 1} onClick={() => handleMove(index, 1)} />
                </Space>
            ),
        },
        {
            title: 'Hình ảnh',
            dataIndex: 'image',
            key: 'image',
            width: 100,
            render: (image, record) => (
                <div style={{ 
                    width: 60, 
                    height: 60, 
                    borderRadius: 8,
                    overflow: 'hidden',
                    border: '1px solid #d9d9d9',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: '#fafafa',
                }}>
                    {image ? (
                        <img
                            src={`/${image}?t=${Date.now()}`}
                            alt={record.name}
                            style={{ 
                                width: '100%',
                                height: '100%',
                                objectFit: 'cover',
                            }}
                            onError={(e) => {
                                e.target.style.display = 'none';
                                e.target.parentElement.innerHTML = '<div style="font-size:24px">📁</div>';
                            }}
                        />
                    ) : (
                        <AppstoreOutlined style={{ fontSize: 24, color: '#999' }} />
                    )}
                </div>
            ),
        },
        {
    title: 'Tên danh mục',
    dataIndex: 'name',
    key: 'name',
    render: (text, record) => (
        <div>
            <a 
                onClick={() => router.visit(`/admin/products?category_id=${record.id}`)}
                style={{ 
                    fontWeight: 500, 
                    fontSize: 15,
                    cursor: 'pointer',
                    color: '#1890ff',
                }}
            >
                {text}
            </a>
            <div style={{ fontSize: 12, color: '#999' }}>Slug: {record.slug}</div>
        </div>
    ),
},
        {
            title: 'Mô tả',
            dataIndex: 'description',
            key: 'description',
            render: (text) => (
                <div style={{ 
                    maxWidth: 300,
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                }}>
                    {text || '-'}
                </div>
            ),
        },
        {
    title: 'Số sản phẩm',
    dataIndex: 'products_count',
    key: 'products_count',
    align: 'center',
    render: (count) => <Tag color="blue">{count} SP</Tag>,
},
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            align: 'center',
            width: 120,
            render: (status) => (
                <Tag color={status === 'active' ? 'green' : 'red'}>
                    {status === 'active' ? 'Hoạt động' : 'Tạm ngưng'}
                </Tag>
            ),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            align: 'center',
            width: 190,
            render: (_, record) => (
                <Space size="small">
                    <Tooltip title="Xem chi tiết">
                        <Button icon={<EyeOutlined />} size="small" onClick={() => router.visit(`/admin/categories/${record.id}`)} />
                    </Tooltip>
                    <Button
                        type="primary"
                        icon={<EditOutlined />}
                        size="small"
                        onClick={() => router.visit(`/admin/categories/${record.id}/edit`)}
                    >
                        Sửa
                    </Button>
                    <Popconfirm
                        title="Xóa danh mục?"
                        description="Bạn có chắc muốn xóa danh mục này?"
                        onConfirm={() => handleDelete(record.id)}
                        okText="Xóa"
                        cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                    >
                        <Button danger icon={<DeleteOutlined />} size="small">
                            Xóa
                        </Button>
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Quản lý danh mục" />

            <Card
                bordered
                bodyStyle={{ padding: 24 }}
                style={{
                    borderRadius: 10,
                    borderColor: '#e8e8e8',
                    boxShadow: 'none',
                }}
                title={<span style={{ fontSize: 20, fontWeight: 700 }}>📁 Quản lý danh mục</span>}
                extra={
                    <Button
                        type="primary"
                        icon={<PlusOutlined />}
                        size="large"
                        onClick={() => router.visit('/admin/categories/create')}
                        style={{
                            height: 44,
                            padding: '0 18px',
                            borderRadius: 9,
                            fontSize: 16,
                            boxShadow: 'none',
                        }}
                    >
                        Thêm danh mục
                    </Button>
                }
                headStyle={{
                    minHeight: 60,
                    padding: '0 28px',
                    borderBottom: '1px solid #ededed',
                }}
            >
                {/* Filters */}
                <Row gutter={[16, 16]} style={{ margin: '0 0 16px', padding: '0 2px' }}>
                    <Col xs={24} md={12}>
                        <Search
                            placeholder="Tìm theo tên, slug..."
                            allowClear
                            size="large"
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onSearch={handleSearch}
                            prefix={<SearchOutlined />}
                            style={{ borderRadius: 9 }}
                        />
                    </Col>
                    <Col xs={24} md={12}>
                        <Select
                            placeholder="Lọc theo trạng thái"
                            size="large"
                            style={{ width: '100%', borderRadius: 9 }}
                            allowClear
                            value={selectedStatus || undefined}
                            onChange={handleFilterChange}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="active">Hoạt động</Option>
                            <Option value="inactive">Tạm ngưng</Option>
                        </Select>
                    </Col>
                </Row>

                {/* Table */}
                <Table
                    columns={columns}
                    dataSource={categories.data}
                    rowKey="id"
                    bordered={false}
                    size="middle"
                    style={{ borderRadius: 8, overflow: 'hidden' }}
                    pagination={{
                        current: categories.current_page,
                        pageSize: categories.per_page,
                        total: categories.total,
                        showSizeChanger: true,
                        showTotal: (total) => `Tổng ${total} danh mục`,
                        onChange: (page, pageSize) => {
                            router.get('/admin/categories', {
                                ...filters,
                                page,
                                per_page: pageSize,
                            }, {
                                preserveState: true,
                                preserveScroll: true,
                            });
                        },
                    }}
                />
            </Card>
        </AdminLayout>
    );
}