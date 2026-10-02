// resources/js/Pages/Admin/Categories/Form.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { 
    Card, Form, Input, Select, Button, Upload, 
    message, Space, Row, Col 
} from 'antd';
import { 
    SaveOutlined, ArrowLeftOutlined, PlusOutlined 
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';

const { TextArea } = Input;
const { Option } = Select;

export default function CategoryForm({ auth, category }) {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [fileList, setFileList] = useState([]);
    const [imageUrl, setImageUrl] = useState(category?.image || '');

    const isEdit = !!category;

    // Handle form submit
    const handleSubmit = (values) => {
        setLoading(true);

        const formData = new FormData();
        
        if (isEdit) {
            formData.append('_method', 'PUT');
        }

        // Append all form values except image
        Object.keys(values).forEach(key => {
            if (key !== 'image' && values[key] !== undefined && values[key] !== null) {
                formData.append(key, values[key]);
            }
        });

        // Append image ONLY if uploaded
        if (fileList.length > 0 && fileList[0].originFileObj) {
            formData.append('image', fileList[0].originFileObj);
        }

        const url = isEdit 
            ? `/admin/categories/${category.id}`
            : '/admin/categories';

        router.post(url, formData, {
            forceFormData: true,
            preserveState: false,
            onSuccess: () => {
                message.success(isEdit ? 'Cập nhật danh mục thành công!' : 'Thêm danh mục thành công!');
            },
            onError: (errors) => {
                message.error('Có lỗi xảy ra! Vui lòng kiểm tra lại.');
                form.setFields(
                    Object.keys(errors).map(key => ({
                        name: key,
                        errors: [errors[key]],
                    }))
                );
            },
            onFinish: () => {
                setLoading(false);
            },
        });
    };

    // Handle image upload
    const handleUploadChange = ({ fileList: newFileList }) => {
        setFileList(newFileList);
        
        if (newFileList.length > 0) {
            const file = newFileList[0];
            if (file.originFileObj) {
                const reader = new FileReader();
                reader.onload = (e) => setImageUrl(e.target.result);
                reader.readAsDataURL(file.originFileObj);
            }
        } else {
            setImageUrl(category?.image || '');
        }
    };

    // Before upload validation
    const beforeUpload = (file) => {
        const isImage = file.type.startsWith('image/');
        if (!isImage) {
            message.error('Chỉ được upload file ảnh!');
            return false;
        }
        
        const isLt2M = file.size / 1024 / 1024 < 2;
        if (!isLt2M) {
            message.error('Ảnh phải nhỏ hơn 2MB!');
            return false;
        }
        
        return false; // Prevent auto upload
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title={isEdit ? `Sửa: ${category.name}` : 'Thêm danh mục'} />

            <Card
                title={
                    <Space>
                        <Button
                            icon={<ArrowLeftOutlined />}
                            onClick={() => router.visit('/admin/categories')}
                        >
                            Quay lại
                        </Button>
                        <span style={{ fontSize: 20, fontWeight: 'bold' }}>
                            {isEdit ? '✏️ Sửa danh mục' : '➕ Thêm danh mục mới'}
                        </span>
                    </Space>
                }
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                    initialValues={category || {
                        status: 'active',
                    }}
                >
                    <Row gutter={24}>
                        {/* Left Column */}
                        <Col xs={24} lg={16}>
                            <Form.Item
                                label="Tên danh mục"
                                name="name"
                                rules={[
                                    { required: true, message: 'Vui lòng nhập tên danh mục!' },
                                    { max: 100, message: 'Tên không được quá 100 ký tự!' },
                                ]}
                            >
                                <Input 
                                    size="large" 
                                    placeholder="VD: Bánh kem" 
                                />
                            </Form.Item>

                            <Form.Item
                                label="Slug (URL)"
                                name="slug"
                                extra="Để trống để tự động tạo từ tên danh mục"
                            >
                                <Input 
                                    size="large" 
                                    placeholder="banh-kem" 
                                />
                            </Form.Item>

                            <Form.Item
                                label="Mô tả"
                                name="description"
                            >
                                <TextArea 
                                    rows={6} 
                                    placeholder="Nhập mô tả về danh mục..."
                                />
                            </Form.Item>

                            <Form.Item
                                label="Trạng thái"
                                name="status"
                                rules={[
                                    { required: true, message: 'Vui lòng chọn trạng thái!' }
                                ]}
                            >
                                <Select size="large">
                                    <Option value="active">Hoạt động</Option>
                                    <Option value="inactive">Tạm ngưng</Option>
                                </Select>
                            </Form.Item>
                        </Col>

                        {/* Right Column - Image Upload */}
                        <Col xs={24} lg={8}>
                            <Form.Item
                                label="Hình ảnh danh mục"
                                extra="Chỉ chấp nhận file JPG, PNG. Tối đa 2MB"
                            >
                                <Upload
                                    listType="picture-card"
                                    fileList={fileList}
                                    onChange={handleUploadChange}
                                    beforeUpload={beforeUpload}
                                    maxCount={1}
                                >
                                    {fileList.length === 0 && (
                                        <div>
                                            <PlusOutlined />
                                            <div style={{ marginTop: 8 }}>Upload</div>
                                        </div>
                                    )}
                                </Upload>
                            </Form.Item>

                            {/* Current or Preview Image */}
                            {imageUrl && !fileList.length && (
                                <div style={{ marginTop: 16 }}>
                                    <div style={{ 
                                        fontSize: 14, 
                                        fontWeight: 500, 
                                        marginBottom: 8,
                                        color: '#666',
                                    }}>
                                        Ảnh hiện tại:
                                    </div>
                                    <img 
                                        src={`/${imageUrl}`}
                                        alt="Current" 
                                        style={{ 
                                            maxWidth: '100%', 
                                            maxHeight: 200,
                                            borderRadius: 8,
                                            boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                                        }} 
                                    />
                                </div>
                            )}

                            {imageUrl && fileList.length > 0 && (
                                <div style={{ marginTop: 16 }}>
                                    <div style={{ 
                                        fontSize: 14, 
                                        fontWeight: 500, 
                                        marginBottom: 8,
                                        color: '#1890ff',
                                    }}>
                                        Ảnh mới:
                                    </div>
                                    <img 
                                        src={imageUrl} 
                                        alt="Preview" 
                                        style={{ 
                                            maxWidth: '100%', 
                                            maxHeight: 200,
                                            borderRadius: 8,
                                            boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                                        }} 
                                    />
                                </div>
                            )}
                        </Col>
                    </Row>

                    {/* Action Buttons */}
                    <Form.Item style={{ marginTop: 24 }}>
                        <Space size="large">
                            <Button
                                type="primary"
                                htmlType="submit"
                                icon={<SaveOutlined />}
                                size="large"
                                loading={loading}
                                style={{ minWidth: 150 }}
                            >
                                {isEdit ? 'Cập nhật' : 'Lưu danh mục'}
                            </Button>
                            <Button
                                size="large"
                                onClick={() => router.visit('/admin/categories')}
                            >
                                Hủy
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Card>
        </AdminLayout>
    );
}