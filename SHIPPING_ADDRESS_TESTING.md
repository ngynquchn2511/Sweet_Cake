# Hướng dẫn Test Chức năng Sửa Địa chỉ Giao hàng (Edit)

## Workflow Chỉnh sửa Địa chỉ

### ✅ Backend Flow
```
User browser (GET) → /shipping-addresses/{id}/edit
        ↓
Controller::edit($id)
  - Query DB: ShippingAddress::findOrFail($id)
  - Kiểm tra quyền: user_id === auth()->id()
  - Render Inertia: ShippingAddress/ShippingAddressForm với address data
        ↓
React Component (ShippingAddressForm)
  - Nhận address prop
  - Form fields được prefill với dữ liệu từ database
  - Form state được quản lý bởi useForm() của Inertia
        ↓
User edit form + click "Lưu thay đổi"
        ↓
Form submit: PUT /shipping-addresses/{id}
  - Gửi dữ liệu: { receiver_name, phone, province, district, ward, address, note, is_default }
        ↓
Controller::update($id)
  - Validate request data
  - Kiểm tra quyền: user_id === auth()->id()
  - Cập nhật database
  - Nếu is_default=true: reset is_default=false cho các địa chỉ khác
  - Redirect to /shipping-addresses với flash message success
        ↓
Inertia xử lý redirect → Navigate về Index page
        ↓
Show success message: "Cập nhật địa chỉ thành công!"
```

---

## 🔍 Test Thủ công (Manual Testing)

### Step 1: Chuẩn bị dữ liệu
```bash
# Chắc chắn đã chạy migration
php artisan migrate

# Seed dữ liệu test
php artisan db:seed --class=DatabaseSeeder

# Hoặc tạo user test bằng tinker
php artisan tinker
# Tạo user
$user = \App\Models\User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('password')]);
# Tạo địa chỉ test
$address = \App\Models\ShippingAddress::factory()->create(['user_id' => $user->id]);
# Xem ID
$user->id;
$address->id;
```

### Step 2: Truy cập trang edit
1. **Đăng nhập** vào tài khoản test:
   - Email: `test@example.com`
   - Password: `password`

2. **Điều hướng** tới "Sổ địa chỉ":
   - Từ Dashboard → Click "Sổ địa chỉ" (hoặc truy cập `/shipping-addresses`)

3. **Trên bảng danh sách**, click nút **"Sửa"** ở hàng địa chỉ

4. **Kiểm tra**:
   - ✅ URL phải là `/shipping-addresses/{id}/edit` (ví dụ: `/shipping-addresses/1/edit`)
   - ✅ Form hiển thị với dữ liệu cũ của địa chỉ đó
   - ✅ Không có lỗi JavaScript trong console (F12 → Console tab)

### Step 3: Chỉnh sửa dữ liệu
1. **Thay đổi** một vài field (ví dụ: tên người nhận, số điện thoại)
2. **Kiểm tra** các field khác còn hiển thị đúng
3. **Tùy chọn**: Đánh dấu "Đặt làm địa chỉ mặc định"

### Step 4: Submit form
1. **Click** nút "Lưu thay đổi"
2. **Kiểm tra**:
   - ✅ Button có loading state (spinner, disabled)
   - ✅ Request được gửi (mở DevTools → Network tab, tìm PUT request tới `/shipping-addresses/{id}`)
   - ✅ Response status phải là 200-302 (redirect)
   - ✅ Không có lỗi trong console

### Step 5: Xác nhận kết quả
1. **Sau submit**, trang phải:
   - ✅ Tự động điều hướng về `/shipping-addresses` (Index page)
   - ✅ Hiển thị thông báo success: "Cập nhật địa chỉ thành công!"

2. **Kiểm tra dữ liệu**:
   - ✅ Danh sách địa chỉ hiển thị dữ liệu mới đã update
   - ✅ Nếu chọn "mặc định", địa chỉ này phải được đánh dấu "Địa chỉ mặc định"

3. **Database verification** (optional):
   ```bash
   php artisan tinker
   $address = \App\Models\ShippingAddress::find(1);
   $address->receiver_name; // Kiểm tra tên đã update
   ```

---

## 🐛 Troubleshooting

### ❌ Form không fillfill dữ liệu cũ
**Triệu chứng**: Tất cả field input đều trống
- Kiểm tra: Network tab → GET `/shipping-addresses/{id}/edit` → Response
  - Phải có `address` object trong response payload
  - Submit lại F5 refresh
  - Xoá cache browser (Ctrl+Shift+Delete)

### ❌ Submit form không hoạt động
**Triệu chứng**: Nút "Lưu thay đổi" bấm không có gì xảy ra
- Mở DevTools (F12) → Console tab
  - Có lỗi JavaScript không? Ghi ra lỗi
- Network tab → Bấm nút → có request gửi không?
  - Nếu không, kiểm tra form submit handler (có lỗi JS)
  - Nếu có, kiểm tra response status

### ❌ Server trả về lỗi validation (422)
**Triệu chứng**: Form submit nhưng quay lại trang edit với error message
- Kiểm tra từng field validation error
- Đảm bảo tất cả required field được fill
  - receiver_name: không rỗng
  - phone: không rỗng
  - province, district, ward, address: không rỗng

### ❌ Error 403 (Forbidden)
**Triệu chứng**: "This action is unauthorized"
- Kiểm tra: đang đăng nhập cùng user tạo địa chỉ không?
- Khi edit địa chỉ của user khác sẽ bị 403

### ❌ Error 404 (Not Found)
**Triệu chứng**: "Route not found"
- Địa chỉ với ID đó không tồn tại
- Kiểm tra ID có đúng không: `/shipping-addresses/{id}/edit`

---

## 📋 Checklist - Xác nhận Workflow Hoàn chỉnh

- [ ] Có thể xem trang edit với dữ liệu cũ được fill
- [ ] Form validation hiển thị lỗi (submit form rỗng)
- [ ] Có thể edit tất cả field (receiver_name, phone, province, etc.)
- [ ] Có thể chọn/bỏ chọn "Đặt làm mặc định"
- [ ] Submit form thành công
- [ ] Thông báo success hiển thị
- [ ] Điều hướng tự động về Index
- [ ] Dữ liệu trong bảng Index hiển thị được update
- [ ] Không thể edit địa chỉ của user khác (403 error)
- [ ] Database reflect được changes

---

## 🔧 Code Files Liên quan

| File | Mục đích |
|------|----------|
| `app/Http/Controllers/ShippingAddressController.php` | `edit()` & `update()` methods |
| `resources/js/Pages/ShippingAddress/ShippingAddressForm.jsx` | Form component (tái sử dụng create + edit) |
| `resources/js/Pages/ShippingAddress/Index.jsx` | Danh sách địa chỉ, nút "Sửa" |
| `app/Models/ShippingAddress.php` | Model |
| `routes/web.php` | Routes `shipping-addresses` resource |

---

## 🚀 Tiếp theo (After Verifying Edit Works)

Sau khi edit workflow OK:
1. Test delete address functionality
2. Add frontend validation (real-time error feedback)
3. Add success toast/notification (currently using Ant Design message)
4. Add loading skeleton while fetching address data
5. Add form dirty state detection (warn before leaving unsaved changes)
