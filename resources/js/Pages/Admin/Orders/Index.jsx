// resources/js/Pages/Admin/Orders/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { 
    Card, Table, Button, Input, Select, Space, Tag, DatePicker, 
    Row, Col 
} from 'antd';
import {
    EyeOutlined, SearchOutlined, FilterOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const { Search } = Input;
const { Option } = Select;
const { RangePicker } = DatePicker;

export default function OrderIndex({ auth, orders, filters = {} }) {
    const [searchText, setSearchText] = useState(filters?.search || '');
    const [selectedOrderStatus, setSelectedOrderStatus] = useState(filters?.order_status || '');
    const [selectedPaymentStatus, setSelectedPaymentStatus] = useState(filters?.payment_status || '');

    // Format currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(value);
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/orders', {
            search: value,
            order_status: selectedOrderStatus,
            payment_status: selectedPaymentStatus,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle filter change
    const handleFilterChange = (type, value) => {
        const params = {
            search: searchText,
            order_status: type === 'order_status' ? value : selectedOrderStatus,
            payment_status: type === 'payment_status' ? value : selectedPaymentStatus,
        };

        if (type === 'order_status') setSelectedOrderStatus(value);
        if (type === 'payment_status') setSelectedPaymentStatus(value);

        router.get('/admin/orders', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle date range filter
    const handleDateRangeChange = (dates) => {
        const params = {
            search: searchText,
            order_status: selectedOrderStatus,
            payment_status: selectedPaymentStatus,
        };

        if (dates) {
            params.from_date = dates[0].format('YYYY-MM-DD');
            params.to_date = dates[1].format('YYYY-MM-DD');
        }

        router.get('/admin/orders', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Order status colors
    const getOrderStatusColor = (status) => {
        const colors = {
            pending: 'orange',
            processing: 'blue',
            shipping: 'cyan',
            completed: 'green',
            cancelled: 'red',
        };
        return colors[status] || 'default';
    };

    // Order status labels
    const getOrderStatusLabel = (status) => {
        const labels = {
            pending: 'Chờ xử lý',
            processing: 'Đang xử lý',
            shipping: 'Đang giao',
            completed: 'Hoàn thành',
            cancelled: 'Đã hủy',
        };
        return labels[status] || status;
    };

    // Payment status colors
    const getPaymentStatusColor = (status) => {
        const colors = {
            pending: 'orange',
            paid: 'green',
            failed: 'red',
            refunded: 'purple',
        };
        return colors[status] || 'default';
    };

    // Payment status labels
    const getPaymentStatusLabel = (status) => {
        const labels = {
            pending: 'Chờ thanh toán',
            paid: 'Đã thanh toán',
            failed: 'Thất bại',
            refunded: 'Đã hoàn tiền',
        };
        return labels[status] || status;
    };

    // Table columns
    const columns = [
        {
            title: 'Mã đơn',
            dataIndex: 'order_code',
            key: 'order_code',
            width: 150,
            render: (text) => (
                <span style={{ fontWeight: 500, color: '#1890ff' }}>{text}</span>
            ),
        },
        {
            title: 'Khách hàng',
            dataIndex: 'user',
            key: 'user',
            width: 200,
            render: (user) => (
                <div>
                    <div style={{ fontWeight: 500 }}>{user?.full_name}</div>
                    <div style={{ fontSize: 12, color: '#999' }}>{user?.phone}</div>
                </div>
            ),
        },
        {
            title: 'Sản phẩm',
            dataIndex: 'order_items',
            key: 'items',
            width: 100,
            align: 'center',
            render: (items) => (
                <Tag color="blue">{items?.length || 0} SP</Tag>
            ),
        },
        {
            title: 'Tổng tiền',
            dataIndex: 'total_amount',
            key: 'total_amount',
            width: 150,
            align: 'right',
            render: (amount) => (
                <span style={{ fontWeight: 500, color: '#f5222d' }}>
                    {formatCurrency(amount)}
                </span>
            ),
        },
        {
            title: 'Trạng thái đơn',
            dataIndex: 'order_status',
            key: 'order_status',
            width: 130,
            align: 'center',
            render: (status) => (
                <Tag color={getOrderStatusColor(status)}>
                    {getOrderStatusLabel(status)}
                </Tag>
            ),
        },
        {
            title: 'Thanh toán',
            dataIndex: 'payment_status',
            key: 'payment_status',
            width: 130,
            align: 'center',
            render: (status) => (
                <Tag color={getPaymentStatusColor(status)}>
                    {getPaymentStatusLabel(status)}
                </Tag>
            ),
        },
        {
            title: 'Ngày đặt',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 120,
            render: (date) => dayjs(date).format('DD/MM/YYYY'),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 100,
            align: 'center',
            render: (_, record) => (
                <Button
                    type="primary"
                    icon={<EyeOutlined />}
                    size="small"
                    onClick={() => router.visit(`/admin/orders/${record.id}`)}
                >
                    Xem
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Quản lý đơn hàng" />

            <Card
                title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>📦 Quản lý đơn hàng</span>}
            >
                {/* Filters */}
                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                    <Col xs={24} md={8}>
                        <Search
                            placeholder="Tìm mã đơn, khách hàng..."
                            allowClear
                            size="large"
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onSearch={handleSearch}
                            prefix={<SearchOutlined />}
                        />
                    </Col>
                    <Col xs={24} md={5}>
                        <Select
                            placeholder="Trạng thái đơn"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedOrderStatus || undefined}
                            onChange={(value) => handleFilterChange('order_status', value)}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="pending">Chờ xử lý</Option>
                            <Option value="processing">Đang xử lý</Option>
                            <Option value="shipping">Đang giao</Option>
                            <Option value="completed">Hoàn thành</Option>
                            <Option value="cancelled">Đã hủy</Option>
                        </Select>
                    </Col>
                    <Col xs={24} md={5}>
                        <Select
                            placeholder="Thanh toán"
                            size="large"
                            style={{ width: '100%' }}
                            allowClear
                            value={selectedPaymentStatus || undefined}
                            onChange={(value) => handleFilterChange('payment_status', value)}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="pending">Chờ thanh toán</Option>
                            <Option value="paid">Đã thanh toán</Option>
                            <Option value="failed">Thất bại</Option>
                            <Option value="refunded">Đã hoàn tiền</Option>
                        </Select>
                    </Col>
                    <Col xs={24} md={6}>
                        <RangePicker
                            size="large"
                            style={{ width: '100%' }}
                            placeholder={['Từ ngày', 'Đến ngày']}
                            format="DD/MM/YYYY"
                            onChange={handleDateRangeChange}
                        />
                    </Col>
                </Row>

                {/* Table */}
                <Table
                    columns={columns}
                    dataSource={orders?.data || []}
                    rowKey="id"
                    pagination={{
                        current: orders?.current_page || 1,
                        pageSize: orders?.per_page || 15,
                        total: orders?.total || 0,
                        showSizeChanger: true,
                        showTotal: (total) => `Tổng ${total} đơn hàng`,
                        onChange: (page, pageSize) => {
                            router.get('/admin/orders', {
                                ...filters,
                                page,
                                per_page: pageSize,
                            }, {
                                preserveState: true,
                                preserveScroll: true,
                            });
                        },
                    }}
                    scroll={{ x: 1300 }}
                />
            </Card>
        </AdminLayout>
    );
}