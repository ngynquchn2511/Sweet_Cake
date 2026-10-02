// resources/js/Pages/Admin/Products/Edit.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { 
    Card, Form, Input, InputNumber, Select, Button, Upload, 
    message, Space, Row, Col, Image 
} from 'antd';
import { ArrowLeftOutlined, SaveOutlined, UploadOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';

const { TextArea } = Input;
const { Option } = Select;

export default function ProductEdit({ auth, product, categories }) {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [fileList, setFileList] = useState([]);

    const handleSubmit = (values) => {
        setLoading(true);

        const formData = new FormData();
        formData.append('_method', 'PUT');
        
        // Append all form data EXCEPT image
        Object.keys(values).forEach(key => {
            if (key !== 'image' && values[key] !== undefined && values[key] !== null) {
                formData.append(key, values[key]);
            }
        });

        // Append new image ONLY if uploaded
        if (fileList.length > 0) {
            const file = fileList[0].originFileObj || fileList[0];
            if (file instanceof File) {
                formData.append('image', file);
            }
        }

        router.post(`/admin/products/${product.id}`, formData, {
            forceFormData: true,
            preserveState: false,
            preserveScroll: false,
            onSuccess: () => {
                message.success('Cập nhật sản phẩm thành công!');
                router.visit('/admin/products', {
                    preserveState: false,
                });
            },
            onError: (errors) => {
                console.error('Errors:', errors);
                message.error('Có lỗi xảy ra, vui lòng kiểm tra lại!');
            },
            onFinish: () => {
                setLoading(false);
            },
        });
    };

    const uploadProps = {
        onRemove: (file) => {
            setFileList([]);
        },
        beforeUpload: (file) => {
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
            setFileList([file]);
            return false;
        },
        fileList,
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Sửa: ${product.name}`} />

            <Card
                title={
                    <Space>
                        <Button 
                            icon={<ArrowLeftOutlined />}
                            onClick={() => router.visit('/admin/products')}
                        >
                            Quay lại
                        </Button>
                        <span style={{ fontSize: 20, fontWeight: 'bold' }}>Sửa sản phẩm</span>
                    </Space>
                }
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                    initialValues={{
                        name: product.name,
                        slug: product.slug,
                        description: product.description,
                        price: product.price,
                        discount_price: product.discount_price,
                        stock: product.stock,
                        unit: product.unit,
                        weight: product.weight,
                        category_id: product.category_id,
                        status: product.status,
                    }}
                >
                    <Row gutter={24}>
                        {/* Left Column */}
                        <Col xs={24} lg={16}>
                            <Card title="Thông tin cơ bản" style={{ marginBottom: 24 }}>
                                <Form.Item
                                    label="Tên sản phẩm"
                                    name="name"
                                    rules={[
                                        { required: true, message: 'Vui lòng nhập tên sản phẩm!' },
                                        { max: 200, message: 'Tên sản phẩm tối đa 200 ký tự!' },
                                    ]}
                                >
                                    <Input 
                                        size="large" 
                                        placeholder="VD: Bánh kem dâu tây" 
                                    />
                                </Form.Item>

                                <Form.Item
                                    label="Slug (URL thân thiện)"
                                    name="slug"
                                    help="Để trống để tự động tạo từ tên sản phẩm"
                                >
                                    <Input 
                                        size="large" 
                                        placeholder="VD: banh-kem-dau-tay" 
                                    />
                                </Form.Item>

                                <Form.Item
                                    label="Mô tả"
                                    name="description"
                                >
                                    <TextArea 
                                        rows={6} 
                                        placeholder="Mô tả chi tiết về sản phẩm..."
                                    />
                                </Form.Item>
                            </Card>

                            <Card title="Giá & Kho" style={{ marginBottom: 24 }}>
                                <Row gutter={16}>
                                    <Col xs={24} sm={12}>
                                        <Form.Item
                                            label="Giá bán"
                                            name="price"
                                            rules={[
                                                { required: true, message: 'Vui lòng nhập giá!' },
                                            ]}
                                        >
                                            <Space.Compact style={{ width: '100%' }}>
                                                <InputNumber
                                                    size="large"
                                                    style={{ width: '100%' }}
                                                    min={0}
                                                    formatter={value => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                                    parser={value => value.replace(/\$\s?|(,*)/g, '')}
                                                    placeholder="0"
                                                />
                                                <Input 
                                                    size="large"
                                                    style={{ width: 50, textAlign: 'center', pointerEvents: 'none', backgroundColor: '#fafafa' }} 
                                                    value="đ" 
                                                    readOnly 
                                                />
                                            </Space.Compact>
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} sm={12}>
                                        <Form.Item
                                            label="Giá khuyến mãi"
                                            name="discount_price"
                                        >
                                            <Space.Compact style={{ width: '100%' }}>
                                                <InputNumber
                                                    size="large"
                                                    style={{ width: '100%' }}
                                                    min={0}
                                                    formatter={value => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                                    parser={value => value.replace(/\$\s?|(,*)/g, '')}
                                                    placeholder="0"
                                                />
                                                <Input 
                                                    size="large"
                                                    style={{ width: 50, textAlign: 'center', pointerEvents: 'none', backgroundColor: '#fafafa' }} 
                                                    value="đ" 
                                                    readOnly 
                                                />
                                            </Space.Compact>
                                        </Form.Item>
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} sm={12}>
                                        <Form.Item
                                            label="Số lượng tồn kho"
                                            name="stock"
                                            rules={[
                                                { required: true, message: 'Vui lòng nhập số lượng!' },
                                            ]}
                                        >
                                            <InputNumber
                                                size="large"
                                                style={{ width: '100%' }}
                                                min={0}
                                                placeholder="0"
                                            />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} sm={12}>
                                        <Form.Item
                                            label="Đơn vị"
                                            name="unit"
                                            rules={[
                                                { required: true, message: 'Vui lòng nhập đơn vị!' },
                                            ]}
                                        >
                                            <Input 
                                                size="large" 
                                                placeholder="Cái, Chiếc, Hộp..." 
                                            />
                                        </Form.Item>
                                    </Col>
                                </Row>

                                <Form.Item
                                    label="Khối lượng (gram)"
                                    name="weight"
                                >
                                    <Space.Compact style={{ width: '100%' }}>
                                        <InputNumber
                                            size="large"
                                            style={{ width: '100%' }}
                                            min={0}
                                            placeholder="0"
                                        />
                                        <Input 
                                            size="large"
                                            style={{ width: 50, textAlign: 'center', pointerEvents: 'none', backgroundColor: '#fafafa' }} 
                                            value="g" 
                                            readOnly 
                                        />
                                    </Space.Compact>
                                </Form.Item>
                            </Card>
                        </Col>

                        {/* Right Column */}
                        <Col xs={24} lg={8}>
                            <Card title="Danh mục & Trạng thái" style={{ marginBottom: 24 }}>
                                <Form.Item
                                    label="Danh mục"
                                    name="category_id"
                                    rules={[
                                        { required: true, message: 'Vui lòng chọn danh mục!' },
                                    ]}
                                >
                                    <Select
                                        size="large"
                                        placeholder="Chọn danh mục"
                                        showSearch
                                        optionFilterProp="children"
                                    >
                                        {categories.map(cat => (
                                            <Option key={cat.id} value={cat.id}>
                                                {cat.name}
                                            </Option>
                                        ))}
                                    </Select>
                                </Form.Item>

                                <Form.Item
                                    label="Trạng thái"
                                    name="status"
                                    rules={[
                                        { required: true, message: 'Vui lòng chọn trạng thái!' },
                                    ]}
                                >
                                    <Select size="large">
                                        <Option value="active">Đang bán</Option>
                                        <Option value="inactive">Ngừng bán</Option>
                                    </Select>
                                </Form.Item>
                            </Card>

                            <Card title="Hình ảnh" style={{ marginBottom: 24 }}>
                                {/* Current Image */}
                                {product.image && fileList.length === 0 && (
                                    <div style={{ marginBottom: 16 }}>
                                        <p style={{ marginBottom: 8, fontWeight: 500 }}>Ảnh hiện tại:</p>
                                        <Image
                                            src={`/${product.image}`}
                                            alt={product.name}
                                            style={{ width: '100%', borderRadius: 8 }}
                                        />
                                    </div>
                                )}

                                <Form.Item label="Thay đổi ảnh">
                                    <Upload
                                        {...uploadProps}
                                        listType="picture-card"
                                        maxCount={1}
                                    >
                                        {fileList.length === 0 && (
                                            <div>
                                                <UploadOutlined />
                                                <div style={{ marginTop: 8 }}>Upload ảnh mới</div>
                                            </div>
                                        )}
                                    </Upload>
                                </Form.Item>
                                <p style={{ fontSize: 12, color: '#999', marginTop: 8 }}>
                                    * Để trống nếu không muốn đổi ảnh<br/>
                                    * Ảnh tối đa 2MB, định dạng: JPG, PNG, WEBP
                                </p>
                            </Card>
                        </Col>
                    </Row>

                    {/* Actions */}
                    <Form.Item style={{ marginTop: 24 }}>
                        <Space>
                            <Button
                                type="primary"
                                htmlType="submit"
                                icon={<SaveOutlined />}
                                size="large"
                                loading={loading}
                            >
                                Cập nhật
                            </Button>
                            <Button
                                size="large"
                                onClick={() => router.visit('/admin/products')}
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