import { test, expect } from '@playwright/test';

// Test trình duyệt thật cho phần "trực quan" mà PHPUnit không kiểm được:
// ST-01, ST-02, ST-12, ST-13 trong bộ testcase màn hình Đăng nhập.

test.describe('Màn hình Đăng nhập — UI', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        // Đợi giao diện React render xong rồi mới kiểm tra/chụp ảnh
        await expect(page.getByRole('button', { name: 'ĐĂNG NHẬP NGAY' })).toBeVisible();
    });

    // ST-01
    test('hiển thị đầy đủ các thành phần', async ({ page }) => {
        await expect(page.getByLabel('Địa chỉ Email')).toBeVisible();
        await expect(page.getByLabel('Mật khẩu')).toBeVisible();
        await expect(page.getByLabel('Ghi nhớ tôi')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Quên mật khẩu?' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'ĐĂNG NHẬP NGAY' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Đăng ký miễn phí' })).toBeVisible();
    });

    // ST-02 (chụp ảnh so sánh layout tổng thể)
    // Lần chạy đầu tiên: `npx playwright test --update-snapshots` để tạo ảnh gốc.
    // Các lần sau, nếu layout đổi khác ảnh gốc, test sẽ Fail và có ảnh diff trong report.
    test('bố cục tổng thể khớp ảnh chụp gốc', async ({ page }) => {
        await expect(page).toHaveScreenshot('login-desktop.png', { fullPage: true });
    });

    // ST-12
    test('vẫn dùng được ở kích thước mobile và tablet', async ({ page }) => {
        for (const viewport of [
            { width: 375, height: 812 },
            { width: 768, height: 1024 },
        ]) {
            await page.setViewportSize(viewport);
            await expect(page.getByLabel('Địa chỉ Email')).toBeVisible();
            await expect(page.getByRole('button', { name: 'ĐĂNG NHẬP NGAY' })).toBeVisible();
        }
    });

    // ST-13
    test('ô mật khẩu ẩn ký tự khi nhập', async ({ page }) => {
        const passwordInput = page.getByLabel('Mật khẩu');
        await passwordInput.fill('Khach123');

        await expect(passwordInput).toHaveAttribute('type', 'password');
    });
});
