import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    // `php artisan serve` là server 1 luồng — chạy song song nhiều worker dễ làm
    // request bị treo/timeout, nên giới hạn 1 worker cho ổn định.
    workers: 1,
    fullyParallel: false,
    reporter: 'html',
    timeout: 60_000,
    expect: { timeout: 15_000 },
    use: {
        baseURL: 'http://127.0.0.1:8000',
        navigationTimeout: 60_000,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        // channel: 'chromium' dùng thẳng bản Chromium đầy đủ đã tải (chạy headless qua
        // cờ --headless), tránh phải tải thêm gói "chromium-headless-shell" riêng —
        // gói đó tải từ cdn.playwright.dev, domain này không phân giải được trong mạng máy này.
        { name: 'chromium', use: { ...devices['Desktop Chrome'], channel: 'chromium' } },
    ],
    // Chạy trên bản giao diện đã build (npm run build) thay vì Vite dev server: dev server chỉ biên dịch
    // JSX khi trang được mở lần đầu nên trên máy chậm lần tải đầu có thể > 30 giây và làm test timeout.
    // Lưu ý: nếu đang bật "npm run dev" (có file public/hot) thì Laravel sẽ lại dùng dev server.
    webServer: [
        {
            command: 'php artisan serve',
            url: 'http://127.0.0.1:8000',
            reuseExistingServer: true,
            timeout: 120_000,
        },
    ],
});
