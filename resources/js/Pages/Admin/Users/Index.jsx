// resources/js/Pages/Admin/Users/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { useEffect } from 'react';
import { 
    Card, Table, Button, Input, Select, Space, Tag, Avatar, 
    Popconfirm, message, Row, Col, Modal, Statistic, 
} from 'antd';
import {
    EyeOutlined, SearchOutlined, FilterOutlined, UserOutlined,
    LockOutlined, UnlockOutlined, DeleteOutlined, DownloadOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const { Search } = Input;
const { Option } = Select;

export default function UserIndex({ auth, users, filters = {} }) {
    const [searchText, setSearchText] = useState(filters?.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');

    // Format currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(value);
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/users', {
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
        router.get('/admin/users', {
            search: searchText,
            status: value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle ban/unban user
    const handleUpdateStatus = (id, currentStatus) => {
        const newStatus = currentStatus === 'active' ? 'banned' : 'active';
        const actionText = newStatus === 'banned' ? 'chặn' : 'mở khóa';

        Modal.confirm({
            title: `Xác nhận ${actionText}`,
            content: `Bạn có chắc muốn ${actionText} tài khoản này?`,
            okText: 'Xác nhận',
            cancelText: 'Hủy',
            okButtonProps: { danger: newStatus === 'banned' },
            onOk: () => {
                router.put(`/admin/users/${id}/status`, {
                    status: newStatus,
                }, {
                    preserveState: false,
                    onSuccess: () => {
                        message.success(`${actionText === 'chặn' ? 'Chặn' : 'Mở khóa'} tài khoản thành công!`);
                    },
                    onError: () => {
                        message.error('Có lỗi xảy ra!');
                    },
                });
            },
        });
    };

    // Handle delete user
    const handleDelete = (id) => {
        router.delete(`/admin/users/${id}`, {
            onSuccess: () => {
                message.success('Xóa khách hàng thành công!');
            },
            onError: (errors) => {
                message.error(errors.error || 'Xóa khách hàng thất bại!');
            },
        });
    };

    // Table columns
    const columns = [
        {
            title: 'Khách hàng',
            dataIndex: 'full_name',
            key: 'full_name',
            render: (text, record) => (
                <Space>
                    <Avatar 
                        size={40} 
                        icon={<UserOutlined />}
                        style={{ backgroundColor: '#1890ff' }}
                    >
                        {text?.charAt(0).toUpperCase()}
                    </Avatar>
                    <div>
                        <div style={{ fontWeight: 500 }}>{text}</div>
                        <div style={{ fontSize: 12, color: '#999' }}>{record.email}</div>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Số điện thoại',
            dataIndex: 'phone',
            key: 'phone',
            width: 110,
        },
        {
            title: 'Địa chỉ',
            dataIndex: 'address',
            key: 'address',
            render: (text) => text || '-',
            ellipsis: true,
        },
        {
            title: 'Số đơn',
            dataIndex: 'total_orders',
            key: 'total_orders',
            width: 100,
            align: 'center',
            render: (count) => (
                <Tag color="blue">{count || 0}</Tag>
            ),
        },
        {
            title: 'Tổng chi tiêu',
            dataIndex: 'total_spent',
            key: 'total_spent',
            width: 120,
            align: 'right',
            render: (amount) => (
                <span style={{ fontWeight: 500 }}>
                    {formatCurrency(amount || 0)}
                </span>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            width: 120,
            align: 'center',
            render: (status) => (
                <Tag color={status === 'active' ? 'green' : 'red'}>
                    {status === 'active' ? 'Hoạt động' : 'Đã chặn'}
                </Tag>
            ),
        },
        {
            title: 'Ngày tham gia',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 120,
            render: (date) => dayjs(date).format('DD/MM/YYYY'),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 200,
            align: 'center',
            render: (_, record) => (
                <Space size="small">
                    <Button
                        type="primary"
                        icon={<EyeOutlined />}
                        size="small"
                        onClick={() => router.visit(`/admin/users/${record.id}`)}
                    >
                        Xem
                    </Button>
                    <Button
                        icon={record.status === 'active' ? <LockOutlined /> : <UnlockOutlined />}
                        size="small"
                        danger={record.status === 'active'}
                        onClick={() => handleUpdateStatus(record.id, record.status)}
                    >
                        {record.status === 'active' ? 'Chặn' : 'Mở'}
                    </Button>
                    {record.total_orders === 0 && (
                        <Popconfirm
                            title="Xóa khách hàng?"
                            description="Bạn có chắc muốn xóa khách hàng này?"
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
                    )}
                </Space>
            ),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Quản lý khách hàng" />

            <UserStatistics />

            <Card
                title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>👥 Quản lý khách hàng</span>}
                extra={
                    <Button icon={<DownloadOutlined />} href="/admin/users/export">
                        Xuất Excel (CSV)
                    </Button>
                }
            >
                {/* Filters */}
                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                    <Col xs={24} md={12}>
                        <Search
                            placeholder="Tìm theo tên, email, SĐT..."
                            allowClear
                            size="large"
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onSearch={handleSearch}
                            prefix={<SearchOutlined />}
                        />
                    </Col>
                    <Col xs={24} md={12}>
                        <Select
                            placeholder="Lọc theo trạng thái"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedStatus || undefined}
                            onChange={handleFilterChange}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="active">Hoạt động</Option>
                            <Option value="banned">Đã chặn</Option>
                        </Select>
                    </Col>
                </Row>

                {/* Table */}
                <Table
                    columns={columns}
                    dataSource={users?.data || []}
                    rowKey="id"
                    pagination={{
                        current: users?.current_page || 1,
                        pageSize: users?.per_page || 15,
                        total: users?.total || 0,
                        showSizeChanger: true,
                        showTotal: (total) => `Tổng ${total} khách hàng`,
                        onChange: (page, pageSize) => {
                            router.get('/admin/users', {
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

// Thống kê tổng quan khách hàng (gọi route statistics)
function UserStatistics() {
    const [stats, setStats] = useState(null);

    useEffect(() => {
        axios.get('/admin/users/statistics').then((res) => setStats(res.data)).catch(() => setStats({}));
    }, []);

    const items = [
        ['Tổng khách hàng', stats?.total_customers],
        ['Đang hoạt động', stats?.active_customers],
        ['Đã bị khoá', stats?.banned_customers],
        ['Đã từng mua hàng', stats?.customers_with_orders],
    ];

    return (
        <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
            {items.map(([title, value]) => (
                <Col xs={12} md={6} key={title}>
                    <Card><Statistic title={title} value={value ?? 0} loading={stats === null} /></Card>
                </Col>
            ))}
        </Row>
    );
}
