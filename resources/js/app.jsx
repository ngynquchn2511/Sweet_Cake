import './bootstrap';
import '../css/app.css'; // Đảm bảo đã có file này
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ConfigProvider } from 'antd';
import viVN from 'antd/locale/vi_VN';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    // Sử dụng resolvePageComponent để code sạch hơn
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob(['./Pages/**/*.jsx', '!./Pages/**/__tests__/**'])),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <ConfigProvider locale={viVN}>
                <App {...props} />
            </ConfigProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});