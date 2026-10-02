// resources/js/Pages/Admin/Logs/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Card, Table, Button, Input, Row, Col, Statistic, Tag, Popconfirm, InputNumber, Space, message } from 'antd';
import { DownloadOutlined, DeleteOutlined, SearchOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const actionColor = { create: 'green', update: 'blue', delete: 'red', update_status: 'orange', cancel: 'volcano' };

export default function AdminLogIndex({ auth, logs, stats, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [days, setDays] = useState(90);

    const reload = (params) =>
        router.get('/admin/logs', { ...filters, ...params }, { preserveState: true, preserveScroll: true });

    const clearOld = () =>
        router.post('/admin/logs/clear-old', { days }, {
            preserveScroll: true,
            onSuccess: (page) => message.success(page.props.flash?.success || 'Đã dọn dẹp log cũ'),
        });

    const columns = [
        { title: 'Thời gian', dataIndex: 'created_at', key: 'created_at', width: 160, render: (d) => dayjs(d).format('DD/MM/YYYY HH:mm') },
        { title: 'Người thực hiện', key: 'user', render: (_, l) => l.user?.full_name || '—' },
        { title: 'Hành động', dataIndex: 'action', key: 'action', render: (a) => <Tag color={actionColor[a] || 'default'}>{a}</Tag> },
        { title: 'Bảng', dataIndex: 'table_name', key: 'table_name' },
        { title: 'Mã bản ghi', dataIndex: 'record_id', key: 'record_id', align: 'center' },
        {
            title: 'Thay đổi',
            key: 'changes',
            render: (_, l) => (
                <div style={{ fontSize: 12, maxWidth: 320 }}>
                    {l.old_value && <div><b>Trước:</b> {JSON.stringify(l.old_value)}</div>}
                    {l.new_value && <div><b>Sau:</b> {JSON.stringify(l.new_value)}</div>}
                </div>
            ),
        },
        { title: 'IP', dataIndex: 'ip_address', key: 'ip_address', width: 120 },
    ];

    const byAction = Object.entries(stats?.by_action || {});

    return (
        <AdminLayout user={auth.user}>
            <Head title="Nhật ký hoạt động" />

            <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                <Col xs={12} md={6}><Card><Statistic title="Tổng số log" value={stats?.total_logs || 0} /></Card></Col>
                <Col xs={12} md={6}><Card><Statistic title="Hôm nay" value={stats?.today_logs || 0} /></Card></Col>
                <Col xs={24} md={12}>
                    <Card title="Theo hành động" size="small">
                        <Space wrap>
                            {byAction.length ? byAction.map(([action, count]) => (
                                <Tag key={action} color={actionColor[action] || 'default'}>{action}: {count}</Tag>
                            )) : <span style={{ color: '#999' }}>Chưa có dữ liệu</span>}
                        </Space>
                    </Card>
                </Col>
            </Row>

            <Card
                title={<span style={{ fontSize: 20, fontWeight: 'bold' }}>Nhật ký hoạt động quản trị</span>}
                extra={
                    <Space wrap>
                        <Space.Compact>
                            <InputNumber min={1} value={days} onChange={(v) => setDays(v || 1)} style={{ width: 90 }} aria-label="Số ngày" />
                            <Button disabled>ngày</Button>
                        </Space.Compact>
                        <Popconfirm title={`Xoá các log cũ hơn ${days} ngày?`} onConfirm={clearOld} okText="Xoá" cancelText="Hủy" okButtonProps={{ danger: true }}>
                            <Button danger icon={<DeleteOutlined />}>Xoá log cũ</Button>
                        </Popconfirm>
                        <Button type="primary" icon={<DownloadOutlined />} href={`/admin/logs/export?from_date=${filters.from_date || ''}&to_date=${filters.to_date || ''}`}>
                            Xuất log (CSV)
                        </Button>
                    </Space>
                }
            >
                <Input.Search
                    placeholder="Tìm theo bảng, hành động, người thực hiện..."
                    allowClear
                    size="large"
                    style={{ maxWidth: 480, marginBottom: 16 }}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    onSearch={(value) => reload({ search: value, page: 1 })}
                    prefix={<SearchOutlined />}
                />

                <Table
                    columns={columns}
                    dataSource={logs.data}
                    rowKey="id"
                    scroll={{ x: 1000 }}
                    pagination={{
                        current: logs.current_page,
                        pageSize: logs.per_page,
                        total: logs.total,
                        showTotal: (total) => `Tổng ${total} bản ghi`,
                        onChange: (page, per_page) => reload({ page, per_page }),
                    }}
                />
            </Card>
        </AdminLayout>
    );
}
