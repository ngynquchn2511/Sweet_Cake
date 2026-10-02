import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import Login from '../Login';

// Login.jsx lấy data/setData/processing từ useForm của Inertia. Trong test không có
// Inertia app thật (router, ...) nên mock lại hook này để kiểm đúng phần logic của
// Login.jsx (nó gọi setData với đúng field/giá trị nào, có disable nút khi processing).
const mockSetData = vi.fn();
let mockFormState;

vi.mock('@inertiajs/react', () => ({
    useForm: () => mockFormState,
    usePage: () => ({ props: { auth: {}, cartCount: 0 } }),
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

// Login.jsx chỉ dùng message của antd; mock lại để không phải nạp cả thư viện antd (rất chậm trong jsdom)
vi.mock('antd', () => ({
    message: { error: vi.fn(), success: vi.fn() },
}));

function renderLogin(overrides = {}) {
    mockFormState = {
        data: { email: '', password: '', remember: false },
        setData: mockSetData,
        post: vi.fn(),
        processing: false,
        errors: {},
        reset: vi.fn(),
        ...overrides,
    };
    return render(<Login canResetPassword={true} />);
}

beforeEach(() => {
    mockSetData.mockClear();
});

describe('Login.jsx', () => {
    // UT-07
    it('gọi setData("email", ...) khi gõ vào ô Địa chỉ Email', () => {
        renderLogin();
        const emailInput = screen.getByLabelText('Địa chỉ Email');

        fireEvent.change(emailInput, { target: { value: 'khach@cake.vn' } });

        expect(mockSetData).toHaveBeenCalledWith('email', 'khach@cake.vn');
    });

    // UT-08
    it('gọi setData("password", ...) và ô Mật khẩu ẩn ký tự (type=password)', () => {
        renderLogin();
        const passwordInput = screen.getByLabelText('Mật khẩu');

        expect(passwordInput).toHaveAttribute('type', 'password');

        fireEvent.change(passwordInput, { target: { value: 'MatKhau123' } });

        expect(mockSetData).toHaveBeenCalledWith('password', 'MatKhau123');
    });

    // UT-09
    it('gọi setData("remember", true) khi click checkbox "Ghi nhớ tôi"', () => {
        renderLogin({ data: { email: '', password: '', remember: false } });
        const rememberCheckbox = screen.getByLabelText('Ghi nhớ tôi');

        fireEvent.click(rememberCheckbox);

        expect(mockSetData).toHaveBeenCalledWith('remember', true);
    });

    // UT-10
    it('vô hiệu nút và hiện "ĐANG XỬ LÝ..." khi processing = true', () => {
        renderLogin({ processing: true });

        const submitButton = screen.getByRole('button', { name: /ĐANG XỬ LÝ/ });

        expect(submitButton).toBeDisabled();
    });

    it('nút hiện "ĐĂNG NHẬP NGAY" và không bị disable khi không xử lý', () => {
        renderLogin({ processing: false });

        const submitButton = screen.getByRole('button', { name: 'ĐĂNG NHẬP NGAY' });

        expect(submitButton).not.toBeDisabled();
    });

    // ST-52
    it('tự động focus vào ô Địa chỉ Email khi trang vừa tải', () => {
        renderLogin();

        expect(screen.getByLabelText('Địa chỉ Email')).toHaveFocus();
    });

    // ST-73
    it('ô nhập bị lỗi được viền đỏ và đánh dấu aria-invalid', () => {
        renderLogin({ errors: { email: 'Thông tin đăng nhập không chính xác' } });

        const emailInput = screen.getByLabelText('Địa chỉ Email');
        const passwordInput = screen.getByLabelText('Mật khẩu');

        expect(emailInput).toHaveClass('border-red-500');
        expect(emailInput).toHaveAttribute('aria-invalid', 'true');
        expect(passwordInput).not.toHaveClass('border-red-500');
    });
});
