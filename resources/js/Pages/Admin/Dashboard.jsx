// resources/js/Pages/Admin/Dashboard.jsx
import { Head } from '@inertiajs/react';
import { Card, Row, Col, Statistic, Table, Typography, Tag } from 'antd';
import {
  ShoppingCartOutlined,
  UserOutlined,
  DollarOutlined,
  AppstoreOutlined,
  RiseOutlined,
  TeamOutlined,
  WarningOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/Layouts/AdminLayout';

const { Title, Text } = Typography;

export default function Dashboard({ auth, stats }) {
  // Format currency
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
    }).format(value);
  };

  // Top selling products columns
  const productColumns = [
    {
      title: 'Sản phẩm',
      dataIndex: 'name',
      key: 'name',
      render: (text, record) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          {record.image && (
            <img 
              src={`/${record.image}`} 
              alt={text}
              style={{ width: 40, height: 40, objectFit: 'cover', borderRadius: 4 }}
            />
          )}
          <Text strong>{text}</Text>
        </div>
      ),
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

  // Low stock products columns
  const stockColumns = [
    {
      title: 'Sản phẩm',
      dataIndex: 'name',
      key: 'name',
    },
    {
      title: 'Tồn kho',
      dataIndex: 'stock',
      key: 'stock',
      align: 'center',
      render: (stock) => (
        <Tag color={stock <= 5 ? 'red' : 'orange'}>
          {stock} {stock <= 5 && '⚠️'}
        </Tag>
      ),
    },
  ];

  return (
    <AdminLayout user={auth.user}>
      <Head title="Dashboard" />

      <div>
        <Title level={2} style={{ marginBottom: 24 }}>
          Dashboard
        </Title>

        {/* Revenue Stats */}
        <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
          <Col xs={24} sm={12} lg={6}>
            <Card>
              <Statistic
                title="Doanh thu hôm nay"
                value={stats.revenue_today}
                prefix={<DollarOutlined />}
                suffix="đ"
                styles={{ value: { color: '#3f8600', fontSize: 20 } }}
              />
            </Card>
          </Col>

          <Col xs={24} sm={12} lg={6}>
            <Card>
              <Statistic
                title="Doanh thu tuần này"
                value={stats.revenue_week}
                prefix={<RiseOutlined />}
                suffix="đ"
                styles={{ value: { color: '#1890ff', fontSize: 20 } }}
              />
            </Card>
          </Col>

          <Col xs={24} sm={12} lg={6}>
            <Card>
              <Statistic
                title="Doanh thu tháng này"
                value={stats.revenue_month}
                prefix={<DollarOutlined />}
                suffix="đ"
                styles={{ value: { color: '#722ed1', fontSize: 20 } }}
              />
            </Card>
          </Col>

          <Col xs={24} sm={12} lg={6}>
            <Card>
              <Statistic
                title="Doanh thu năm nay"
                value={stats.revenue_year}
                prefix={<DollarOutlined />}
                suffix="đ"
                styles={{ value: { color: '#cf1322', fontSize: 20 } }}
              />
            </Card>
          </Col>
        </Row>

        {/* General Stats */}
        <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
          <Col xs={24} sm={12} lg={6}>
            <Card>
              <Statistic
                title="Tổng đơn hàng"
                value={stats.total_orders}
                prefix={<ShoppingCartOutlined />}
                styles={{ value: { color: '#3f8600' } }}
              />
              <Text type="secondary" style={{ fontSize: 12 }}>
                Chờ xử lý: {stats.pending_orders}
              </Text>
            </Card>
          </Col>

          <Col xs={24} sm={12} lg={6}>
            <Card>
              <Statistic
                title="Khách hàng mới"
                value={stats.new_customers_month}
                prefix={<TeamOutlined />}
                styles={{ value: { color: '#1890ff' } }}
              />
              <Text type="secondary" style={{ fontSize: 12 }}>
                Tổng: {stats.active_customers}
              </Text>
            </Card>
          </Col>

          <Col xs={24} sm={12} lg={6}>
            <Card>
              <Statistic
                title="Tổng sản phẩm"
                value={stats.total_products}
                prefix={<AppstoreOutlined />}
                styles={{ value: { color: '#722ed1' } }}
              />
              <Text type="secondary" style={{ fontSize: 12 }}>
                Đang bán: {stats.active_products}
              </Text>
            </Card>
          </Col>

          <Col xs={24} sm={12} lg={6}>
            <Card>
              <Statistic
                title="Sắp hết hàng (≤ 10)"
                value={stats.low_stock}
                prefix={<WarningOutlined />}
                styles={{ value: { color: '#d48806' } }}
              />
              <Text type="secondary" style={{ fontSize: 12 }}>
                Hết hàng: <b style={{ color: '#cf1322' }}>{stats.out_of_stock}</b> sản phẩm
              </Text>
            </Card>
          </Col>
        </Row>

        {/* Top Selling Products & Low Stock */}
        <Row gutter={[16, 16]}>
          <Col xs={24} lg={12}>
            <Card 
              title={<Text strong>🔥 Sản phẩm bán chạy</Text>}
              style={{ height: '100%' }}
            >
              <Table
                columns={productColumns}
                dataSource={stats.top_selling_products}
                pagination={false}
                size="small"
                rowKey="id"
              />
            </Card>
          </Col>

          <Col xs={24} lg={12}>
            <Card 
              title={<Text strong>⚠️ Sản phẩm sắp hết hàng</Text>}
              style={{ height: '100%' }}
            >
              <Table
                columns={stockColumns}
                dataSource={stats.low_stock_products}
                pagination={false}
                size="small"
                rowKey="id"
              />
            </Card>
          </Col>
        </Row>

        {/* Doanh thu 7 ngày gần nhất & Khách hàng chi tiêu nhiều nhất */}
        <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
          <Col xs={24} lg={12}>
            <Card title={<Text strong>📈 Doanh thu 7 ngày gần nhất</Text>} style={{ height: '100%' }}>
              <Table
                size="small"
                pagination={false}
                rowKey="label"
                dataSource={(stats.revenue_chart_7_days?.labels || []).map((label, i) => ({
                  label,
                  revenue: stats.revenue_chart_7_days.data[i],
                }))}
                columns={[
                  { title: 'Ngày', dataIndex: 'label', key: 'label' },
                  { title: 'Doanh thu', dataIndex: 'revenue', key: 'revenue', align: 'right', render: (v) => formatCurrency(v) },
                ]}
              />
            </Card>
          </Col>
          <Col xs={24} lg={12}>
            <Card title={<Text strong>👑 Khách hàng chi tiêu nhiều nhất</Text>} style={{ height: '100%' }}>
              <Table
                size="small"
                pagination={false}
                rowKey="id"
                dataSource={stats.top_customers || []}
                columns={[
                  { title: 'Khách hàng', dataIndex: 'full_name', key: 'full_name' },
                  { title: 'Số đơn', dataIndex: 'total_orders', key: 'total_orders', align: 'center' },
                  { title: 'Tổng chi tiêu', dataIndex: 'total_spent', key: 'total_spent', align: 'right', render: (v) => formatCurrency(v) },
                ]}
              />
            </Card>
          </Col>
        </Row>
      </div>
    </AdminLayout>
  );
}