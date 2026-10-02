// resources/js/Layouts/AdminLayout.jsx
import { useState, useEffect } from 'react';
import { Layout, Menu, Button, Avatar, Dropdown, Space, Typography, Badge } from 'antd';
import {
  MenuFoldOutlined,
  MenuUnfoldOutlined,
  DashboardOutlined,
  ShoppingCartOutlined,
  AppstoreOutlined,
  UserOutlined,
  BarChartOutlined,
  LogoutOutlined,
  SettingOutlined,
  ShoppingOutlined,
  GiftOutlined,
  MessageOutlined,
  StarOutlined,
  MailOutlined,
  HistoryOutlined,
  EnvironmentOutlined  
} from '@ant-design/icons';
import { Link, router, usePage } from '@inertiajs/react';

const { Header, Sider, Content } = Layout;
const { Text } = Typography;

export default function AdminLayout({ children, user }) {
  const [collapsed, setCollapsed] = useState(false);
  const [newOrderCount,   setNewOrderCount]   = useState(0);
  const [newMessageCount, setNewMessageCount] = useState(0);
  const page = usePage();

  useEffect(() => {
    if (page.props?.newOrderCount   !== undefined) setNewOrderCount(page.props.newOrderCount);
    if (page.props?.newMessageCount !== undefined) setNewMessageCount(page.props.newMessageCount);
  }, [page.props?.newOrderCount, page.props?.newMessageCount]);
// tải lại trang sau mỗi 10 s
  // useEffect(() => {
//   let loading = false;

//   const interval = setInterval(() => {
//     if (loading) return;

//     loading = true;
//     router.reload({
//       only: ['newOrderCount', 'newMessageCount'],
//       preserveState: true,
//       onFinish: () => loading = false,
//     });
//   }, 10000);

//   return () => clearInterval(interval);
// }, []);


  // Mark read orders khi click — navigate vẫn do Link tự làm
  const markOrdersRead = () => {
    setNewOrderCount(0);
    router.post('/admin/orders/mark-read');
  };

  
  const handleLogout = () => {
    router.post('/admin/logout');
  };

  const menuItems = [
    {
      key: '/admin/dashboard',
      icon: <DashboardOutlined />,
      label: <Link href="/admin/dashboard">Dashboard</Link>,
    },
    {
      key: '/admin/products',
      icon: <ShoppingCartOutlined />,
      label: <Link href="/admin/products">Sản phẩm</Link>,
    },
    {
      key: '/admin/categories',
      icon: <AppstoreOutlined />,
      label: <Link href="/admin/categories">Danh mục</Link>,
    },
    {
      key: '/admin/orders',
      icon: <ShoppingOutlined />,
      label: (
        <Link href="/admin/orders" onClick={markOrdersRead} style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
          Đơn hàng
          {newOrderCount > 0 && (
            <Badge count={newOrderCount} style={{ backgroundColor: '#f5222d', boxShadow: 'none' }} />
          )}
        </Link>
      ),
    },
    {
  key: '/admin/messages',
  icon: <MessageOutlined />,
  label: (
    <Link href="/admin/messages" style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
      Tin nhắn
      {newMessageCount > 0 && (
        <Badge count={newMessageCount} style={{ backgroundColor: '#f5222d', boxShadow: 'none' }} />
      )}
    </Link>
  ),
},

    {
      key: '/admin/contacts',
      icon: <MailOutlined />,
      label: <Link href="/admin/contacts">Liên hệ</Link>,
    },
    {
      key: '/admin/promotions',
      icon: <GiftOutlined />,
      label: <Link href="/admin/promotions">Mã khuyến mãi</Link>,
    },
    {
      key: '/admin/users',
      icon: <UserOutlined />,
      label: <Link href="/admin/users">Khách hàng</Link>,
    },
    {
     key: '/admin/cart-analytics',
     icon: <ShoppingCartOutlined />,
     label: <Link href="/admin/cart-analytics">Giỏ hàng</Link>,
   },
    {
  key: '/admin/reviews',
  icon: <StarOutlined />,
  label: <Link href="/admin/reviews">Đánh giá</Link>,
},
    {
      key: '/admin/reports',
      icon: <BarChartOutlined />,
      label: <Link href="/admin/reports">Báo cáo</Link>,
    },
    {
      key: '/admin/logs',
      icon: <HistoryOutlined />,
      label: <Link href="/admin/logs">Nhật ký hoạt động</Link>,
    },
  ];

  const userMenuItems = [
    {
      key: 'profile',
      icon: <UserOutlined />,
      label: 'Thông tin cá nhân',
      onClick: () => router.visit('/admin/profile'),
    },
    {
      key: 'settings',
      icon: <SettingOutlined />,
      label: 'Cài đặt',
      onClick: () => router.visit('/admin/settings'),
    },
    { type: 'divider' },
    {
      key: 'logout',
      icon: <LogoutOutlined />,
      label: 'Đăng xuất',
      danger: true,
      onClick: handleLogout,
    },
  ];

  return (
    <Layout style={{ minHeight: '100vh' }}>
      <Sider
        trigger={null}
        collapsible
        collapsed={collapsed}
        style={{
          overflow: 'auto',
          height: '100vh',
          position: 'fixed',
          left: 0, top: 0, bottom: 0,
          boxShadow: '2px 0 8px rgba(0,0,0,0.15)',
        }}
        theme="dark"
      >
        <div style={{
          height: 64,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          color: 'white',
          fontSize: collapsed ? 20 : 24,
          fontWeight: 'bold',
          borderBottom: '1px solid rgba(255,255,255,0.1)',
        }}>
          {collapsed ? '' : ' Sales Cake'}
        </div>

        <Menu
          theme="dark"
          mode="inline"
          selectedKeys={[window.location.pathname]}
          items={menuItems}
          style={{ borderRight: 0 }}
        />
      </Sider>

      <Layout style={{ marginLeft: collapsed ? 80 : 200, transition: 'all 0.2s' }}>
        <Header style={{
          padding: '0 24px',
          background: '#fff',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          boxShadow: '0 1px 4px rgba(0,21,41,.08)',
          position: 'sticky',
          top: 0, zIndex: 999,
        }}>
          <Button
            type="text"
            icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
            onClick={() => setCollapsed(!collapsed)}
            style={{ fontSize: '16px', width: 64, height: 64 }}
          />

          <Dropdown menu={{ items: userMenuItems }} placement="bottomRight">
  <Space style={{ cursor: 'pointer' }}>
    <Avatar style={{ backgroundColor: '#1890ff' }} icon={<UserOutlined />} />
    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-start' }}>
      <Text strong>{user?.full_name}</Text>
      <Text type="secondary" style={{ fontSize: 12 }}>Admin</Text>
    </div>
  </Space>
</Dropdown>
        </Header>

        <Content style={{
          margin: '24px',
          padding: 24,
          minHeight: 280,
          background: '#fff',
          borderRadius: 8,
          boxShadow: '0 1px 2px rgba(0,0,0,0.03)',
        }}>
          {children}
        </Content>
      </Layout>
    </Layout>
  );
}