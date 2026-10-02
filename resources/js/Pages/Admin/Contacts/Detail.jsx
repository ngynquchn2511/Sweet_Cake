// resources/js/Pages/Admin/Contacts/Detail.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Card, Button, Input, Space, Tag, Popconfirm, message, Descriptions } from 'antd';
import { ArrowLeftOutlined, SendOutlined, DeleteOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const statusColor = { new: 'red', read: 'blue', replied: 'green' };
const statusLabel = { new: 'Mới', read: 'Đã đọc', replied: 'Đã phản hồi' };

export default function ContactDetail({ auth, contact }) {
    const [reply, setReply] = useState(contact.admin_reply || '');
    const [loading, setLoading] = useState(false);

    const handleReply = () => {
        if (!reply.trim()) {
            message.warning('Vui lòng nhập nội dung phản hồi');
            return;
        }
        setLoading(true);
        router.post(`/admin/contacts/${contact.id}/reply`, { admin_reply: reply }, {
            preserveScroll: true,
            onSuccess: () => message.success('Đã gửi phản hồi tới email khách hàng'),
            onError: () => message.error('Gửi phản hồi thất bại'),
            onFinish: () => setLoading(false),
        });
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Liên hệ: ${contact.subject}`} />

            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Button icon={<ArrowLeftOutlined />} onClick={() => router.visit('/admin/contacts')}>Quay lại</Button>
                <Space>
                    <Tag color={statusColor[contact.status]}>{statusLabel[contact.status]}</Tag>
                    <Popconfirm
                        title="Xóa liên hệ này?"
                        onConfirm={() => router.delete(`/admin/contacts/${contact.id}`)}
                        okText="Xóa"
                        cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                    >
                        <Button danger icon={<DeleteOutlined />}>Xóa</Button>
                    </Popconfirm>
                </Space>
            </div>

            <Card title="Thông tin liên hệ" style={{ marginBottom: 16 }}>
                <Descriptions column={{ xs: 1, md: 2 }} bordered size="small">
                    <Descriptions.Item label="Họ tên">{contact.name}</Descriptions.Item>
                    <Descriptions.Item label="Email">{contact.email}</Descriptions.Item>
                    <Descriptions.Item label="Số điện thoại">{contact.phone || '—'}</Descriptions.Item>
                    <Descriptions.Item label="Ngày gửi">{dayjs(contact.created_at).format('DD/MM/YYYY HH:mm')}</Descriptions.Item>
                    <Descriptions.Item label="Chủ đề" span={2}>{contact.subject}</Descriptions.Item>
                    <Descriptions.Item label="Nội dung" span={2}>
                        <div style={{ whiteSpace: 'pre-wrap' }}>{contact.message}</div>
                    </Descriptions.Item>
                </Descriptions>
            </Card>

            <Card title="Phản hồi của Admin">
                {contact.admin_reply && (
                    <div style={{ background: '#f6ffed', border: '1px solid #b7eb8f', borderRadius: 8, padding: '10px 14px', marginBottom: 16, whiteSpace: 'pre-wrap' }}>
                        {contact.admin_reply}
                        {contact.replied_at && (
                            <div style={{ fontSize: 12, color: '#999', marginTop: 6 }}>
                                Đã phản hồi lúc {dayjs(contact.replied_at).format('DD/MM/YYYY HH:mm')}
                            </div>
                        )}
                    </div>
                )}
                <Input.TextArea rows={4} placeholder="Nhập nội dung phản hồi (sẽ được gửi qua email cho khách)…" value={reply} onChange={(e) => setReply(e.target.value)} />
                <div style={{ marginTop: 12, textAlign: 'right' }}>
                    <Button type="primary" size="large" icon={<SendOutlined />} loading={loading} onClick={handleReply}>
                        Gửi phản hồi
                    </Button>
                </div>
            </Card>
        </AdminLayout>
    );
}
