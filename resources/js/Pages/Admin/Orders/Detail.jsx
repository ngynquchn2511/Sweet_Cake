// resources/js/Pages/Admin/Orders/Detail.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { 
    Card, Descriptions, Table, Button, Select, Space, Tag, 
    Timeline, Modal, message, Row, Col, Divider 
} from 'antd';
import {
    ArrowLeftOutlined, PrinterOutlined, CheckCircleOutlined,
    ClockCircleOutlined, SyncOutlined, CloseCircleOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const { Option } = Select;

export default function OrderDetail({ auth, order }) {
    const [updating, setUpdating] = useState(false);
    const [selectedStatus, setSelectedStatus] = useState(order.order_status);

    // Format currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(value);
    };

    // Handle update order status
    const handleUpdateStatus = () => {
        if (selectedStatus === order.order_status) {
            message.warning('Vui lòng chọn trạng thái khác!');
            return;
        }

        Modal.confirm({
            title: 'Xác nhận cập nhật',
            content: `Bạn có chắc muốn cập nhật trạng thái đơn hàng?`,
            okText: 'Cập nhật',
            cancelText: 'Hủy',
            onOk: () => {
                setUpdating(true);
                router.put(`/admin/orders/${order.id}/status`, {
                    order_status: selectedStatus,
                }, {
                    preserveState: false,
                    onSuccess: () => {
                        message.success('Cập nhật trạng thái thành công!');
                    },
                    onError: () => {
                        message.error('Cập nhật thất bại!');
                    },
                    onFinish: () => {
                        setUpdating(false);
                    },
                });
            },
        });
    };

    // Order status config
    const statusConfig = {
        pending: { label: 'Chờ xử lý', color: 'orange', icon: <ClockCircleOutlined /> },
        processing: { label: 'Đang xử lý', color: 'blue', icon: <SyncOutlined spin /> },
        shipping: { label: 'Đang giao', color: 'cyan', icon: <SyncOutlined spin /> },
        completed: { label: 'Hoàn thành', color: 'green', icon: <CheckCircleOutlined /> },
        cancelled: { label: 'Đã hủy', color: 'red', icon: <CloseCircleOutlined /> },
    };

    // Payment status config
    const paymentConfig = {
        pending: { label: 'Chờ thanh toán', color: 'orange' },
        paid: { label: 'Đã thanh toán', color: 'green' },
        failed: { label: 'Thất bại', color: 'red' },
        refunded: { label: 'Đã hoàn tiền', color: 'purple' },
    };

    // Product columns
    const columns = [
        {
            title: 'Sản phẩm',
            dataIndex: 'product',
            key: 'product',
            render: (product, record) => (
                <Space>
                    <div style={{
                        width: 50,
                        height: 50,
                        borderRadius: 8,
                        overflow: 'hidden',
                        border: '1px solid #d9d9d9',
                    }}>
                        {product?.image ? (
                            <img
                                src={`/${product.image}`}
                                alt={product.name}
                                style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                onError={(e) => {
                                    e.target.style.display = 'none';
                                    e.target.parentElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:20px;">🍰</div>';
                                }}
                            />
                        ) : (
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '100%', fontSize: 20 }}>
                                🍰
                            </div>
                        )}
                    </div>
                    <div>
                        <div style={{ fontWeight: 500 }}>{product?.name}</div>
                        <div style={{ fontSize: 12, color: '#999' }}>
                            {formatCurrency(record.price)} x {record.quantity}
                        </div>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Đơn giá',
            dataIndex: 'price',
            key: 'price',
            align: 'right',
            render: (price) => formatCurrency(price),
        },
        {
            title: 'Số lượng',
            dataIndex: 'quantity',
            key: 'quantity',
            align: 'center',
        },
        {
            title: 'Thành tiền',
            dataIndex: 'subtotal',
            key: 'subtotal',
            align: 'right',
            render: (subtotal) => (
                <span style={{ fontWeight: 500 }}>{formatCurrency(subtotal)}</span>
            ),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Đơn hàng #${order.order_code}`} />

            <Space orientation="vertical" size="large" style={{ width: '100%' }}>
                {/* Header */}
                <Card>
                    <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                        <Space>
                            <Button
                                icon={<ArrowLeftOutlined />}
                                onClick={() => router.visit('/admin/orders')}
                            >
                                Quay lại
                            </Button>
                            <span style={{ fontSize: 20, fontWeight: 'bold' }}>
                                Đơn hàng #{order.order_code}
                            </span>
                        </Space>
                        <Button icon={<PrinterOutlined />}>
                            In hóa đơn
                        </Button>
                    </Space>
                </Card>

                <Row gutter={[16, 16]}>
                    {/* Left Column */}
                    <Col xs={24} lg={16}>
                        {/* Order Items */}
                        <Card title="Sản phẩm" style={{ marginBottom: 16 }}>
                            <Table
                                columns={columns}
                                dataSource={order.order_items}
                                rowKey="id"
                                pagination={false}
                            />
                            <Divider />
                            <div style={{ textAlign: 'right' }}>
                                <Space orientation="vertical" size="small" style={{ alignItems: 'flex-end' }}>
                                    <div>
                                        <span>Tạm tính: </span>
                                        <span style={{ fontWeight: 500 }}>
                                            {formatCurrency(order.subtotal)}
                                        </span>
                                    </div>
                                    {order.shipping_fee > 0 && (
                                        <div>
                                            <span>Phí vận chuyển: </span>
                                            <span style={{ fontWeight: 500 }}>
                                                {formatCurrency(order.shipping_fee)}
                                            </span>
                                        </div>
                                    )}
                                    {order.discount_amount > 0 && (
                                        <div>
                                            <span>Giảm giá: </span>
                                            <span style={{ fontWeight: 500, color: '#52c41a' }}>
                                                -{formatCurrency(order.discount_amount)}
                                            </span>
                                        </div>
                                    )}
                                    <div style={{ fontSize: 18 }}>
                                        <span>Tổng cộng: </span>
                                        <span style={{ fontWeight: 'bold', color: '#f5222d' }}>
                                            {formatCurrency(order.total_amount)}
                                        </span>
                                    </div>
                                </Space>
                            </div>
                        </Card>

                        {/* Customer Info */}
                        <Card title="Thông tin khách hàng">
                            <Descriptions column={1}>
                                <Descriptions.Item label="Họ tên">
                                    {order.user?.full_name}
                                </Descriptions.Item>
                                <Descriptions.Item label="Email">
                                    {order.user?.email}
                                </Descriptions.Item>
                                <Descriptions.Item label="Số điện thoại">
                                    {order.user?.phone}
                                </Descriptions.Item>
                                <Descriptions.Item label="Địa chỉ giao hàng">
                                    {order.shipping_address}
                                </Descriptions.Item>
                                {order.note && (
                                    <Descriptions.Item label="Ghi chú">
                                        {order.note}
                                    </Descriptions.Item>
                                )}
                            </Descriptions>
                        </Card>
                    </Col>

                    {/* Right Column */}
                    <Col xs={24} lg={8}>
                        {/* Order Status */}
                        <Card title="Trạng thái đơn hàng" style={{ marginBottom: 16 }}>
                            <Space orientation="vertical" size="large" style={{ width: '100%' }}>
                                <div>
                                    <div style={{ marginBottom: 8, fontWeight: 500 }}>
                                        Trạng thái hiện tại:
                                    </div>
                                    <Tag 
                                        color={statusConfig[order.order_status]?.color}
                                        icon={statusConfig[order.order_status]?.icon}
                                        style={{ fontSize: 14, padding: '4px 12px' }}
                                    >
                                        {statusConfig[order.order_status]?.label}
                                    </Tag>
                                </div>

                                <div>
                                    <div style={{ marginBottom: 8, fontWeight: 500 }}>
                                        Cập nhật trạng thái:
                                    </div>
                                    <Select
                                        size="large"
                                        style={{ width: '100%', marginBottom: 8 }}
                                        value={selectedStatus}
                                        onChange={setSelectedStatus}
                                    >
                                        <Option value="pending">Chờ xử lý</Option>
                                        <Option value="processing">Đang xử lý</Option>
                                        <Option value="shipping">Đang giao</Option>
                                        <Option value="completed">Hoàn thành</Option>
                                        <Option value="cancelled">Hủy đơn</Option>
                                    </Select>
                                    <Button
                                        type="primary"
                                        block
                                        size="large"
                                        onClick={handleUpdateStatus}
                                        loading={updating}
                                        disabled={selectedStatus === order.order_status}
                                    >
                                        Cập nhật
                                    </Button>
                                </div>
                            </Space>
                        </Card>

                        {/* Payment Status */}
                        <Card title="Thanh toán" style={{ marginBottom: 16 }}>
                            <Space orientation="vertical" size="small" style={{ width: '100%' }}>
                                <div>
                                    <span>Phương thức: </span>
                                    <span style={{ fontWeight: 500 }}>
                                        {order.payment_method === 'cod' ? 'COD' : 'Chuyển khoản'}
                                    </span>
                                </div>
                                <div>
                                    <span>Trạng thái: </span>
                                    <Tag color={paymentConfig[order.payment_status]?.color}>
                                        {paymentConfig[order.payment_status]?.label}
                                    </Tag>
                                </div>
                            </Space>
                        </Card>

                        {/* Timeline */}
                        <Card title="Lịch sử đơn hàng">
                            <Timeline
                                items={[
                                    {
                                        color: 'green',
                                        content: (
                                            <div>
                                                <div style={{ fontWeight: 500 }}>Đơn hàng đã tạo</div>
                                                <div style={{ fontSize: 12, color: '#999' }}>
                                                    {dayjs(order.created_at).format('DD/MM/YYYY HH:mm')}
                                                </div>
                                            </div>
                                        ),
                                    },
                                    ...(order.order_status !== 'pending' ? [{
                                        color: 'blue',
                                        content: (
                                            <div>
                                                <div style={{ fontWeight: 500 }}>
                                                    {statusConfig[order.order_status]?.label}
                                                </div>
                                                <div style={{ fontSize: 12, color: '#999' }}>
                                                    {dayjs(order.updated_at).format('DD/MM/YYYY HH:mm')}
                                                </div>
                                            </div>
                                        ),
                                    }] : []),
                                ]}
                            />
                        </Card>
                    </Col>
                </Row>
            </Space>
        </AdminLayout>
    );
}