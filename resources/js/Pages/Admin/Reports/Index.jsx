// resources/js/Pages/Admin/Reports/Index.jsx
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { 
    Card, Row, Col, Statistic, DatePicker, Button, Select, Table, Progress, Tag, 
} from 'antd';
import {
    DollarOutlined, ShoppingCartOutlined, UserOutlined, 
    RiseOutlined, DownloadOutlined, ReloadOutlined,
} from '@ant-design/icons';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Line, Bar, Pie } from 'react-chartjs-2';
import AdminLayout from '@/Layouts/AdminLayout';
import dayjs from 'dayjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
);

const { RangePicker } = DatePicker;
const { Option } = Select;

export default function ReportsIndex({ 
    auth, 
    stats, 
    revenueChart, 
    topProducts, 
    revenueByCategory,
    orderStatusStats,
    topCustomers,
    topReviewedProducts,
    filters = {} 
}) {
    const [chartType, setChartType] = useState(filters.chart_type || 'daily');

    // Format currency
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(value);
    };

    // Handle date range change
    const handleDateRangeChange = (dates) => {
        if (dates) {
            router.get('/admin/reports', {
                from_date: dates[0].format('YYYY-MM-DD'),
                to_date: dates[1].format('YYYY-MM-DD'),
                chart_type: chartType,
            });
        }
    };

    // Handle chart type change
    const handleChartTypeChange = (value) => {
        setChartType(value);
        router.get('/admin/reports', {
            from_date: filters.from_date,
            to_date: filters.to_date,
            chart_type: value,
        });
    };

    // Revenue Line Chart Config
    const revenueLineData = {
        labels: revenueChart.labels,
        datasets: [
            {
                label: 'Doanh thu (VNĐ)',
                data: revenueChart.data,
                borderColor: 'rgb(24, 144, 255)',
                backgroundColor: 'rgba(24, 144, 255, 0.1)',
                tension: 0.4,
                fill: true,
            },
        ],
    };

    const lineOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: (context) => formatCurrency(context.parsed.y),
                },
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: (value) => formatCurrency(value),
                },
            },
        },
    };

    // Top Products Bar Chart Config
    const topProductsData = {
        labels: topProducts.map(p => p.name),
        datasets: [
            {
                label: 'Số lượng bán',
                data: topProducts.map(p => p.total_sold),
                backgroundColor: 'rgba(82, 196, 26, 0.6)',
                borderColor: 'rgb(82, 196, 26)',
                borderWidth: 1,
            },
        ],
    };

    const barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
        },
        scales: {
            y: {
                beginAtZero: true,
            },
        },
    };

    // Category Pie Chart Config
    const categoryData = {
        labels: revenueByCategory.map(c => c.name),
        datasets: [
            {
                data: revenueByCategory.map(c => c.total_revenue),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                ],
                borderWidth: 1,
            },
        ],
    };

    const pieOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
            },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const label = context.label || '';
                        const value = formatCurrency(context.parsed);
                        return `${label}: ${value}`;
                    },
                },
            },
        },
    };

    // Top Products Table Columns
    const productColumns = [
        {
            title: 'Sản phẩm',
            dataIndex: 'name',
            key: 'name',
        },
        {
            title: 'Đã bán',
            dataIndex: 'total_sold',
            key: 'total_sold',
            align: 'center',
        },
        {
            title: 'Doanh thu',
            dataIndex: 'total_revenue',
            key: 'total_revenue',
            align: 'right',
            render: (value) => formatCurrency(value),
        },
    ];

    // Top Customers Table Columns
    const customerColumns = [
        {
            title: 'Khách hàng',
            dataIndex: 'full_name',
            key: 'full_name',
        },
        {
            title: 'Số đơn',
            dataIndex: 'total_orders',
            key: 'total_orders',
            align: 'center',
        },
        {
            title: 'Tổng chi tiêu',
            dataIndex: 'total_spent',
            key: 'total_spent',
            align: 'right',
            render: (value) => formatCurrency(value),
        },
    ];

    return (
        <AdminLayout user={auth.user}>
            <Head title="Báo cáo & Thống kê" />

            <div style={{ marginBottom: 16 }}>
                <Row gutter={16} align="middle">
                    <Col flex="auto">
                        <h2 style={{ margin: 0, fontSize: 24, fontWeight: 'bold' }}>
                            📊 Báo cáo & Thống kê
                        </h2>
                    </Col>
                    <Col>
                        <RangePicker
                            size="large"
                            defaultValue={[
                                dayjs(filters.from_date),
                                dayjs(filters.to_date),
                            ]}
                            format="DD/MM/YYYY"
                            onChange={handleDateRangeChange}
                        />
                    </Col>
                    <Col>
                        <Button
                            size="large"
                            icon={<ReloadOutlined />}
                            onClick={() => router.reload()}
                        >
                            Làm mới
                        </Button>
                    </Col>
                    <Col>
                        <Button
                            size="large"
                            type="primary"
                            icon={<DownloadOutlined />}
                            href={`/admin/reports/export?from_date=${filters.from_date || ''}&to_date=${filters.to_date || ''}`}
                        >
                            Xuất báo cáo
                        </Button>
                    </Col>
                </Row>
            </div>

            {/* Overview Stats */}
            <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                <Col xs={24} sm={12} lg={8}>
                    <Card>
                        <Statistic
                            title="Tổng doanh thu"
                            value={stats.total_revenue}
                            formatter={(value) => formatCurrency(value)}
                            prefix={<DollarOutlined />}
                            valueStyle={{ color: '#3f8600' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={8}>
                    <Card>
                        <Statistic
                            title="Tổng đơn hàng"
                            value={stats.total_orders}
                            prefix={<ShoppingCartOutlined />}
                            valueStyle={{ color: '#1890ff' }}
                        />
                        <div style={{ fontSize: 12, color: '#999', marginTop: 8 }}>
                            Hoàn thành: {stats.completed_orders}
                        </div>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={8}>
                    <Card>
                        <Statistic
                            title="Khách hàng mới"
                            value={stats.total_customers}
                            prefix={<UserOutlined />}
                            valueStyle={{ color: '#cf1322' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={8}>
                    <Card>
                        <Statistic
                            title="Sản phẩm đã bán"
                            value={stats.total_products_sold}
                            prefix={<RiseOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={8}>
                    <Card>
                        <Statistic
                            title="Giá trị đơn TB"
                            value={stats.avg_order_value}
                            formatter={(value) => formatCurrency(value)}
                            valueStyle={{ color: '#722ed1' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={8}>
                    <Card>
                        <Statistic
                            title="Tỷ lệ hoàn thành"
                            value={
                                stats.total_orders > 0
                                    ? ((stats.completed_orders / stats.total_orders) * 100).toFixed(1)
                                    : 0
                            }
                            suffix="%"
                            valueStyle={{ color: '#52c41a' }}
                        />
                        <Progress 
                            percent={
                                stats.total_orders > 0
                                    ? ((stats.completed_orders / stats.total_orders) * 100)
                                    : 0
                            } 
                            showInfo={false}
                            strokeColor="#52c41a"
                        />
                    </Card>
                </Col>
            </Row>

            {/* Revenue Chart */}
            <Card 
                title="Biểu đồ doanh thu" 
                style={{ marginBottom: 16 }}
                extra={
                    <Select
                        value={chartType}
                        onChange={handleChartTypeChange}
                        style={{ width: 150 }}
                    >
                        <Option value="daily">Theo ngày</Option>
                        <Option value="weekly">Theo tuần</Option>
                        <Option value="monthly">Theo tháng</Option>
                    </Select>
                }
            >
                <div style={{ height: 350 }}>
                    <Line data={revenueLineData} options={lineOptions} />
                </div>
            </Card>

            <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                {/* Top Products Chart */}
                <Col xs={24} lg={12}>
                    <Card title="Top 10 sản phẩm bán chạy">
                        <div style={{ height: 350 }}>
                            <Bar data={topProductsData} options={barOptions} />
                        </div>
                    </Card>
                </Col>

                {/* Revenue by Category */}
                <Col xs={24} lg={12}>
                    <Card title="Doanh thu theo danh mục">
                        <div style={{ height: 350 }}>
                            <Pie data={categoryData} options={pieOptions} />
                        </div>
                    </Card>
                </Col>
            </Row>

            <Row gutter={[16, 16]}>
                {/* Top Products Table */}
                <Col xs={24} lg={12}>
                    <Card title="Chi tiết sản phẩm bán chạy">
                        <Table
                            columns={productColumns}
                            dataSource={topProducts}
                            rowKey="id"
                            pagination={false}
                            size="small"
                        />
                    </Card>
                </Col>

                {/* Top Customers Table */}
                <Col xs={24} lg={12}>
                    <Card title="Khách hàng thân thiết">
                        <Table
                            columns={customerColumns}
                            dataSource={topCustomers}
                            rowKey="id"
                            pagination={false}
                            size="small"
                        />
                    </Card>
                </Col>
            </Row>

            {/* Top Reviewed Products */}
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col xs={24}>
                    <Card title="⭐ Top 10 sản phẩm đánh giá tốt nhất">
                        <Table
                            columns={[
                                {
                                    title: 'Sản phẩm',
                                    key: 'product',
                                    render: (_, record) => (
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                            <div style={{
                                                width: 50, height: 50, borderRadius: 8,
                                                overflow: 'hidden', border: '1px solid #d9d9d9',
                                            }}>
                                                {record.image ? (
                                                    <img
                                                        src={`/${record.image}`}
                                                        alt={record.name}
                                                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                                        onError={(e) => {
                                                            e.target.style.display = 'none';
                                                            e.target.parentElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:20px;">🍰</div>';
                                                        }}
                                                    />
                                                ) : (
                                                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '100%', fontSize: 20 }}>
                                                        🍰
                                                    </div>
                                                )}
                                            </div>
                                            <div style={{ fontWeight: 500 }}>{record.name}</div>
                                        </div>
                                    ),
                                },
                                {
                                    title: 'Đánh giá TB',
                                    dataIndex: 'avg_rating',
                                    key: 'avg_rating',
                                    width: 150,
                                    align: 'center',
                                    render: (rating) => (
                                        <div style={{ fontSize: 18, fontWeight: 'bold', color: '#faad14' }}>
                                            {rating} ⭐
                                        </div>
                                    ),
                                    sorter: (a, b) => a.avg_rating - b.avg_rating,
                                },
                                {
                                    title: 'Số đánh giá',
                                    dataIndex: 'review_count',
                                    key: 'review_count',
                                    width: 120,
                                    align: 'center',
                                    render: (count) => (
                                        <div style={{ color: '#1890ff', fontWeight: 500 }}>
                                            {count} đánh giá
                                        </div>
                                    ),
                                    sorter: (a, b) => a.review_count - b.review_count,
                                },
                                {
                                    title: 'Giá',
                                    dataIndex: 'price',
                                    key: 'price',
                                    width: 150,
                                    align: 'right',
                                    render: (price) => formatCurrency(price),
                                },
                            ]}
                            dataSource={topReviewedProducts || []}
                            rowKey="id"
                            pagination={false}
                        />
                    </Card>
                </Col>
            </Row>

            <InventoryReport />
        </AdminLayout>
    );
}

// Báo cáo tồn kho chi tiết (menu con "Báo cáo tồn kho")
function InventoryReport() {
    const [report, setReport] = useState(null);
    const [loading, setLoading] = useState(false);

    const load = async () => {
        setLoading(true);
        try {
            const res = await axios.get('/admin/reports/inventory');
            setReport(res.data.data);
        } finally {
            setLoading(false);
        }
    };

    const statusTag = {
        out_of_stock: <Tag color="red">Hết hàng</Tag>,
        low_stock: <Tag color="orange">Sắp hết</Tag>,
        in_stock: <Tag color="green">Còn hàng</Tag>,
    };

    return (
        <Card
            title="📦 Báo cáo tồn kho"
            style={{ marginTop: 16 }}
            extra={<Button onClick={load} loading={loading}>{report ? 'Tải lại' : 'Xem báo cáo tồn kho'}</Button>}
        >
            {report ? (
                <>
                    <Row gutter={16} style={{ marginBottom: 16 }}>
                        <Col xs={12}><Statistic title="Hết hàng" value={report.out_of_stock_count} valueStyle={{ color: '#cf1322' }} /></Col>
                        <Col xs={12}><Statistic title="Sắp hết hàng (≤ 10)" value={report.low_stock_count} valueStyle={{ color: '#d48806' }} /></Col>
                    </Row>
                    <Table
                        size="small"
                        rowKey="id"
                        dataSource={report.products}
                        pagination={{ pageSize: 10 }}
                        columns={[
                            { title: 'Sản phẩm', dataIndex: 'name', key: 'name' },
                            { title: 'Tồn kho', dataIndex: 'stock', key: 'stock', align: 'center' },
                            { title: 'Đã bán', dataIndex: 'sold_count', key: 'sold_count', align: 'center' },
                            { title: 'Tình trạng', dataIndex: 'inventory_status', key: 'inventory_status', align: 'center', render: (s) => statusTag[s] },
                        ]}
                    />
                </>
            ) : (
                <div style={{ color: '#999' }}>Bấm "Xem báo cáo tồn kho" để tải danh sách tồn kho hiện tại.</div>
            )}
        </Card>
    );
}