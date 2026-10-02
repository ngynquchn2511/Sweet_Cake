// resources/js/Pages/Admin/Login.jsx
import { useState } from 'react';
import { router, Head } from '@inertiajs/react';
import { Form, Input, Button, Card, message, Typography, Alert } from 'antd';
import { UserOutlined, LockOutlined, SafetyOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;

export default function AdminLogin({ errors }) {
  const [loading, setLoading] = useState(false);

  const handleSubmit = (values) => {
    setLoading(true);
    
    router.post('/admin/login', values, {
      onSuccess: () => {
        message.success('Đăng nhập thành công!');
      },
      onError: (errors) => {
        message.error(errors.email || 'Đăng nhập thất bại');
        setLoading(false);
      },
      onFinish: () => {
        setLoading(false);
      },
    });
  };

  return (
    <>
      <Head title="Admin Login" />
      
      <div
        style={{
          minHeight: '100vh',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)',
          padding: '20px',
        }}
      >
        <Card
          style={{
            width: '100%',
            maxWidth: 420,
            boxShadow: '0 20px 60px rgba(0,0,0,0.4)',
          }}
        >
          <div style={{ textAlign: 'center', marginBottom: 30 }}>
            <div style={{ 
              fontSize: 48, 
              marginBottom: 16,
              background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)',
              WebkitBackgroundClip: 'text',
              WebkitTextFillColor: 'transparent',
            }}>
              <SafetyOutlined />
            </div>
            <Title level={2} style={{ marginBottom: 8, color: '#1e3c72' }}>
              🎂 Admin Panel
            </Title>
            <Text type="secondary">Sales Cake Cake - Quản trị hệ thống</Text>
          </div>

          {errors.email && (
            <Alert
              message={errors.email}
              type="error"
              showIcon
              style={{ marginBottom: 20 }}
              closable
            />
          )}

          <Form
            name="admin-login"
            onFinish={handleSubmit}
            autoComplete="off"
            layout="vertical"
            size="large"
          >
            <Form.Item
              name="email"
              rules={[
                { required: true, message: 'Vui lòng nhập email!' },
                { type: 'email', message: 'Email không hợp lệ!' },
              ]}
            >
              <Input
                prefix={<UserOutlined />}
                placeholder="Email admin"
                autoComplete="email"
              />
            </Form.Item>

            <Form.Item
              name="password"
              rules={[{ required: true, message: 'Vui lòng nhập mật khẩu!' }]}
            >
              <Input.Password
                prefix={<LockOutlined />}
                placeholder="Mật khẩu"
                autoComplete="current-password"
              />
            </Form.Item>

            <Form.Item>
              <Button
                type="primary"
                htmlType="submit"
                loading={loading}
                block
                size="large"
                style={{
                  background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)',
                  borderColor: 'transparent',
                  height: 48,
                  fontSize: 16,
                  fontWeight: 'bold',
                }}
              >
                {loading ? 'Đang đăng nhập...' : 'Đăng nhập Admin'}
              </Button>
            </Form.Item>
          </Form>

          <div style={{ 
            textAlign: 'center', 
            marginTop: 24,
            padding: '16px',
            background: '#f5f5f5',
            borderRadius: 8,
          }}>
            <Text type="secondary" style={{ fontSize: 12 }}>
              <strong>🔐 Test Admin Account:</strong><br/>
              admin@cakes.com / password
            </Text>
          </div>
        </Card>
      </div>
    </>
  );
}