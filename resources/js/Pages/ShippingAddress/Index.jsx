import { Head, Link, router } from '@inertiajs/react';
import { Button, Card, Empty, Space, Popconfirm, Tag, Table, Modal, message } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined, CheckCircleOutlined } from '@ant-design/icons';
import Navbar from '@/Components/Navbar';
import { useState } from 'react';

export default function Index({ addresses = [] }) {
    const [selectedAddress, setSelectedAddress] = useState(null);

    const handleDelete = (id) => {
        router.delete(route('shipping-addresses.destroy', id), {
            onSuccess: () => {
                message.success('Xóa địa chỉ thành công!');
            },
            onError: () => {
                message.error('Lỗi khi xóa địa chỉ!');
            }
        });
    };

    const handleSetDefault = (id) => {
        // Gửi request để đặt địa chỉ mặc định
        router.put(route('shipping-addresses.update', id), {
            is_default: true,
        }, {
            onSuccess: () => {
                message.success('Đặt địa chỉ mặc định thành công!');
            },
            onError: () => {
                message.error('Lỗi khi cập nhật!');
            }
        });
    };

    const columns = [
        {
            title: 'Tên người nhận',
            dataIndex: 'receiver_name',
            key: 'receiver_name',
        },
        {
            title: 'Số điện thoại',
            dataIndex: 'phone',
            key: 'phone',
        },
        {
            title: 'Địa chỉ',
            key: 'address',
            render: (_, record) => (
                <span>
                    {record.address}, {record.ward}, {record.district}, {record.province}
                </span>
            ),
        },
        {
            title: 'Mặc định',
            key: 'default',
            render: (_, record) => (
                record.is_default ? (
                    <Tag icon={<CheckCircleOutlined />} color="green">
                        Địa chỉ mặc định
                    </Tag>
                ) : (
                    <Button 
                        type="link" 
                        size="small"
                        onClick={() => handleSetDefault(record.id)}
                    >
                        Đặt mặc định
                    </Button>
                )
            ),
        },
        {
            title: 'Hành động',
            key: 'action',
            width: 150,
            render: (_, record) => (
                <Space size="middle">
                    <Link href={route('shipping-addresses.edit', record.id)}>
                        <Button type="link" icon={<EditOutlined />} size="small">
                            Sửa
                        </Button>
                    </Link>
                    <Popconfirm
                        title="Xác nhận xóa"
                        description="Bạn có chắc chắn muốn xóa địa chỉ này không?"
                        onConfirm={() => handleDelete(record.id)}
                        okText="Xóa"
                        cancelText="Hủy"
                    >
                        <Button type="link" danger icon={<DeleteOutlined />} size="small">
                            Xóa
                        </Button>
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="Sổ địa chỉ của tôi" />
            <Navbar />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Breadcrumb */}
                    <nav className="text-sm text-gray-500 mb-6">
                        <Link href="/" className="hover:text-orange-500">Trang chủ</Link>
                        <span className="mx-2"> &gt; </span>
                        <Link href="/dashboard" className="hover:text-orange-500">Trang khách hàng</Link>
                        <span className="mx-2"> &gt; </span>
                        <span className="text-orange-500 font-medium">Sổ địa chỉ</span>
                    </nav>

                    <div className="flex flex-col md:flex-row gap-6">
                        {/* SIDEBAR BÊN TRÁI */}
                        <div className="w-full md:w-1/4 bg-white rounded-lg shadow-sm p-4 h-fit border border-gray-100">
                            <ul className="space-y-1">
                                <li>
                                    <Link href="/dashboard" className="flex items-center p-3 text-gray-600 hover:bg-gray-50 rounded-md transition">
                                        <span className="mr-3">👤</span> Thông tin cá nhân
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/my-orders" className="flex items-center p-3 text-gray-600 hover:bg-gray-50 rounded-md transition">
                                        <span className="mr-3">📦</span> Đơn hàng của bạn
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/change-password" className="flex items-center p-3 text-gray-600 hover:bg-gray-50 rounded-md transition">
                                        <span className="mr-3">🔒</span> Đổi mật khẩu
                                    </Link>
                                </li>
                                <li className="bg-orange-50 text-orange-600 rounded-md">
                                    <Link href="/shipping-addresses" className="flex items-center p-3 font-medium">
                                        <span className="mr-3">📍</span> Sổ địa chỉ
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        {/* NỘI DUNG BÊN PHẢI */}
                        <div className="w-full md:w-3/4 bg-white rounded-lg shadow-sm p-8 border border-gray-100">
                            <div className="border-b pb-6 mb-6 flex justify-between items-center">
                                <div>
                                    <h2 className="text-2xl font-semibold text-gray-800">Sổ địa chỉ của tôi</h2>
                                    <p className="text-gray-500 text-sm">Quản lý các địa chỉ giao hàng của bạn</p>
                                </div>
                                <Link href={route('shipping-addresses.create')}>
                                    <Button 
                                        type="primary" 
                                        icon={<PlusOutlined />}
                                        className="bg-orange-500 hover:bg-orange-600 border-orange-500"
                                    >
                                        Thêm địa chỉ
                                    </Button>
                                </Link>
                            </div>

                            {addresses.length === 0 ? (
                                <Empty 
                                    description="Chưa có địa chỉ nào"
                                    style={{ marginTop: '50px' }}
                                >
                                    <Link href={route('shipping-addresses.create')}>
                                        <Button type="primary" className="bg-orange-500 hover:bg-orange-600 border-orange-500">
                                            Thêm địa chỉ đầu tiên
                                        </Button>
                                    </Link>
                                </Empty>
                            ) : (
                                <Table 
                                    columns={columns}
                                    dataSource={addresses}
                                    rowKey="id"
                                    pagination={{
                                        pageSize: 10,
                                        showTotal: (total) => `Tổng ${total} địa chỉ`
                                    }}
                                />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}