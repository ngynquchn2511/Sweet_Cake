import { Head, router } from '@inertiajs/react';
import { Card, Table, Button, Row, Col, Statistic, Tag, Space, message } from 'antd';
import { ShoppingCartOutlined, ClockCircleOutlined, DollarOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';

export default function CartAnalyticsIndex({ auth, abandonedProducts, inactiveCartProducts, stats }) {
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(value);
    };

    const handleMarkAbandoned = () => {
        router.post('/admin/cart-analytics/mark-abandoned', {}, {
            onSuccess: () => message.success('Đã đánh dấu cart items cũ thành abandoned'),
        });
    };

    const handleSuggestDiscounts = () => {
        router.get('/admin/cart-analytics/suggest-discounts');
    };

    const abandonedColumns = [
        {
            title: 'Sản phẩm',
            key: 'product',
            render: (_, record) => (
                <Space>
                    {record.image && (
                        <img
                            src={`/${record.image}`}
                            alt={record.name}
                            style={{ width: 50, height: 50, objectFit: 'cover', borderRadius: 4 }}
                        />
                    )}
                    <div>
                        <div style={{ fontWeight: 500 }}>{record.name}</div>
                        <div style={{ fontSize: 12, color: '#999' }}>
                            Giá: {formatCurrency(record.discount_price || record.price)}
                        </div>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Số lần bỏ giỏ',
            dataIndex: 'abandoned_count',
            key: 'abandoned_count',
            align: 'center',
            sorter: (a, b) => a.abandoned_count - b.abandoned_count,
            render: (count) => (
                <Tag color={count > 10 ? 'red' : count > 5 ? 'orange' : 'default'}>
                    {count} lần
                </Tag>
            ),
        },
        {
            title: 'Tổng SL bỏ giỏ',
            dataIndex: 'total_quantity_abandoned',
            key: 'total_quantity_abandoned',
            align: 'center',
        },
        {
            title: 'Gợi ý',
            key: 'suggestion',
            align: 'center',
            render: (_, record) => {
                if (record.abandoned_count > 10 && !record.discount_price) {
                    return <Tag color="red">Nên giảm giá</Tag>;
                }
                return <Tag color="green">OK</Tag>;
            },
        },
    ];

    const inactiveColumns = [
        {
            title: 'Sản phẩm',
            key: 'product',
            render: (_, record) => (
                <Space>
                    {record.image && (
                        <img
                            src={`/${record.image}`}
                            alt={record.name}
                            style={{ width: 50, height: 50, objectFit: 'cover', borderRadius: 4 }}
                        />
                    )}
                    <div>
                        <div style={{ fontWeight: 500 }}>{record.name}</div>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Đang trong giỏ',
            dataIndex: 'inactive_count',
            key: 'inactive_count',
            align: 'center',
            sorter: (a, b) => a.inactive_count - b.inactive_count,
            render: (count) => <Tag color="orange">{count} user</Tag>,
        },
        {
            title: 'Tổng SL chờ',
            dataIndex: 'total_quantity_inactive',
            key: 'total_quantity_inactive',
            align: 'center',
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Phân tích giỏ hàng" />

            <Card
                title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>🛒 Phân tích giỏ hàng</span>}
                extra={
                    <Space>
                        <Button onClick={handleSuggestDiscounts}>
                            Gợi ý giảm giá
                        </Button>
                        <Button type="primary" onClick={handleMarkAbandoned}>
                            Đánh dấu giỏ cũ
                        </Button>
                    </Space>
                }
            >
                {/* 3 card mới */}
                <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                    <Col xs={24} sm={12} md={8}>
                        <Card>
                            <Statistic
                                title="Sản phẩm đang trong giỏ (chưa mua)"
                                value={stats.total_products_in_cart}
                                prefix={<ShoppingCartOutlined />}
                                valueStyle={{ color: '#1890ff' }}
                            />
                        </Card>
                    </Col>
                    <Col xs={24} sm={12} md={8}>
                        <Card>
                            <Statistic
                                title="Sản phẩm nằm giỏ > 7 ngày"
                                value={stats.products_in_cart_over_7days}
                                prefix={<ClockCircleOutlined />}
                                valueStyle={{ color: '#fa8c16' }}
                            />
                        </Card>
                    </Col>
                    <Col xs={24} sm={12} md={8}>
                        <Card>
                            <Statistic
                                title="Giá trị sản phẩm trong giỏ"
                                value={stats.total_value_in_cart}
                                prefix={<DollarOutlined />}
                                formatter={(value) => formatCurrency(value)}
                                valueStyle={{ color: '#52c41a' }}
                            />
                        </Card>
                    </Col>
                </Row>

                {/* Abandoned Products Table */}
                <Card title="⚠️ Sản phẩm bị bỏ giỏ nhiều nhất" style={{ marginBottom: 16 }}>
                    <Table
                        columns={abandonedColumns}
                        dataSource={abandonedProducts}
                        rowKey="id"
                        pagination={{ pageSize: 10 }}
                    />
                </Card>

                {/* Inactive Cart Products Table */}
                <Card title="🕐 Sản phẩm đang nằm trong giỏ (>7 ngày)">
                    <Table
                        columns={inactiveColumns}
                        dataSource={inactiveCartProducts}
                        rowKey="id"
                        pagination={{ pageSize: 10 }}
                    />
                </Card>
            </Card>
        </AdminLayout>
    );
}