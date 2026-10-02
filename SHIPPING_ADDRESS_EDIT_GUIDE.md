# Chức năng Sửa Địa chỉ Giao hàng - Hướng dẫn Setup & Test

## 🚀 Quick Start - Setup & Test Trong 5 Phút

### Step 1: Chạy Migration (nếu chưa)
```bash
php artisan migrate
```

### Step 2: Seed Dữ liệu Test
```bash
php artisan db:seed --class=ShippingAddressSeeder
```

**Kết quả**: Tạo 2 user test với 4 địa chỉ giao hàng:
- **User 1**: `test@example.com` / `password123` (3 địa chỉ)
- **User 2**: `user2@example.com` / `password123` (1 địa chỉ)

### Step 3: Chạy Dev Server
```bash
php artisan serve
npm run dev
```

### Step 4: Test Workflow Edit
1. **Đăng nhập** với `test@example.com`
2. **Vào trang chủ** → **Sổ địa chỉ**
3. **Click "Sửa"** ở 1 trong 3 địa chỉ
4. **Chỉnh sửa** một vài field (ví dụ: tên người nhận, số điện thoại)
5. **Click "Lưu thay đổi"**
6. **Kiểm tra**:
   - ✅ Trang quay lại danh sách
   - ✅ Thông báo success hiển thị
   - ✅ Dữ liệu trong bảng cập nhật

---

## 🔄 Workflow Chỉnh sửa Địa chỉ

```
Frontend (React)
├── ShippingAddress/Index.jsx
│   ├── Hiển thị danh sách địa chỉ (Table)
│   ├── Nút "Sửa" → navigate tới /shipping-addresses/:id/edit
│   └── Nút "Xóa" → DELETE request
│
└── ShippingAddress/ShippingAddressForm.jsx
    ├── Nhận address prop từ backend
    ├── Form fields: receiver_name, phone, province, district, ward, address, note, is_default
    ├── useForm() hook xử lý state & submission
    └── PUT /shipping-addresses/{id} when submit

Backend (Laravel)
├── Routes
│   └── Route::resource('shipping-addresses', ShippingAddressController::class)
│       ├── GET /shipping-addresses → index()
│       ├── GET /shipping-addresses/create → create()
│       ├── POST /shipping-addresses → store()
│       ├── GET /shipping-addresses/{id}/edit → edit() ← EDIT ROUTE
│       └── PUT /shipping-addresses/{id} → update() ← UPDATE HANDLER
│
└── ShippingAddressController
    ├── edit($id): 
    │   ├── Query address by id
    │   ├── Check user authorization
    │   └── Render ShippingAddressForm with address data
    │
    └── update($id):
        ├── Validate request data
        ├── Check user authorization
        ├── Update address record
        ├── If is_default=true: reset is_default for other addresses
        └── Redirect + flash message
```

---

## 📝 Files Involved

### Backend
| File | Bộ phận |
|------|--------|
| `app/Http/Controllers/ShippingAddressController.php` | `edit($id)`, `update($id)` methods |
| `app/Models/ShippingAddress.php` | Model với fillable fields |
| `database/factories/ShippingAddressFactory.php` | Factory để seed data |
| `database/seeders/ShippingAddressSeeder.php` | Seeder test data |
| `routes/web.php` | Route resource |

### Frontend
| File | Mục đích |
|------|---------|
| `resources/js/Pages/ShippingAddress/Index.jsx` | Danh sách & nút edit |
| `resources/js/Pages/ShippingAddress/ShippingAddressForm.jsx` | Form component (create + edit) |

---

## ✨ Features Implemented

### Edit Page (`/shipping-addresses/{id}/edit`)
- ✅ Hiển thị form với dữ liệu cũ được fill
- ✅ Validate fields: receiver_name, phone, province, district, ward, address (required)
- ✅ Optional fields: note
- ✅ Checkbox "Đặt làm địa chỉ mặc định"
- ✅ Error display khi validation fail
- ✅ Button "Hủy" (quay lại) & "Lưu thay đổi" (submit)
- ✅ Loading state khi submit
- ✅ Breadcrumb navigation

### Update Logic
- ✅ PUT request tới `/shipping-addresses/{id}`
- ✅ Validate tất cả required fields
- ✅ Check user authorization (chỉ edit địa chỉ của mình)
- ✅ Cập nhật database
- ✅ Auto-reset `is_default` cho các địa chỉ khác nếu chọn "mặc định"
- ✅ Flash message "Cập nhật địa chỉ thành công!"
- ✅ Redirect tới index page

### Index Page improvements
- ✅ Nút "Sửa" với icon để chỉnh sửa địa chỉ
- ✅ Nút "Xóa" với confirmation dialog
- ✅ Hiển thị "Địa chỉ mặc định" tag
- ✅ Nút chuyển địa chỉ khác thành mặc định từ bảng

---

## 🧪 Test Workflow

### ✅ Test Case 1: Edit địa chỉ thành công
1. Vào `/shipping-addresses`
2. Click "Sửa" ở 1 địa chỉ
3. Thay đổi tên người nhận
4. Click "Lưu thay đổi"
5. Kết quả:
   - Redirect tới index
   - Success message hiển thị
   - Bảng cập nhật tên mới

### ✅ Test Case 2: Validation error
1. Vào edit form
2. Xóa hết tên người nhận (receiver_name)
3. Click "Lưu thay đổi"
4. Kết quả:
   - Quay lại form
   - Error message: "The receiver name field is required"

### ✅ Test Case 3: Set as default
1. Vào edit form
2. Đánh dấu "Đặt làm địa chỉ mặc định"
3. Click "Lưu thay đổi"
4. Kết quả:
   - Bảng hiển thị địa chỉ này là "Địa chỉ mặc định"
   - Các địa chỉ khác bỏ tag "mặc định"

### ❌ Test Case 4: Authorization check
1. Đăng nhập user 2
2. Cố truy cập `/shipping-addresses/1/edit` (của user 1)
3. Kết quả:
   - Error 403 Forbidden

---

## 🔍 Debugging Tips

### Form không fill dữ liệu cũ
```bash
# Kiểm tra response HTTP
curl -H "Authorization: Bearer TOKEN" http://127.0.0.1:8000/shipping-addresses/1/edit
# Phải có address object trong props
```

### Submit form không gửi request
- F12 → Console: có Error không?
- F12 → Network tab: có PUT request tới `/shipping-addresses/{id}`?
- Kiểm tra form.receiver_name, form.phone có value không?

### Server error 422 (Validation)
- Kiểm tra validation rules ở Controller::update()
- Đảm bảo tất cả required field không rỗng

### Server error 403 (Unauthorised)
- Kiểm tra: `$address->user_id === auth()->id()`
- Đảm bảo đang đăng nhập user chủ sở hữu địa chỉ

---

## 📚 API Contract

### GET /shipping-addresses/{id}/edit - Edit Form
**Response** (Inertia):
```json
{
  "props": {
    "address": {
      "id": 1,
      "user_id": 1,
      "receiver_name": "Nguyễn Văn A",
      "phone": "0912345678",
      "province": "Hà Nội",
      "district": "Ba Đình",
      "ward": "Phú Thượng",
      "address": "123 Đường Láng",
      "note": "Ghi chú",
      "is_default": true,
      "created_at": "2026-02-06T...",
      "updated_at": "2026-02-06T..."
    }
  }
}
```

### PUT /shipping-addresses/{id} - Update Address
**Request**:
```json
{
  "receiver_name": "Nguyễn Văn A",
  "phone": "0912345678",
  "province": "Hà Nội",
  "district": "Ba Đình",
  "ward": "Phú Thượng",
  "address": "123 Đường Láng",
  "note": "Ghi chú",
  "is_default": true
}
```

**Response**:
```
HTTP 302 Found (Redirect)
Location: /shipping-addresses
Set-Cookie: XSRF-TOKEN=...
Session Flash: {
  "success": "Cập nhật địa chỉ thành công!"
}
```

---

## ✅ Checklist - Xác nhận Workflow Complete

- [ ] Database migrations chạy thành công
- [ ] Seeder tạo dữ liệu test
- [ ] Login được với tài khoản test
- [ ] Vào trang sổ địa chỉ xem danh sách
- [ ] Click "Sửa" → hiển thị form với dữ liệu cũ
- [ ] Edit form → submit thành công
- [ ] Redirect về index + success message
- [ ] Dữ liệu trong bảng cập nhật
- [ ] Checkbox "mặc định" hoạt động
- [ ] Không thể edit địa chỉ người khác (403)
- [ ] Validation error display đúng
- [ ] Delete address hoạt động (related task)

---

## 🎯 Next Steps

1. **Optimization**:
   - [ ] Add form unsaved changes warning
   - [ ] Add loading skeleton while fetching
   - [ ] Add success toast instead of page message

2. **Enhancement**:
   - [ ] Real-time address validation
   - [ ] Address autocomplete (API like Google Maps)
   - [ ] Bulk operations (delete multiple)
   - [ ] Export addresses

3. **Testing**:
   - [ ] Write end-to-end tests (if using Cypress/Playwright)
   - [ ] Unit tests for validation rules
   - [ ] Performance testing

4. **Security**:
   - [ ] Rate limit shipping address endpoints
   - [ ] Log address changes for audit
   - [ ] Add address verification (SMS/Email confirmation)
