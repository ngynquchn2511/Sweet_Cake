import { defineConfig } from 'vitest/config';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig({
    // Vitest 4 dùng Vite 8 riêng (biên dịch bằng oxc), tự xử lý JSX được nên không cần @vitejs/plugin-react
    // (plugin bản 4.x vẫn đặt tuỳ chọn esbuild cũ → sinh cảnh báo "esbuild option ... deprecated").
    oxc: {
        jsx: { runtime: 'automatic' },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    test: {
        environment: 'jsdom',
        // Chạy bằng worker thread thay vì tiến trình con (forks): khởi động nhanh hơn nhiều trên Windows,
        // tránh lỗi "Failed to start forks worker / Timeout waiting for worker to respond" khi máy đang bận.
        pool: 'threads',
        maxWorkers: 1,
        setupFiles: ['./resources/js/test/setup.js'],
        globals: true,
        css: false,
        // tests/e2e/*.spec.js là kịch bản Playwright, không phải test Vitest
        include: ['resources/js/**/*.test.{js,jsx}'],
        exclude: ['node_modules/**', 'tests/e2e/**'],
        // Máy chậm/đang bận: cho phép khởi động môi trường jsdom lâu hơn trước khi báo lỗi
        testTimeout: 30000,
        hookTimeout: 120000,
        teardownTimeout: 60000,
    },
});
