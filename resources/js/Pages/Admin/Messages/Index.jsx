// resources/js/Pages/Admin/Messages/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Card, Table, Button, Input, Select, Space, Tag,
    Popconfirm, message, Row, Col, Badge
} from 'antd';
import {
    SearchOutlined, FilterOutlined, DeleteOutlined,
    MailOutlined, CheckCircleOutlined
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const { Search } = Input;
const { Option } = Select;

export default function MessageIndex({ auth, messages, filters = {} }) {
    const [searchText, setSearchText] = useState(filters?.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');

    // count unread từ data hiện tại
    const unreadCount = (messages?.data || []).filter(m => m.status === 'unread').length;

    const handleSearch = (value) => {
        router.get('/admin/messages', {
            search: value,
            status: selectedStatus,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleFilterChange = (value) => {
        setSelectedStatus(value);
        router.get('/admin/messages', {
            search: searchText,
            status: value,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleDelete = (id) => {
        router.delete(`/admin/messages/${id}`, {
            onSuccess: () => message.success('Đã xóa tin nhắn!'),
        });
    };

    const handleMarkAllRead = () => {
        router.post('/admin/messages/mark-all-read', {}, {
            onSuccess: () => {
                message.success('Đã đánh dấu tất cả đã đọc!');
                router.reload();
            },
        });
    };

    const statusColor = { unread: 'red', read: 'blue', replied: 'green' };
    const statusLabel = { unread: 'Chưa đọc', read: 'Đã đọc', replied: 'Đã phản hồi' };

    const columns = [
        {
            title: '',
            key: 'dot',
            width: 24,
            align: 'center',
            render: (_, record) => (
                record.status === 'unread' ? <Badge dot color="#f5222d" /> : null
            ),
        },
        {
            title: 'Người gửi',
            key: 'sender',
            width: 200,
            render: (_, record) => {
                const name  = record.user ? record.user.full_name : (record.guest_name || 'Khách ẩn danh');
                const email = record.user ? record.user.email    : (record.guest_email || '—');
                return (
                    <div>
                        <div style={{ fontWeight: 500 }}>{name}</div>
                        <div style={{ fontSize: 12, color: '#999' }}>{email}</div>
                    </div>
                );
            },
        },
        {
            title: 'Chủ đề',
            dataIndex: 'subject',
            key: 'subject',
            render: (text, record) => (
                <span style={{ fontWeight: record.status === 'unread' ? 600 : 400 }}>{text}</span>
            ),
        },
        {
            title: 'Xem trước',
            dataIndex: 'content',
            key: 'content',
            render: (text) => (
                <span style={{ color: '#666', fontSize: 13 }}>
                    {(text || '').length > 55 ? text.slice(0, 55) + '…' : text}
                </span>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            width: 110,
            align: 'center',
            render: (s) => <Tag color={statusColor[s]}>{statusLabel[s]}</Tag>,
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 145,
            align: 'center',
            render: (d) => dayjs(d).format('DD/MM/YYYY HH:mm'),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 120,
            align: 'center',
            render: (_, record) => (
                <Space size="small">
                    <Button
                        type="primary"
                        icon={<MailOutlined />}
                        size="small"
                        onClick={(e) => { e.stopPropagation(); router.visit(`/admin/messages/${record.id}`); }}
                    >
                        Đọc
                    </Button>
                    <Popconfirm
                        title="Xóa tin nhắn này?"
                        onConfirm={() => handleDelete(record.id)}
                        okText="Xóa" cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                    >
                        <Button danger icon={<DeleteOutlined />} size="small"
                            onClick={(e) => e.stopPropagation()} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Tin nhắn khách hàng" />

            <Card
                title={
                    <span style={{ fontSize: 20, fontWeight: 'bold' }}>
                        💬 Tin nhắn khách hàng
                        {unreadCount > 0 && (
                            <Badge count={unreadCount} style={{ marginLeft: 10, backgroundColor: '#f5222d', boxShadow: 'none' }} />
                        )}
                    </span>
                }
                extra={
                    <Button icon={<CheckCircleOutlined />} onClick={handleMarkAllRead}>
                        Đánh dấu tất cả đã đọc
                    </Button>
                }
            >
                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                    <Col xs={24} md={15}>
                        <Search
                            placeholder="Tìm theo chủ đề, nội dung, người gửi…"
                            allowClear size="large"
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onSearch={handleSearch}
                            prefix={<SearchOutlined />}
                        />
                    </Col>
                    <Col xs={24} md={9}>
                        <Select
                            placeholder="Lọc trạng thái"
                            size="large" style={{ width: '100%' }}
                            allowClear
                            value={selectedStatus || undefined}
                            onChange={handleFilterChange}
                            suffixIcon={<FilterOutlined />}
                        >
                            <Option value="unread">Chưa đọc</Option>
                            <Option value="read">Đã đọc</Option>
                            <Option value="replied">Đã phản hồi</Option>
                        </Select>
                    </Col>
                </Row>

                <Table
                    columns={columns}
                    dataSource={messages?.data || []}
                    rowKey="id"
                    onRow={(record) => ({
                        onClick: () => router.visit(`/admin/messages/${record.id}`),
                        style: {
                            ...(record.status === 'unread' ? { backgroundColor: '#f0f7ff' } : {}),
                            cursor: 'pointer',
                        },
                    })}
                    pagination={{
                        current:  messages?.current_page || 1,
                        pageSize: messages?.per_page   || 15,
                        total:    messages?.total      || 0,
                        showSizeChanger: true,
                        showTotal: (total) => `Tổng ${total} tin nhắn`,
                        onChange: (page, pageSize) => {
                            router.get('/admin/messages', { ...filters, page, per_page: pageSize }, {
                                preserveState: true, preserveScroll: true,
                            });
                        },
                    }}
                />
            </Card>
        </AdminLayout>
    );
}