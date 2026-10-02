// import React from 'react';
// import { Head, useForm } from '@inertiajs/react';
// import { Card, Form, Input, Button, Switch, Space, message } from 'antd';
// import { ArrowLeftOutlined, SaveOutlined } from '@ant-design/icons';
// import AdminLayout from '@/Layouts/AdminLayout';

// export default function Edit({ auth, address }) {
//     // Sử dụng useForm của Inertia để quản lý state và gửi dữ liệu
//     const { data, setData, put, processing, errors } = useForm({
//         receiver_name: address.receiver_name || '',
//         phone: address.phone || '',
//         province: address.province || '',
//         district: address.district || '',
//         ward: address.ward || '',
//         address: address.address || '',
//         note: address.note || '',
//         is_default: address.is_default === 1,
//     });

//     const handleSubmit = () => {
//         put(route('shipping-addresses.update', address.id), {
//             onSuccess: () => message.success('Cập nhật địa chỉ thành công!'),
//             onError: () => message.error('Vui lòng kiểm tra lại thông tin.'),
//         });
//     };

//     return (
//         <AdminLayout user={auth.user}>
//             <Head title="Chỉnh sửa địa chỉ" />

//             <div style={{ maxWidth: 800, margin: '0 auto' }}>
//                 <Space style={{ marginBottom: 16 }}>
//                     <Button 
//                         icon={<ArrowLeftOutlined />} 
//                         onClick={() => window.history.back()}
//                     >
//                         Quay lại
//                     </Button>
//                     <h2 style={{ margin: 0 }}>Chỉnh sửa địa chỉ giao hàng</h2>
//                 </Space>

//                 <Card border={false} className="shadow-sm">
//                     <Form
//                         layout="vertical"
//                         onFinish={handleSubmit}
//                         initialValues={data}
//                     >
//                         <Form.Item 
//                             label="Tên người nhận" 
//                             validateStatus={errors.receiver_name ? 'error' : ''}
//                             help={errors.receiver_name}
//                             required
//                         >
//                             <Input 
//                                 value={data.receiver_name}
//                                 onChange={e => setData('receiver_name', e.target.value)}
//                                 placeholder="VD: Nguyễn Văn A"
//                             />
//                         </Form.Item>

//                         <Form.Item 
//                             label="Số điện thoại"
//                             validateStatus={errors.phone ? 'error' : ''}
//                             help={errors.phone}
//                             required
//                         >
//                             <Input 
//                                 value={data.phone}
//                                 onChange={e => setData('phone', e.target.value)}
//                                 placeholder="VD: 0912345678"
//                             />
//                         </Form.Item>

//                         <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '16px' }}>
//                             <Form.Item label="Tỉnh/Thành phố" required>
//                                 <Input value={data.province} onChange={e => setData('province', e.target.value)} />
//                             </Form.Item>
//                             <Form.Item label="Quận/Huyện" required>
//                                 <Input value={data.district} onChange={e => setData('district', e.target.value)} />
//                             </Form.Item>
//                             <Form.Item label="Phường/Xã" required>
//                                 <Input value={data.ward} onChange={e => setData('ward', e.target.value)} />
//                             </Form.Item>
//                         </div>

//                         <Form.Item label="Địa chỉ chi tiết" required>
//                             <Input.TextArea 
//                                 rows={3} 
//                                 value={data.address}
//                                 onChange={e => setData('address', e.target.value)}
//                                 placeholder="Số nhà, tên đường..."
//                             />
//                         </Form.Item>

//                         <Form.Item label="Ghi chú">
//                             <Input value={data.note} onChange={e => setData('note', e.target.value)} />
//                         </Form.Item>

//                         <Form.Item label="Đặt làm địa chỉ mặc định">
//                             <Switch 
//                                 checked={data.is_default}
//                                 onChange={checked => setData('is_default', checked)}
//                             />
//                         </Form.Item>

//                         <Form.Item>
//                             <Button 
//                                 type="primary" 
//                                 htmlType="submit" 
//                                 icon={<SaveOutlined />}
//                                 loading={processing}
//                                 block
//                             >
//                                 Lưu thay đổi
//                             </Button>
//                         </Form.Item>
//                     </Form>
//                 </Card>
//             </div>
//         </AdminLayout>
//     );
// }