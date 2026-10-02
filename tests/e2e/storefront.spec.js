import { test, expect } from '@playwright/test';

// Kiểm thử trình duyệt thật cho các màn hình được bổ sung/sửa ở đợt khắc phục lỗi lần 2.
// Chỉ dùng luồng khách vãng lai (không ghi dữ liệu) để chạy lặp lại an toàn trên CSDL dev.

test.describe('Cửa hàng — màn hình khách hàng', () => {
    // SPDM-ST-006, SPDM-ST-015, SPDM-ST-024
    test('bấm vào sản phẩm ở trang Cửa hàng mở được trang chi tiết', async ({ page }) => {
        await page.goto('/products');
        const firstProduct = page.locator('h3 a').first();
        const name = (await firstProduct.textContent()).trim();

        await firstProduct.click();

        await expect(page.getByRole('heading', { level: 1, name })).toBeVisible();
        await expect(page.getByTestId('detail-add-to-cart')).toBeVisible();
        await expect(page.getByRole('heading', { name: /Đánh giá từ khách hàng/ })).toBeVisible();
    });

    // SPDM-ST-024: trang chi tiết dùng được ở mobile
    test('trang chi tiết sản phẩm hiển thị tốt ở mobile', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/products');
        await page.locator('h3 a').first().click();

        await expect(page.getByTestId('detail-add-to-cart')).toBeVisible();
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
        expect(overflow).toBe(false);
    });

    // SPDM-ST-009: khách chưa đăng nhập bấm "yêu thích" được chuyển tới trang đăng nhập
    test('khách vãng lai bấm trái tim yêu thích được yêu cầu đăng nhập', async ({ page }) => {
        await page.goto('/products');
        await page.getByRole('button', { name: 'Thêm vào yêu thích' }).first().click();

        await expect(page).toHaveURL(/\/login$/);
    });

    // LHTN-ST-001 (phần kiểm tra dữ liệu trống): form hỏi đáp báo lỗi ngay trên từng ô
    test('trang Liên hệ hiển thị 2 form và báo lỗi khi bỏ trống', async ({ page }) => {
        await page.goto('/contact');

        await expect(page.getByRole('heading', { name: 'Gửi liên hệ' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Hỏi đáp nhanh' })).toBeVisible();

        await page.getByRole('button', { name: 'Gửi câu hỏi' }).click();

        await expect(page.getByRole('alert')).toContainText('Vui lòng kiểm tra lại thông tin');
        await expect(page.locator('#content')).toHaveClass(/is-invalid/);
    });

    // HOME-ST-003: trang chủ có sản phẩm mới
    test('trang chủ hiển thị sản phẩm mới nhất', async ({ page }) => {
        await page.goto('/');

        await expect(page.getByRole('heading', { name: 'SẢN PHẨM MỚI NHẤT' })).toBeVisible();
        await expect(page.getByRole('button', { name: /THÊM VÀO GIỎ HÀNG/ }).first()).toBeVisible();
    });
});
