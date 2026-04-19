# 🏢 Web Hệ thống Quản lý Vận hành Cho thuê Cao ốc

> **Trạng thái:** v2.1 — Production Ready (Đạt chuẩn Zero Bugs Backend)

## 📋 Thông tin đồ án

| Thông tin       | Chi tiết                                      |
| --------------- | --------------------------------------------- |
| **Tên đề tài**  | Web Hệ thống Quản lý Vận hành Cho thuê Cao ốc |
| **Môn học**     | Lập trình Web                                 |
| **Lớp**         | DH24TH2                                       |
| **Sinh viên 1** | Trần Trí Nhân – MSSV: DTH235712               |
| **Sinh viên 2** | Huỳnh Minh Nhật – MSSV: DTH235713             |
| **Trường**      | Đại học An Giang – Khoa Công nghệ Thông tin   |
| **Năm học**     | 2025 – 2026                                   |
| **GVHD**        | ThS. Thiều Thanh Quang Phú                    |

---

## 🎯 Giới thiệu hệ thống

Hệ thống số hóa toàn bộ nghiệp vụ cho thuê văn phòng tại các cao ốc thương mại. Thay thế quản lý thủ công bằng giấy tờ, phần mềm xử lý trọn vẹn vòng đời hợp đồng: từ lập hợp đồng, thanh toán hằng tháng có **bù trừ nợ/dư tự động**, gia hạn linh hoạt từng phòng, cho đến kết thúc hoặc hủy hợp đồng.

**Điểm nổi bật:**

- ✅ Bù trừ nợ/dư tự động giữa các kỳ thanh toán
- ✅ Gia hạn linh hoạt từng phòng riêng biệt trong cùng một hợp đồng
- ✅ Giá thuê tự động: `donGiaM2 × dienTich × heSoGia` – field chỉ đọc
- ✅ Phân quyền 4 cấp: Admin / Quản lý Nhà / Kế toán / Khách hàng
- ✅ Nguyên khối Transaction (A.C.I.D) cho nghiệp vụ nhạy cảm (hủy HĐ, kết thúc phòng lẻ)
- ✅ Cấn trừ nợ tự động Waterfall Payment qua bảng `PHIEU_THU` / `PHIEU_THU_CHI_TIET`
- ✅ Xử lý Dangling State Tiền Cọc khép kín (luồng 4 trạng thái: Đã thu → Chờ Xử Lý → Đã Hoàn / Tịch Thu)
- ✅ Chống Race Condition & Deadlock bằng `SELECT FOR UPDATE` + `ORDER BY` lock cố định
- ✅ Bảo vệ IDOR cấp độ CSDL (verify ownership mọi maCTHD/maPhong trước khi UPDATE)
- ✅ Rate-Limit & Login Throttle chống Brute-force (lockout 15 phút, ghi log IP)
- ✅ Soft Delete (Thùng rác) – không mất dữ liệu vĩnh viễn
- ✅ Audit Log – ghi nhận "Ai sửa gì, từ giá trị nào, lúc nào"
- ✅ Cấu hình hệ thống động – Admin tự điều chỉnh quy tắc nghiệp vụ
- ✅ Dark / Light Mode toggle – lưu preference vào localStorage
- ✅ Đa ngôn ngữ Việt / Anh (lang_vi.php / lang_en.php)
- ✅ Export PDF hợp đồng & hóa đơn (html2pdf.js)
- ✅ QR Code bảo mật trên hóa đơn (token JWT 15 phút, không public)
- ✅ Chatbot Rule-based tra cứu phòng trống
- ✅ Tenant Portal – khách đăng nhập tài khoản riêng, xem HĐ cá nhân
- ✅ Maintenance Request – luồng sửa chữa có SLA & priority
- ✅ Lock phòng tạm thời khi wizard đang lập HĐ (chống race condition + atomic upsert)
- ✅ Trạng thái nháp hợp đồng (ChoDuyet → DangHieuLuc)
- ✅ Void hóa đơn + credit note có audit trail
- ✅ Validate chỉ số điện/nước không được nhỏ hơn kỳ trước
- ✅ Theo dõi tiền cọc & luồng hoàn cọc khép kín (4 trạng thái: Đã thu / Đã hoàn / Tịch thu / Chờ Xử Lý)
- ✅ Tài khoản Khách hàng (đăng nhập riêng, không dùng mã HĐ + SĐT)
- ✅ Lockout đăng nhập sau 5 lần sai

---

## 🛠️ Công nghệ sử dụng

| Thành phần      | Công nghệ                                                       |
| --------------- | --------------------------------------------------------------- |
| **Frontend**    | HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons                     |
| **Backend**     | PHP 8.x Native (OOP style) – **không** dùng Laravel/CodeIgniter |
| **CSDL**        | MySQL 8.0 – kết nối qua PDO + Prepared Statements               |
| **Thư viện JS** | Chart.js, html2pdf.js, qrcode.js, DataTables (Bootstrap 5)      |
| **Môi trường**  | XAMPP / Laragon                                                 |

---

## 📁 Cấu trúc thư mục

```
quan_ly_cao_oc/
│
├── 📄 index.php                    ← Trang chủ Public (hero, phòng trống, filter)
├── 📄 dangnhap.php                 ← Trang đăng nhập (dùng chung mọi role)
├── 📄 dangnhap_submit.php          ← Xử lý đăng nhập, ghi Session, redirect theo role
├── 📄 dangxuat.php                 ← Hủy session + redirect
├── 📄 gioi_thieu.php               ← Giới thiệu & Liên hệ
├── 📄 phong_trong.php              ← Danh sách phòng trống public
├── 📄 chi_tiet_phong.php           ← Chi tiết phòng + gallery ảnh
├── 📄 dang_ky_thue.php             ← Form đăng ký thuê (public)
├── 📄 dang_ky_thue_submit.php      ← Xử lý INSERT yêu cầu thuê + gửi email xác nhận
│
├── 📁 config/
│   ├── app.php                     ← Đường dẫn, tên app, timezone, phân trang
│   ├── database.php                ← DB credentials (gitignore file này)
│   ├── constants.php               ← Hằng số hệ thống
│   ├── status.php                  ← Trạng thái + màu badge + icon cho mọi bảng
│   ├── messages.php                ← Thông báo lỗi/thành công dùng chung
│   ├── business_rules.php          ← Quy tắc nghiệp vụ (lấy từ DB bảng CAU_HINH)
│   ├── roles.php                   ←  Mapping role → redirect sau đăng nhập
│   ├── lang_vi.php                 ← Ngôn ngữ Tiếng Việt
│   └── lang_en.php                 ← Ngôn ngữ English
│
├── 📁 includes/
│   ├── 📁 public/
│   │   ├── header.php, navbar.php, footer.php, banner.php
│   ├── 📁 admin/
│   │   ├── admin-header.php, sidebar.php, admin-footer.php
│   │   └── notifications.php
│   └── 📁 common/
│       ├── db.php                  ← Kết nối PDO singleton
│       ├── auth.php                ← kiemTraSession(), kiemTraQuyen(), kiemTraRole()
│       ├── functions.php           ← Hàm dùng chung
│       ├── csrf.php                ← Tạo/xác minh CSRF token
│       ├── mailer.php              ←  PHPMailer wrapper gửi email thông báo
│       └── login_throttle.php      ←  Đếm lần đăng nhập sai, lockout 15 phút
│
├── 📁 modules/
│   ├── 📁 phong/                   ← CRUD Phòng + Upload ảnh gallery
│   │   └── phong_lock.php          ←  API lock/unlock phòng tạm thời (AJAX)
│   ├── 📁 cao_oc/                  ← CRUD Cao ốc & Tầng
│   ├── 📁 khach_hang/              ← CRUD + Lịch sử giao dịch
│   ├── 📁 khach_hang_account/      ←  Tài khoản đăng nhập của Khách hàng
│   │   ├── kh_dangnhap.php
│   │   ├── kh_dangnhap_submit.php
│   │   └── kh_doi_mat_khau.php
│   ├── 📁 nhan_vien/               ← CRUD (Admin only)
│   ├── 📁 hop_dong/
│   │   ├── hd_them.php             ← Wizard 4 bước (có lock phòng tạm)
│   │   ├── hd_them_submit.php      ← Transaction + SELECT FOR UPDATE
│   │   ├── hd_hienthi.php          ← Danh sách (bao gồm badge "Nháp")
│   │   ├── hd_gia_han.php/submit
│   │   ├── hd_ket_thuc_le.php/submit
│   │   └── hd_huy.php/submit
│   ├── 📁 tien_coc/                ← Theo dõi tiền cọc & hoàn cọc
│   │   ├── coc_hienthi.php
│   │   └── coc_hoan_submit.php
│   ├── 📁 thanh_toan/
│   │   ├── tt_tao.php              ← Thanh toán + bù trừ + chọn phương thức
│   │   ├── tt_tao_submit.php
│   │   ├── tt_void.php             ← Void hóa đơn sai
│   │   ├── tt_void_submit.php
│   │   ├── dien_nuoc_ghi.php       ← Validate delta >= 0
│   │   └── dien_nuoc_ghi_submit.php
│   ├── 📁 bao_cao/                 ← 4 tab báo cáo + Export CSV + In
│   ├── 📁 yeu_cau_thue/            ← Duyệt/Từ chối yêu cầu thuê
│   ├── 📁 maintenance/
│   │   ├── yc_them.php             ← Form gửi (có priority: Khẩn/Cao/Bình thường)
│   │   ├── yc_quan_ly.php          ← Admin duyệt + timeline + SLA countdown
│   │   └── yc_notify.php           ← Gửi email khi status thay đổi
│   ├── 📁 tenant/                  ← Dashboard Khách hàng (sau đăng nhập)
│   │   ├── dashboard.php           ← Tổng quan HĐ, ngày hết hạn, nợ hiện tại
│   │   ├── hop_dong.php            ← Chi tiết HĐ + danh sách phòng
│   │   ├── hoa_don.php             ← Lịch sử thanh toán từng kỳ
│   │   ├── dien_nuoc.php           ← Lịch sử tiêu thụ điện/nước theo tháng
│   │   ├── yeu_cau_giahan.php      ← Gửi yêu cầu gia hạn online
│   │   ├── tranh_chap.php          ← Yêu cầu kiểm tra lại hóa đơn
│   │   └── maintenance.php         ← Gửi & theo dõi yêu cầu sửa chữa
│   ├── 📁 tenant_portal/           ← Tra cứu nhanh (public, dùng token JWT)
│   ├── 📁 thung_rac/               ← Soft Delete – khôi phục
│   ├── 📁 audit_log/               ← Xem lịch sử thao tác (Admin only, append-only)
│   ├── 📁 cau_hinh/                ← Admin cấu hình quy tắc nghiệp vụ
│   └── 📁 ho_so/                   ← Hồ sơ cá nhân + Đổi mật khẩu
│
├── 📁 assets/
│   ├── 📁 css/
│   │   ├── style.css
│   │   └── dark-mode.css
│   ├── 📁 js/
│   │   ├── main.js
│   │   ├── wizard.js               ← Autosave dùng key = sessionID+userID
│   │   ├── realtime-calc.js        ← Bao gồm validate delta điện/nước >= 0
│   │   ├── room-lock.js            ← Heartbeat giữ lock phòng trong wizard
│   │   ├── export-pdf.js
│   │   ├── datatables-init.js
│   │   ├── chatbot.js
│   │   └── lang.js
│   └── 📁 uploads/
│       └── 📁 phong/
│
└── 📁 database/
    └── quan_ly_cao_oc.sql          ← 28 bảng + INSERT dữ liệu mẫu
```

---

## ✅ Danh sách chức năng

### 🔓 Public (Không cần đăng nhập)

| STT | Chức năng                                             | File                     |
| --- | ----------------------------------------------------- | ------------------------ |
| 1   | Trang chủ (Hero + Phòng nổi bật + Filter nhanh)       | `index.php`              |
| 2   | Danh sách phòng trống (tìm kiếm nâng cao)             | `phong_trong.php`        |
| 3   | Chi tiết phòng (gallery ảnh + thông số)               | `chi_tiet_phong.php`     |
| 4   | Đăng ký thuê phòng + email xác nhận tự động           | `dang_ky_thue.php`       |
| 5   | Giới thiệu & Liên hệ                                  | `gioi_thieu.php`         |
| 6   | Tenant Portal nhanh (token JWT 15 phút, không public) | `modules/tenant_portal/` |
| 7   | Đăng nhập hệ thống (dùng chung, redirect theo role)   | `dangnhap.php`           |

### 🔒 Khách hàng (Tenant – đăng nhập tài khoản riêng)

| STT | Chức năng                       | Mô tả                                              |
| --- | ------------------------------- | -------------------------------------------------- |
| 8   | Dashboard cá nhân               | Tóm tắt HĐ, ngày hết hạn, số tiền nợ hiện tại      |
| 9   | Chi tiết hợp đồng & phòng       | Xem đầy đủ điều khoản, trạng thái từng phòng       |
| 10  | Lịch sử hóa đơn                 | Xem từng kỳ đã đóng tiền bao nhiêu, trạng thái     |
| 11  | Lịch sử điện/nước               | Biểu đồ tiêu thụ kWh/m³ theo tháng từng phòng      |
| 12  | Yêu cầu gia hạn online          | Chọn số tháng từng phòng → gửi → QLN duyệt         |
| 13  | Tranh chấp hóa đơn              | Yêu cầu kiểm tra lại → kế toán xử lý → đóng ticket |
| 14  | Gửi & theo dõi yêu cầu sửa chữa | Có priority, xem tiến độ timeline                  |
| 15  | Đổi mật khẩu tài khoản          | Xác nhận mật khẩu cũ trước khi đổi                 |

### 🔒 Sau đăng nhập – Quản trị (Admin / QLN / Kế toán)

| STT | Chức năng                                              | Quyền          |
| --- | ------------------------------------------------------ | -------------- |
| 16  | Dashboard Admin (4 KPI + Chart.js + bảng HĐ hết hạn)   | Admin          |
| 17  | Dashboard cá nhân theo bộ phận                         | Tất cả NV      |
| 18  | Quản lý Cao ốc (CRUD + đếm phòng)                      | Admin, QLN     |
| 19  | Quản lý Tầng (CRUD + liên kết cao ốc)                  | Admin, QLN     |
| 20  | Quản lý Phòng (CRUD + gallery ảnh + upload)            | Admin, QLN     |
| 21  | Quản lý Khách hàng (CRUD + lịch sử giao dịch)          | Admin, QLN     |
| 22  | Quản lý Tài khoản Khách hàng                           | Admin          |
| 23  | Quản lý Nhân viên (Admin only)                         | Admin          |
| 24  | Lập hợp đồng – Wizard 4 bước + Lock phòng tạm          | QLN            |
| 25  | HĐ trạng thái Nháp → Ký → Hiệu lực                     | QLN            |
| 26  | Danh sách hợp đồng + Badge trạng thái                  | QLN, Admin     |
| 27  | Gia hạn hợp đồng từng phòng (3 điều kiện)              | QLN            |
| 28  | Kết thúc thuê phòng lẻ (Transaction)                   | QLN            |
| 29  | Hủy hợp đồng (kiểm tra nợ + cọc, Transaction)          | QLN            |
| 30  | Theo dõi tiền cọc & hoàn cọc                           | QLN, Kế toán   |
| 31  | Duyệt yêu cầu gia hạn online từ Khách hàng             | QLN            |
| 32  | Thanh toán & Bù trừ công nợ real-time + phương thức TT | Kế toán        |
| 33  | Void hóa đơn sai + credit note                         | Kế toán, Admin |
| 34  | Ghi chỉ số điện/nước + Validate delta >= 0             | Kế toán        |
| 35  | Xử lý tranh chấp hóa đơn từ Khách hàng                 | Kế toán        |
| 36  | Duyệt/Từ chối yêu cầu thuê từ public                   | Admin, QLN     |
| 37  | Báo cáo 4 tab + Export CSV + In trang                  | Admin, QLN     |

### ✨ Tính năng nâng cao (bắt buộc)

| STT | Tính năng                 | Mô tả                                            |
| --- | ------------------------- | ------------------------------------------------ |
| 38  | Dark / Light Mode Toggle  | CSS variables + localStorage (key theo userID)   |
| 39  | Notification Center       | Chuông dropdown AJAX                             |
| 40  | Email notification        | PHPMailer: hóa đơn mới, HĐ sắp hết hạn, duyệt YC |
| 41  | Global Search             | FULLTEXT INDEX trên tenKH, soHopDong, maPhong    |
| 42  | Export PDF                | html2pdf.js                                      |
| 43  | Advanced Filter & Sort    | DataTables Bootstrap 5                           |
| 44  | Login Lockout             | Khóa 15 phút sau 5 lần đăng nhập sai             |
| 45  | Buộc đổi mật khẩu lần đầu | Áp dụng tài khoản demo & tài khoản mới tạo       |

### 🔧 Tính năng an toàn vận hành (bắt buộc)

| STT | Tính năng                    | Mô tả                                             |
| --- | ---------------------------- | ------------------------------------------------- |
| 46  | Soft Delete (Thùng rác)      | Xóa mềm – có thể khôi phục                        |
| 47  | Audit Log (append-only)      | Không có nút Delete trong module này              |
| 48  | Cấu hình hệ thống            | Admin tự điều chỉnh: thuê tối thiểu, % phạt, SLA  |
| 49  | Maintenance SLA              | Khẩn=4h / Cao=24h / Bình=72h + countdown hiển thị |
| 50  | Gallery ảnh phòng            | Lightbox JS                                       |
| 51  | QR Code bảo mật              | JWT token 15 phút thay vì link public             |
| 52  | Hồ sơ cá nhân & Đổi mật khẩu | Tất cả user                                       |
| 53  | Đa ngôn ngữ Việt / Anh       | Toggle nút ở header                               |
| 54  | Race condition prevention    | SELECT FOR UPDATE + room reservation lock         |
| 55  | Phương thức thanh toán       | Tiền mặt / Chuyển khoản / Ví + mã giao dịch       |

---

## 🔧 Tính năng BỔ SUNG — Làm nếu kịp thời gian

> Không ảnh hưởng đến điểm cốt lõi. Làm được sẽ tăng tính chuyên nghiệp khi demo.

| STT | Tính năng                              | Độ khó     | Mô tả                                                                    |
| --- | -------------------------------------- | ---------- | ------------------------------------------------------------------------ |
| B1  | Phạt trả chậm tự động                  | Trung bình | Tính lãi suất % / ngày từ CAU_HINH khi soTienConNo_cu > 0 quá hạn N ngày |
| B2  | Thêm phòng vào HĐ hiệu lực (UC12)      | Trung bình | INSERT CTHD mới vào HĐ đang chạy, không tạo HĐ mới                       |
| B3  | Biểu đồ xu hướng điện/nước             | Dễ         | Chart.js line chart trong tenant dashboard                               |
| B4  | Override cửa sổ gia hạn 30 ngày        | Dễ         | Trưởng BP có thể bypass với lý do được lưu vào audit                     |
| B5  | Checklist kiểm tra phòng khi trả       | Dễ         | Form checkbox + upload ảnh khi kết thúc UC10                             |
| B6  | Bảng giá điện/nước theo effective_date | Khó        | Pro-rata khi giá thay đổi giữa kỳ                                        |
| B7  | 2FA cho Admin (TOTP)                   | Khó        | Google Authenticator compatible                                          |
| B8  | Báo cáo dự báo doanh thu               | Trung bình | Extrapolate từ HĐ đang hiệu lực × tháng còn lại                          |
| B9  | Bulk reminder email                    | Dễ         | Gửi email hàng loạt cho HĐ sắp hết hạn trong tháng                       |
| B10 | Import dữ liệu từ Excel                | Khó        | Upload .xlsx → parse SheetJS → INSERT batch                              |

---

## 🗄️ Cơ sở dữ liệu (28 bảng)

**11 bảng gốc từ đồ án OOAD:** CAO_OC, TANG, PHONG, KHACH_HANG, HOP_DONG, CHI_TIET_HOP_DONG, GIA_HAN_HOP_DONG, CHI_TIET_GIA_HAN, HOA_DON, CHI_SO_DIEN_NUOC, NHAN_VIEN

**Bảng bổ sung cho Web:**

| Bảng                     | Mục đích                                                |
| ------------------------ | ------------------------------------------------------- |
| `PHONG_HINH_ANH`         | Gallery nhiều ảnh cho một phòng                         |
| `PHONG_LOCK`             | Lock phòng tạm thời khi wizard đang mở (expire 10 phút) |
| `YEU_CAU_THUE`           | Đơn đăng ký thuê từ trang public                        |
| `YEU_CAU_GIA_HAN`        | Yêu cầu gia hạn online từ Khách hàng                    |
| `TIEN_COC`               | Theo dõi tiền cọc đã thu & luồng hoàn cọc khép kín (4 trạng thái) |
| `HOA_DON_VOID`           | Lưu lý do void + credit note tham chiếu                 |
| `KHACH_HANG_ACCOUNT`     | Tài khoản đăng nhập của Khách hàng                      |
| `LOGIN_ATTEMPT`          | Đếm lần đăng nhập sai theo IP + username                |
| `TRANH_CHAP_HOA_DON`     | Ticket tranh chấp hóa đơn từ Khách hàng                 |
| `THONG_BAO`              | Notification Center                                     |
| `AUDIT_LOG`              | Ghi nhận thay đổi dữ liệu (append-only)                 |
| `ACTIVITY_LOG`           | Lịch sử thao tác                                        |
| `CAU_HINH_HE_THONG`      | Cấu hình quy tắc nghiệp vụ động                         |
| `MAINTENANCE_REQUEST`    | Yêu cầu sửa chữa phòng (có priority + SLA)              |
| `MAINTENANCE_STATUS_LOG` | Timeline trạng thái sửa chữa                            |
| `PHIEU_THU`              | Ledger giao dịch thanh toán (phiếu thu tiền mặt/CK/ví)  |
| `PHIEU_THU_CHI_TIET`     | Mapping waterfall phân bổ tiền vào hóa đơn (cấn trừ nợ) |

> **Tổng:** 28 bảng. Tất cả cột tiền dùng `DECIMAL(15,2)`. Mọi bảng quan trọng có `deleted_at` (Soft Delete).

---

## 📐 Nghiệp vụ quan trọng bổ sung

### Race condition – Lock phòng tạm thời

Khi NV bắt đầu wizard lập HĐ (bước 2 – chọn phòng):

1. Client gọi `phong_lock.php?action=lock&maPhong=P-301` → INSERT vào `PHONG_LOCK` với `expire_at = NOW() + 10 phút`
2. `room-lock.js` gửi heartbeat mỗi 3 phút để gia hạn lock
3. Khi submit `hd_them_submit.php`: `SELECT maPhong FROM PHONG_LOCK WHERE maPhong=? AND expire_at > NOW() AND session_id != ?` — nếu có bản ghi của session khác → báo lỗi
4. Sau submit thành công hoặc hủy wizard: gọi `action=unlock`

### Trạng thái hợp đồng (mới)

```
[Nháp/ChoDuyet] → (2 bên ký) → [DangHieuLuc] → (gia hạn) → [GiaHan]
                                              → (kết thúc lẻ) → [DangHieuLuc] (ít phòng hơn)
                                              → (hủy) → [DaHuy]
                                              → (hết hạn tự nhiên) → [HetHan]
```

Phòng chỉ chuyển `DangThue` khi HĐ chuyển từ `ChoDuyet` → `DangHieuLuc` (sau khi ký UC04).

### Void hóa đơn

```php
// Chỉ Admin hoặc Trưởng BP được void
// Không UPDATE hóa đơn gốc – chỉ thêm bản ghi mới
INSERT INTO HOA_DON (soPhieu, lyDo, tongTien, soTienDaNop, soTienConNo, trangThai)
VALUES ('CN-2026-001', 'Điều chỉnh HĐ-001 kỳ 3', -22500000, 0, -22500000, 'DaThu');
INSERT INTO HOA_DON_VOID (soPhieuGoc, soPhieuCreditNote, lyDoVoid, maNV_void, thoiGian)
VALUES ('PT-2026-085', 'CN-2026-001', 'Nhập sai kỳ thanh toán', 'NV002', NOW());
```

### Validate chỉ số điện/nước

```javascript
// realtime-calc.js
function validateChiSo(dauKy, cuoiKy, donVi) {
  const delta = cuoiKy - dauKy;
  if (delta < 0) {
    showError(
      `Chỉ số ${donVi} cuối kỳ phải lớn hơn hoặc bằng đầu kỳ (${dauKy})`,
    );
    return false;
  }
  if (delta > 9999) {
    showWarning(
      `Tiêu thụ ${delta} ${donVi} có vẻ bất thường. Vui lòng kiểm tra lại.`,
    );
  }
  return true;
}
```

---

## 🔐 Hệ thống phân quyền 4 cấp

```
Trang chủ public (index.php)
       ↓
   Đăng nhập (dangnhap.php)
       ↓
  [Kiểm tra role trong Session]
       ├── admin       → /modules/dashboard/admin.php
       ├── quan_ly_nha → /modules/dashboard/qlnha.php
       ├── ke_toan     → /modules/dashboard/ketoan.php
       └── khach_hang  → /modules/tenant/dashboard.php
```

**Tài khoản mặc định:**

| Tên đăng nhập | Mật khẩu       | Quyền              | Ghi chú                       |
| ------------- | -------------- | ------------------ | ----------------------------- |
| `admin`       | `Admin@2026!`  | Admin – toàn quyền | Bắt buộc đổi mật khẩu lần đầu |
| `quanly`      | `Qlnha@2026!`  | Quản lý Nhà        |                               |
| `ketoan`      | `Ketoan@2026!` | Kế toán            |                               |
| `kh001`       | `Kh001@2026!`  | Khách hàng         | Tài khoản demo tenant         |

> ⚠️ Mật khẩu hash bằng `password_hash()`. **Không dùng MD5.**
> ⚠️ Sau 5 lần đăng nhập sai: tài khoản bị khóa 15 phút, ghi log IP.

---

## 🗄️ CSDL Mở rộng – ALTER TABLE & bảng mới

```sql
-- Bảng PHONG_LOCK: ngăn race condition
CREATE TABLE PHONG_LOCK (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    maPhong     CHAR(10) NOT NULL,
    session_id  VARCHAR(128) NOT NULL,
    maNV        CHAR(10) NULL,
    expire_at   DATETIME NOT NULL,
    UNIQUE KEY uq_phong (maPhong),
    FOREIGN KEY (maPhong) REFERENCES PHONG(maPhong)
);

-- Bảng tài khoản Khách hàng
CREATE TABLE KHACH_HANG_ACCOUNT (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    maKH            CHAR(10) NOT NULL UNIQUE,
    username        VARCHAR(50) UNIQUE NOT NULL,
    mat_khau        VARCHAR(255) NOT NULL,
    trang_thai      TINYINT DEFAULT 1,
    phai_doi_matkhau TINYINT DEFAULT 1,
    lan_dang_nhap_sai TINYINT DEFAULT 0,
    khoa_den        DATETIME NULL,
    FOREIGN KEY (maKH) REFERENCES KHACH_HANG(maKH)
);

-- Bảng LOGIN_ATTEMPT: chống brute force
CREATE TABLE LOGIN_ATTEMPT (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100) NOT NULL,
    ip_address  VARCHAR(45) NOT NULL,
    thanh_cong  TINYINT DEFAULT 0,
    thoi_gian   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_time (username, thoi_gian)
);

-- Bảng theo dõi tiền cọc
CREATE TABLE TIEN_COC (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    soHopDong   CHAR(15) NOT NULL,
    so_tien_coc DECIMAL(15,2) NOT NULL,
    ngay_thu    DATE NOT NULL,
    trang_thai  ENUM('DaGiu','DaHoan','KhauTru') DEFAULT 'DaGiu',
    ngay_hoan   DATE NULL,
    ly_do_hoan  NVARCHAR(200) NULL,
    maNV        CHAR(10) NULL,
    FOREIGN KEY (soHopDong) REFERENCES HOP_DONG(soHopDong),
    FOREIGN KEY (maNV) REFERENCES NHAN_VIEN(maNV)
);

-- Bảng void hóa đơn
CREATE TABLE HOA_DON_VOID (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    soPhieuGoc      CHAR(15) NOT NULL,
    soPhieuCreditNote CHAR(15) NOT NULL,
    lyDoVoid        NVARCHAR(300) NOT NULL,
    maNV_void       CHAR(10) NOT NULL,
    thoiGian        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (soPhieuGoc) REFERENCES HOA_DON(soPhieu),
    FOREIGN KEY (maNV_void) REFERENCES NHAN_VIEN(maNV)
);

-- Bảng yêu cầu gia hạn online từ Khách hàng
CREATE TABLE YEU_CAU_GIA_HAN (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    soHopDong   CHAR(15) NOT NULL,
    maKH        CHAR(10) NOT NULL,
    chi_tiet    JSON NOT NULL,   -- [{maPhong, soThangGiaHan}, ...]
    trang_thai  ENUM('ChoDuyet','DaDuyet','TuChoi') DEFAULT 'ChoDuyet',
    ghi_chu     NVARCHAR(300) NULL,
    maNV_duyet  CHAR(10) NULL,
    tao_luc     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (soHopDong) REFERENCES HOP_DONG(soHopDong)
);

-- Bảng tranh chấp hóa đơn
CREATE TABLE TRANH_CHAP_HOA_DON (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    soPhieu     CHAR(15) NOT NULL,
    maKH        CHAR(10) NOT NULL,
    ly_do       NVARCHAR(500) NOT NULL,
    trang_thai  ENUM('MoTicket','DangXuLy','DaDong') DEFAULT 'MoTicket',
    ket_qua     NVARCHAR(500) NULL,
    maNV_xu_ly  CHAR(10) NULL,
    tao_luc     DATETIME DEFAULT CURRENT_TIMESTAMP,
    dong_luc    DATETIME NULL,
    FOREIGN KEY (soPhieu) REFERENCES HOA_DON(soPhieu)
);

-- Bổ sung cột vào HOA_DON
ALTER TABLE HOA_DON ADD COLUMN phuong_thuc ENUM('TienMat','ChuyenKhoan','Vi') NULL;
ALTER TABLE HOA_DON ADD COLUMN ma_giao_dich VARCHAR(100) NULL;
ALTER TABLE HOA_DON ADD COLUMN trang_thai_void TINYINT DEFAULT 0;

-- Bổ sung cột vào HOP_DONG
ALTER TABLE HOP_DONG MODIFY COLUMN trangThai ENUM(
    'ChoDuyet','DangHieuLuc','GiaHan','HetHan','DaHuy'
) DEFAULT 'ChoDuyet';

-- Bổ sung cột vào MAINTENANCE_REQUEST
ALTER TABLE MAINTENANCE_REQUEST ADD COLUMN priority ENUM('Khan','Cao','BinhThuong') DEFAULT 'BinhThuong';
ALTER TABLE MAINTENANCE_REQUEST ADD COLUMN sla_deadline DATETIME NULL;

-- Soft Delete cho các bảng quan trọng
ALTER TABLE PHONG        ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE KHACH_HANG   ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE NHAN_VIEN    ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE HOA_DON      ADD COLUMN deleted_at DATETIME NULL;

-- FULLTEXT INDEX cho Global Search
ALTER TABLE KHACH_HANG   ADD FULLTEXT INDEX ft_tenkh (tenKH);
ALTER TABLE HOP_DONG     ADD FULLTEXT INDEX ft_sophd (soHopDong);
ALTER TABLE PHONG        ADD FULLTEXT INDEX ft_maphong (maPhong, moTaViTri);

-- Cấu hình hệ thống
INSERT INTO CAU_HINH_HE_THONG VALUES
    ('THUE_TOI_THIEU_THANG',  '6',    'Thời gian thuê tối thiểu (tháng)'),
    ('PHAT_HUY_HOP_DONG',    '10',   'Phần trăm phạt hủy HĐ trước hạn (%)'),
    ('CANH_BAO_HET_HAN',     '30',   'Số ngày cảnh báo trước khi HĐ hết hạn'),
    ('LAI_SUAT_TRA_CHAM',    '0.05', 'Lãi suất trả chậm (% / ngày) – Tùy chọn'),
    ('SLA_KHAN_GIO',         '4',    'SLA yêu cầu khẩn (giờ)'),
    ('SLA_CAO_GIO',          '24',   'SLA yêu cầu cao (giờ)'),
    ('SLA_BINH_GIO',         '72',   'SLA yêu cầu bình thường (giờ)'),
    ('LOCK_PHONG_PHUT',      '10',   'Thời gian giữ lock phòng trong wizard (phút)'),
    ('LOCKOUT_SO_LAN',       '5',    'Số lần đăng nhập sai trước khi khóa'),
    ('LOCKOUT_PHUT',         '15',   'Thời gian khóa tài khoản (phút)');
```

---

## 🚀 Hướng dẫn cài đặt

### Yêu cầu môi trường

- PHP >= 8.0
- MySQL >= 8.0 hoặc MariaDB >= 10.5
- XAMPP / Laragon

### Bước 1: Import CSDL

```
1. Tạo database `quan_ly_cao_oc` trong phpMyAdmin
2. Import `database/quan_ly_cao_oc.sql`
```

### Bước 2: Cấu hình

```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quan_ly_cao_oc');

// config/app.php
define('BASE_URL', 'http://localhost/quan_ly_cao_oc/');
define('APP_NAME', 'CaoOc Manager');
define('TIMEZONE', 'Asia/Ho_Chi_Minh');
define('ITEMS_PER_PAGE', 15);
define('UPLOAD_MAX_MB', 2);
```

### Bước 3: Chạy

Truy cập: `http://localhost/quan_ly_cao_oc/`

---

## 📐 Quy tắc code bắt buộc (Coding Standard)

| #   | Quy tắc                                                            | Lý do                   |
| --- | ------------------------------------------------------------------ | ----------------------- |
| 1   | **Prepared Statements** cho mọi query có tham số                   | Chống SQL Injection     |
| 2   | **`htmlspecialchars()`** cho mọi output ra HTML                    | Chống XSS               |
| 3   | **CSRF Token** trong mọi form POST                                 | Chống CSRF              |
| 4   | **`password_hash()` / `password_verify()`**                        | Không dùng MD5          |
| 5   | **Không hardcode** bất kỳ text/giá trị nào                         | Dùng `/config/`         |
| 6   | **`header("Location: ...")` + `exit()`** ngay sau redirect         | Không để code chạy tiếp |
| 7   | **`kiemTraSession()`** ở đầu mọi trang admin                       | Bảo vệ route            |
| 8   | **Validate upload:** chỉ jpg/png/webp, max 2MB, đổi tên `uniqid()` | Bảo mật file            |
| 9   | **Soft Delete:** không `DELETE` vật lý                             | An toàn dữ liệu         |
| 10  | **Transaction:** mọi nghiệp vụ multi-table                         | Toàn vẹn dữ liệu        |
| 11  | **Audit Log:** INSERT sau mỗi UPDATE/DELETE quan trọng             | Truy vết                |
| 12  | **`SELECT FOR UPDATE`** khi kiểm tra phòng lúc submit HĐ           | Chống race condition    |
| 13  | **Validate chỉ số ĐN:** `cuoi >= dau` cả client & server           | Tránh hóa đơn âm        |
| 14  | **Wizard localStorage key:** `wizard_{sessionID}_{userID}`         | Tránh xung đột tab      |

---

## 🔀 Git Workflow

```
main          ← Chỉ merge khi ổn định, đã test
  └── develop ← Branch tích hợp nhóm
        ├── feature/db-pdo
        ├── feature/auth-lockout
        ├── feature/race-condition-fix
        ├── feature/tenant-account
        └── ...
```

---

_Đồ án môn Lập trình Web – Khoa Công nghệ Thông tin – Đại học An Giang – 2026_
