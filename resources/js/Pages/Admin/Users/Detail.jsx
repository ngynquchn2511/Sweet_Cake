// resources/js/Pages/Admin/Users/Detail.jsx
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { 
    Card, Descriptions, Table, Button, Space, Tag, Row, Col, Statistic, Popconfirm, Modal, Timeline, message, 
} from 'antd';
import {
    ArrowLeftOutlined, ShoppingCartOutlined, DollarOutlined,
    CheckCircleOutlined, CloseCircleOutlined, KeyOutlined, HistoryOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/vi';

dayjs.extend(relativeTime);
dayjs.locale('vi');

const actionLabel = {
    update_status: 'Đổi trạng thái',
    reset_password: 'Đặt lại mật khẩu',
    update: 'Cập nhật thông tin',
    create: 'Tạo tài khoản',
    delete: 'Xoá tài khoản',
};

export default function UserDetail({ auth, user, orders, stats }) {
    // Nhật ký hoạt động của admin trên tài khoản này
    const [activityLogs, setActivityLogs] = useState(null);
    const loadActivityLogs = () =>
        axios.get(`/admin/users/${user.id}/activity-log`)
            .then((res) => setActivityLogs(res.data.data))
            .catch(() => setActivityLogs([]));
    useEffect(() => { loadActivityLogs(); }, [user.id]);

    // Đặt lại mật khẩu hộ khách hàng
    const handleResetPassword = async () => {
        try {
            const res = await axios.post(`/admin/users/${user.id}/reset-password`);
            Modal.success({
                title: res.data.message,
                content: <div>Mật khẩu tạm thời: <b style={{ fontFamily: 'monospace' }}>{res.data.new_password}</b></div>,
            });
            loadActivityLogs();
        } catch (err) {
            message.error(err.response?.data?.message || 'Không đặt lại được mật khẩu');
        }
    };

    // Format currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(value);
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

    // Order columns
    const columns = [
        {
            title: 'Mã đơn',
            dataIndex: 'order_code',
            key: 'order_code',
            width: 150,
            render: (text, record) => (
                <a 
                    onClick={() => router.visit(`/admin/orders/${record.id}`)}
                    style={{ color: '#1890ff', cursor: 'pointer' }}
                >
                    {text}
                </a>
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
            title: 'Số sản phẩm',
            dataIndex: 'order_items',
            key: 'items',
            width: 120,
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
                <span style={{ fontWeight: 500 }}>
                    {formatCurrency(amount)}
                </span>
            ),
        },
        {
            title: 'Trạng thái',
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
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Khách hàng: ${user.full_name}`} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                {/* Header */}
                <Card>
                    <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                        <Space>
                            <Button
                                icon={<ArrowLeftOutlined />}
                                onClick={() => router.visit('/admin/users')}
                            >
                                Quay lại
                            </Button>
                            <span style={{ fontSize: 20, fontWeight: 'bold' }}>
                                Chi tiết khách hàng
                            </span>
                        </Space>
                        <Space>
                            <Popconfirm
                                title="Đặt lại mật khẩu cho khách hàng này?"
                                description="Mật khẩu tạm thời sẽ được gửi tới email của khách."
                                onConfirm={handleResetPassword}
                                okText="Đặt lại"
                                cancelText="Hủy"
                            >
                                <Button icon={<KeyOutlined />}>Đặt lại mật khẩu</Button>
                            </Popconfirm>
                            <Tag color={user.status === 'active' ? 'green' : 'red'} style={{ fontSize: 14 }}>
                                {user.status === 'active' ? 'Hoạt động' : 'Đã chặn'}
                            </Tag>
                        </Space>
                    </Space>
                </Card>

                <Row gutter={[16, 16]}>
                    {/* Left Column */}
                    <Col xs={24} lg={16}>
                        {/* Customer Info */}
                        <Card title="Thông tin khách hàng" style={{ marginBottom: 16 }}>
                            <Descriptions column={1} bordered>
                                <Descriptions.Item label="Họ tên">
                                    {user.full_name}
                                </Descriptions.Item>
                                <Descriptions.Item label="Email">
                                    {user.email}
                                </Descriptions.Item>
                                <Descriptions.Item label="Số điện thoại">
                                    {user.phone || '-'}
                                </Descriptions.Item>
                                <Descriptions.Item label="Địa chỉ">
                                    {user.address || '-'}
                                </Descriptions.Item>
                                <Descriptions.Item label="Ngày tham gia">
                                    {dayjs(user.created_at).format('DD/MM/YYYY HH:mm')}
                                </Descriptions.Item>
                                <Descriptions.Item label="Đăng nhập lần cuối">
                                    {user.last_login 
                                        ? dayjs(user.last_login).format('DD/MM/YYYY HH:mm')
                                        : 'Chưa đăng nhập'
                                    }
                                </Descriptions.Item>
                            </Descriptions>
                        </Card>

                        {/* Order History */}
                        <Card title="Lịch sử đơn hàng">
                            <Table
                                columns={columns}
                                dataSource={orders?.data || []}
                                rowKey="id"
                                pagination={{
                                    current: orders?.current_page || 1,
                                    pageSize: orders?.per_page || 10,
                                    total: orders?.total || 0,
                                    showTotal: (total) => `Tổng ${total} đơn hàng`,
                                    onChange: (page) => {
                                        router.get(`/admin/users/${user.id}`, {
                                            page,
                                        }, {
                                            preserveState: true,
                                            preserveScroll: true,
                                        });
                                    },
                                }}
                            />
                        </Card>
                    </Col>

                    {/* Right Column */}
                    <Col xs={24} lg={8}>
                        {/* Statistics */}
                        <Card title="Thống kê" style={{ marginBottom: 16 }}>
                            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                                <Statistic
                                    title="Tổng đơn hàng"
                                    value={stats.total_orders}
                                    prefix={<ShoppingCartOutlined />}
                                    styles={{ value: { color: '#1890ff' } }}
                                />
                                <Statistic
                                    title="Đơn hoàn thành"
                                    value={stats.completed_orders}
                                    prefix={<CheckCircleOutlined />}
                                    styles={{ value: { color: '#52c41a' } }}
                                />
                                <Statistic
                                    title="Đơn đã hủy"
                                    value={stats.cancelled_orders}
                                    prefix={<CloseCircleOutlined />}
                                    styles={{ value: { color: '#f5222d' } }}
                                />
                                <Statistic
                                    title="Tổng chi tiêu"
                                    value={stats.total_spent}
                                    formatter={(value) => formatCurrency(value)}
                                    prefix={<DollarOutlined />}
                                    styles={{ value: { color: '#722ed1', fontSize: 20 } }}
                                />
                                <Statistic
                                    title="Giá trị đơn TB"
                                    value={stats.avg_order_value}
                                    formatter={(value) => formatCurrency(value)}
                                    styles={{ value: { color: '#fa8c16' } }}
                                />
                            </Space>
                        </Card>

                        <Card title={<span><HistoryOutlined /> Nhật ký hoạt động</span>} style={{ marginBottom: 16 }} loading={activityLogs === null}>
                            {activityLogs?.length ? (
                                <Timeline
                                    items={activityLogs.map((log) => ({
                                        children: (
                                            <div>
                                                <div style={{ fontWeight: 500 }}>{actionLabel[log.action] || log.action}</div>
                                                <div style={{ fontSize: 12, color: '#999' }}>
                                                    {log.user?.full_name || 'Admin'} · {dayjs(log.created_at).format('DD/MM/YYYY HH:mm')}
                                                </div>
                                            </div>
                                        ),
                                    }))}
                                />
                            ) : (
                                <div style={{ color: '#999' }}>Chưa có thao tác quản trị nào trên tài khoản này.</div>
                            )}
                        </Card>

                        {/* Last Order */}
                        {stats.last_order_date && (
                            <Card title="Đơn hàng gần nhất">
                                <div style={{ textAlign: 'center', padding: '20px 0' }}>
                                    <div style={{ fontSize: 16, color: '#666', marginBottom: 8 }}>
                                        Ngày đặt
                                    </div>
                                    <div style={{ fontSize: 20, fontWeight: 'bold', color: '#1890ff' }}>
                                        {dayjs(stats.last_order_date).format('DD/MM/YYYY')}
                                    </div>
                                    <div style={{ fontSize: 12, color: '#999', marginTop: 4 }}>
                                        {dayjs(stats.last_order_date).fromNow()}
                                    </div>
                                </div>
                            </Card>
                        )}
                    </Col>
                </Row>
            </Space>
        </AdminLayout>
    );
}