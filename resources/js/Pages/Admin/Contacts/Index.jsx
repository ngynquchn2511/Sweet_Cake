// resources/js/Pages/Admin/Contacts/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Card, Table, Button, Input, Select, Space, Tag, Row, Col } from 'antd';
import { EyeOutlined, SearchOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const statusColor = { new: 'red', read: 'blue', replied: 'green' };
const statusLabel = { new: 'Mới', read: 'Đã đọc', replied: 'Đã phản hồi' };

export default function ContactIndex({ auth, contacts, filters }) {
    const [search, setSearch] = useState(filters.search || '');

    const reload = (params) =>
        router.get('/admin/contacts', { ...filters, ...params }, { preserveState: true, preserveScroll: true });

    const columns = [
        {
            title: 'Người gửi',
            key: 'sender',
            render: (_, r) => (
                <div>
                    <div style={{ fontWeight: 500 }}>{r.name}</div>
                    <div style={{ fontSize: 12, color: '#999' }}>{r.email} · {r.phone || '—'}</div>
                </div>
            ),
        },
        { title: 'Chủ đề', dataIndex: 'subject', key: 'subject' },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            align: 'center',
            width: 130,
            render: (s) => <Tag color={statusColor[s]}>{statusLabel[s]}</Tag>,
        },
        {
            title: 'Ngày gửi',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 160,
            render: (d) => dayjs(d).format('DD/MM/YYYY HH:mm'),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            align: 'center',
            width: 110,
            render: (_, r) => (
                <Button type="primary" size="small" icon={<EyeOutlined />} onClick={() => router.visit(`/admin/contacts/${r.id}`)}>
                    Xem
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Liên hệ khách hàng" />
            <Card title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>Liên hệ từ khách hàng</span>}>
                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                    <Col xs={24} md={12}>
                        <Input.Search
                            placeholder="Tìm theo tên, email, chủ đề..."
                            allowClear
                            size="large"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onSearch={(value) => reload({ search: value, page: 1 })}
                            prefix={<SearchOutlined />}
                        />
                    </Col>
                    <Col xs={24} md={8}>
                        <Select
                            placeholder="Lọc theo trạng thái"
                            size="large"
                            allowClear
                            style={{ width: '100%' }}
                            value={filters.status || undefined}
                            onChange={(value) => reload({ status: value, page: 1 })}
                            options={Object.entries(statusLabel).map(([value, label]) => ({ value, label }))}
                        />
                    </Col>
                </Row>

                <Table
                    columns={columns}
                    dataSource={contacts.data}
                    rowKey="id"
                    pagination={{
                        current: contacts.current_page,
                        pageSize: contacts.per_page,
                        total: contacts.total,
                        showTotal: (total) => `Tổng ${total} liên hệ`,
                        onChange: (page, per_page) => reload({ page, per_page }),
                    }}
                />
            </Card>
        </AdminLayout>
    );
}
