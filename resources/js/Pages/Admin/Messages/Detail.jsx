// resources/js/Pages/Admin/Messages/Detail.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Card, Button, Input, Space, Tag, Popconfirm, message,
    Divider
} from 'antd';
import {
    ArrowLeftOutlined, SendOutlined, DeleteOutlined, UserOutlined,
    MailOutlined, PhoneOutlined, ClockCircleOutlined
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const { TextArea } = Input;

export default function MessageDetail({ auth, message: msg }) {
    const [reply, setReply] = useState(msg.admin_reply || '');
    const [loading, setLoading] = useState(false);

    const statusColor = { unread: 'red', read: 'blue', replied: 'green' };
    const statusLabel = { unread: 'Chưa đọc', read: 'Đã đọc', replied: 'Đã phản hồi' };

    // Người gửi
    const senderName  = msg.user ? msg.user.full_name  : (msg.guest_name  || 'Khách ẩn danh');
    const senderEmail = msg.user ? msg.user.email      : (msg.guest_email || '—');
    const senderPhone = msg.user ? msg.user.phone      : (msg.guest_phone || '—');

    // Submit reply
    const handleReply = () => {
        if (!reply.trim()) {
            message.warning('Nhập nội dung phản hồi trước đi!');
            return;
        }
        setLoading(true);
        router.post(`/admin/messages/${msg.id}/reply`, { admin_reply: reply }, {
            onSuccess: () => message.success('Đã phản hồi!'),
            onError:   () => message.error('Lỗi gửi phản hồi.'),
            onFinish:  () => setLoading(false),
        });
    };

    const handleDelete = () => {
        router.delete(`/admin/messages/${msg.id}`, {
            onSuccess: () => {
                message.success('Đã xóa tin nhắn!');
                router.visit('/admin/messages');
            },
        });
    };

    // Info row helper
    const InfoRow = ({ icon, label, value }) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
            {icon}
            <span style={{ color: '#999', fontSize: 13, minWidth: 45 }}>{label}:</span>
            <span style={{ fontSize: 14 }}>{value}</span>
        </div>
    );

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Tin nhắn: ${msg.subject}`} />

            {/* Header actions */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                <Button icon={<ArrowLeftOutlined />} onClick={() => router.visit('/admin/messages')}>
                    Quay lại
                </Button>
                <Space>
                    <Tag color={statusColor[msg.status]}>{statusLabel[msg.status]}</Tag>
                    <Popconfirm
                        title="Xóa tin nhắn này?"
                        onConfirm={handleDelete}
                        okText="Xóa" cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                    >
                        <Button danger icon={<DeleteOutlined />}>Xóa</Button>
                    </Popconfirm>
                </Space>
            </div>

            {/* Thông tin người gửi */}
            <Card
                style={{ marginBottom: 16, borderRadius: 10 }}
                styles={{ header: { backgroundColor: '#f5f7fa' } }}
                title={<span style={{ fontSize: 16, fontWeight: 600 }}>👤 Thông tin người gửi</span>}
                size="small"
            >
                <InfoRow icon={<UserOutlined style={{ color: '#1890ff' }} />}  label="Tên"   value={senderName} />
                <InfoRow icon={<MailOutlined style={{ color: '#52c41a' }} />}  label="Email" value={senderEmail} />
                <InfoRow icon={<PhoneOutlined style={{ color: '#faad14' }} />} label="SĐT"  value={senderPhone} />
            </Card>

            {/* Nội dung tin nhắn */}
            <Card
                style={{ marginBottom: 16, borderRadius: 10 }}
                styles={{ header: { backgroundColor: '#e6f4ff' } }}
                title={
                    <span style={{ fontSize: 16, fontWeight: 600 }}>
                        💬 {msg.subject}
                        <span style={{ fontSize: 12, color: '#999', marginLeft: 12 }}>
                            <ClockCircleOutlined /> {dayjs(msg.created_at).format('DD/MM/YYYY HH:mm')}
                        </span>
                    </span>
                }
                size="small"
            >
                <div style={{
                    fontSize: 15,
                    lineHeight: 1.7,
                    color: '#333',
                    padding: '8px 4px',
                    whiteSpace: 'pre-wrap',
                }}>
                    {msg.content}
                </div>
            </Card>

            {/* Phản hồi của Admin */}
            <Card
                style={{ borderRadius: 10 }}
                styles={{ header: { backgroundColor: '#f0fff0' } }}
                title={<span style={{ fontSize: 16, fontWeight: 600 }}>📝 Phản hồi của Admin</span>}
                size="small"
            >
                {/* Nếu đã phản hồi trước → hiển thị */}
                {msg.admin_reply && (
                    <div style={{
                        backgroundColor: '#f6ffed',
                        border: '1px solid #b7eb8f',
                        borderRadius: 8,
                        padding: '10px 14px',
                        marginBottom: 16,
                        fontSize: 14,
                        lineHeight: 1.6,
                        whiteSpace: 'pre-wrap',
                        color: '#333',
                    }}>
                        {msg.admin_reply}
                    </div>
                )}

                <TextArea
                    rows={4}
                    placeholder="Nhập nội dung phản hồi cho khách hàng…"
                    value={reply}
                    onChange={(e) => setReply(e.target.value)}
                    style={{ fontSize: 14 }}
                />

                <div style={{ marginTop: 12, display: 'flex', justifyContent: 'flex-end' }}>
                    <Button
                        type="primary"
                        size="large"
                        icon={<SendOutlined />}
                        loading={loading}
                        onClick={handleReply}
                        style={{ minWidth: 160 }}
                    >
                        Gửi phản hồi
                    </Button>
                </div>
            </Card>
        </AdminLayout>
    );
}