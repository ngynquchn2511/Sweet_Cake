// resources/js/Pages/Admin/Promotions/Form.jsx
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { 
    Card, Form, Input, Select, InputNumber, DatePicker, Radio, 
    Button, Space, Row, Col, Divider 
} from 'antd';
import { ArrowLeftOutlined, SaveOutlined } from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

const { TextArea } = Input;
const { Option } = Select;
const { RangePicker } = DatePicker;

export default function PromotionForm({ auth, promotion }) {
    const isEdit = !!promotion;
    
    const { data, setData, post, put, processing, errors } = useForm({
        code: promotion?.code || '',
        description: promotion?.description || '',
        discount_type: promotion?.discount_type || 'percent',
        discount_value: promotion?.discount_value || 0,
        min_order_value: promotion?.min_order_value || null,
        max_discount: promotion?.max_discount || null,
        usage_limit: promotion?.usage_limit || null,
        start_date: promotion?.start_date || '',
        end_date: promotion?.end_date || '',
        status: promotion?.status || 'active',
    });

    // Handle form submit
    const handleSubmit = (e) => {
        e.preventDefault();

        if (isEdit) {
            put(`/admin/promotions/${promotion.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    window.location.href = '/admin/promotions';
                },
            });
        } else {
            post('/admin/promotions', {
                preserveScroll: true,
                onSuccess: () => {
                    window.location.href = '/admin/promotions';
                },
            });
        }
    };

    // Handle date range change
    const handleDateRangeChange = (dates) => {
        if (dates) {
            setData({
                ...data,
                start_date: dates[0].format('YYYY-MM-DD HH:mm:ss'),
                end_date: dates[1].format('YYYY-MM-DD HH:mm:ss'),
            });
        } else {
            setData({
                ...data,
                start_date: '',
                end_date: '',
            });
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title={isEdit ? 'Sửa mã khuyến mãi' : 'Thêm mã khuyến mãi'} />

            <Card>
                <Space style={{ marginBottom: 24 }}>
                    <Button
                        icon={<ArrowLeftOutlined />}
                        onClick={() => router.visit('/admin/promotions')}
                    >
                        Quay lại
                    </Button>
                    <span style={{ fontSize: 20, fontWeight: 'bold' }}>
                        {isEdit ? '✏️ Sửa mã khuyến mãi' : '➕ Thêm mã khuyến mãi mới'}
                    </span>
                </Space>

                <Divider />

                <form onSubmit={handleSubmit}>
                    <Row gutter={24}>
                        <Col xs={24} md={12}>
                            <Card title="Thông tin cơ bản" size="small" style={{ marginBottom: 24 }}>
                                {/* Mã khuyến mãi */}
                                <Form.Item
                                    label="Mã khuyến mãi"
                                    validateStatus={errors.code ? 'error' : ''}
                                    help={errors.code}
                                    required
                                >
                                    <Space.Compact style={{ width: '100%' }}>
                                        <Input
                                            size="large"
                                            placeholder="VD: SUMMER2026"
                                            value={data.code}
                                            onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                            maxLength={50}
                                        />
                                        <Button
                                            size="large"
                                            onClick={async () => {
                                                const res = await axios.get('/admin/promotions-generate-code');
                                                setData('code', res.data.code);
                                            }}
                                        >
                                            Tự sinh mã
                                        </Button>
                                    </Space.Compact>
                                    <div style={{ fontSize: 12, color: '#999', marginTop: 4 }}>
                                        Mã sẽ tự động chuyển thành chữ IN HOA
                                    </div>
                                </Form.Item>

                                {/* Mô tả */}
                                <Form.Item
                                    label="Mô tả"
                                    validateStatus={errors.description ? 'error' : ''}
                                    help={errors.description}
                                >
                                    <TextArea
                                        rows={3}
                                        placeholder="Giảm 20% cho đơn hàng đầu tiên..."
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        maxLength={500}
                                        showCount
                                    />
                                </Form.Item>

                                {/* Loại giảm giá */}
                                <Form.Item
                                    label="Loại giảm giá"
                                    validateStatus={errors.discount_type ? 'error' : ''}
                                    help={errors.discount_type}
                                    required
                                >
                                    <Radio.Group
                                        size="large"
                                        value={data.discount_type}
                                        onChange={(e) => setData('discount_type', e.target.value)}
                                    >
                                        <Radio.Button value="percent">Phần trăm (%)</Radio.Button>
                                        <Radio.Button value="fixed">Số tiền cố định (VNĐ)</Radio.Button>
                                    </Radio.Group>
                                </Form.Item>

                                {/* Giá trị giảm */}
                                <Form.Item
                                    label={data.discount_type === 'percent' ? 'Giảm giá (%)' : 'Giảm giá (VNĐ)'}
                                    validateStatus={errors.discount_value ? 'error' : ''}
                                    help={errors.discount_value}
                                    required
                                >
                                    <InputNumber
                                        size="large"
                                        style={{ width: '100%' }}
                                        min={0}
                                        max={data.discount_type === 'percent' ? 100 : undefined}
                                        value={data.discount_value}
                                        onChange={(value) => setData('discount_value', value)}
                                        formatter={(value) => 
                                            data.discount_type === 'percent' 
                                                ? `${value}%`
                                                : `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                                        }
                                        parser={(value) => value.replace(/[%,]/g, '')}
                                    />
                                </Form.Item>

                                {/* Giảm tối đa (chỉ với percent) */}
                                {data.discount_type === 'percent' && (
                                    <Form.Item
                                        label="Giảm tối đa (VNĐ)"
                                        validateStatus={errors.max_discount ? 'error' : ''}
                                        help={errors.max_discount || 'Để trống nếu không giới hạn'}
                                    >
                                        <InputNumber
                                            size="large"
                                            style={{ width: '100%' }}
                                            min={0}
                                            value={data.max_discount}
                                            onChange={(value) => setData('max_discount', value)}
                                            formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                            parser={(value) => value.replace(/,/g, '')}
                                            placeholder="VD: 100000"
                                        />
                                    </Form.Item>
                                )}
                            </Card>
                        </Col>

                        <Col xs={24} md={12}>
                            <Card title="Điều kiện áp dụng" size="small" style={{ marginBottom: 24 }}>
                                {/* Giá trị đơn hàng tối thiểu */}
                                <Form.Item
                                    label="Đơn hàng tối thiểu (VNĐ)"
                                    validateStatus={errors.min_order_value ? 'error' : ''}
                                    help={errors.min_order_value || 'Để trống nếu không yêu cầu'}
                                >
                                    <InputNumber
                                        size="large"
                                        style={{ width: '100%' }}
                                        min={0}
                                        value={data.min_order_value}
                                        onChange={(value) => setData('min_order_value', value)}
                                        formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                        parser={(value) => value.replace(/,/g, '')}
                                        placeholder="VD: 200000"
                                    />
                                </Form.Item>

                                {/* Giới hạn số lần sử dụng */}
                                <Form.Item
                                    label="Giới hạn số lần sử dụng"
                                    validateStatus={errors.usage_limit ? 'error' : ''}
                                    help={errors.usage_limit || 'Để trống nếu không giới hạn'}
                                >
                                    <InputNumber
                                        size="large"
                                        style={{ width: '100%' }}
                                        min={1}
                                        value={data.usage_limit}
                                        onChange={(value) => setData('usage_limit', value)}
                                        placeholder="VD: 100"
                                    />
                                </Form.Item>

                                {/* Thời gian áp dụng */}
                                <Form.Item
                                    label="Thời gian áp dụng"
                                    validateStatus={errors.start_date || errors.end_date ? 'error' : ''}
                                    help={errors.start_date || errors.end_date}
                                    required
                                >
                                    <RangePicker
                                        size="large"
                                        style={{ width: '100%' }}
                                        showTime
                                        format="DD/MM/YYYY HH:mm"
                                        value={
                                            data.start_date && data.end_date
                                                ? [dayjs(data.start_date), dayjs(data.end_date)]
                                                : null
                                        }
                                        onChange={handleDateRangeChange}
                                    />
                                </Form.Item>

                                {/* Trạng thái */}
                                <Form.Item
                                    label="Trạng thái"
                                    validateStatus={errors.status ? 'error' : ''}
                                    help={errors.status}
                                    required
                                >
                                    <Radio.Group
                                        size="large"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                    >
                                        <Radio.Button value="active">Kích hoạt</Radio.Button>
                                        <Radio.Button value="inactive">Vô hiệu hóa</Radio.Button>
                                    </Radio.Group>
                                </Form.Item>

                                {isEdit && (
                                    <Form.Item label="Đã sử dụng">
                                        <Input
                                            size="large"
                                            value={`${promotion.used_count} lần`}
                                            disabled
                                        />
                                    </Form.Item>
                                )}
                            </Card>
                        </Col>
                    </Row>

                    {/* Submit buttons */}
                    <Space size="large" style={{ width: '100%', justifyContent: 'center' }}>
                        <Button
                            size="large"
                            onClick={() => router.visit('/admin/promotions')}
                        >
                            Hủy
                        </Button>
                        <Button
                            type="primary"
                            size="large"
                            icon={<SaveOutlined />}
                            htmlType="submit"
                            loading={processing}
                        >
                            {isEdit ? 'Cập nhật' : 'Thêm mới'}
                        </Button>
                    </Space>
                </form>
            </Card>
        </AdminLayout>
    );
}