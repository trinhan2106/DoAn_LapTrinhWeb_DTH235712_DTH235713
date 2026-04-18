# 📋 BẢNG PHÂN CÔNG CÔNG VIỆC CHI TIẾT (CẬP NHẬT v2)

## Đồ án: Web Hệ thống Quản lý Vận hành Cho thuê Cao ốc

| Thông tin          | Chi tiết                                                        |
| ------------------ | --------------------------------------------------------------- |
| **Môn học**        | Lập trình Web – Lớp DH24TH2                                     |
| **GVHD**           | ThS. Thiều Thanh Quang Phú                                      |
| **Sinh viên 1**    | Trần Trí Nhân – DTH235712                                       |
| **Sinh viên 2**    | Huỳnh Minh Nhật – DTH235713                                     |
| **Tỉ lệ đóng góp** | 50% – 50%                                                       |
| **Quy tắc Git**    | Mỗi task = 1 branch `feature/ten-task`. Merge qua Pull Request. |
| **Phiên bản**      | Chính thức                                                      |

> **Ghi chú phân loại task:**
>
> - 🔴 `[BẮT BUỘC]` – Phải hoàn thành trước khi nộp
> - 🟡 `[BỔ SUNG]` – Làm nếu còn thời gian, không cần thiết cho điểm cốt lõi

---

## 🗂️ KHU VỰC 1: FOUNDATION & CONFIG

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1.1 | `feature/db-pdo` | Tạo kết nối PDO singleton | `includes/common/db.php` | Nhân | PDO, singleton | 🔴 | 🔍 "Cần rà soát (BE)" |
| 1.2 | `feature/config-constants` | Toàn bộ file /config (app, constants, status, messages, business_rules, **roles.php mới**) | `config/*.php` | Nhân | PHP constants | 🔴 | 🔍 "Cần rà soát (BE)" |
| 1.3 | `feature/config-lang` | File đa ngôn ngữ vi/en | `config/lang_vi.php`, `lang_en.php` | Nhân | PHP array, Session | 🔴 | 🔍 "Cần rà soát (BE)" |
| 1.4 | `feature/csrf-auth` | CSRF token, kiemTraSession(), kiemTraQuyen(), **kiemTraRole()** | `includes/common/csrf.php`, `auth.php` | Nhân | PHP Session, hash | 🔴 | 🔍 "Cần rà soát (BE)" |
| 1.5 | `feature/sql-schema` | File SQL **21+ bảng** (thêm PHONG_LOCK, KHACH_HANG_ACCOUNT, LOGIN_ATTEMPT, TIEN_COC, HOA_DON_VOID, YEU_CAU_GIA_HAN, TRANH_CHAP_HOA_DON) + FULLTEXT INDEX + ALTER TABLE | `database/quan_ly_cao_oc.sql` | Nhân | MySQL DDL, Constraints | 🔴 | ✅ "Đã xong (BE)" |
| 1.6 | `feature/public-layout` | Header/Navbar/Footer/Banner public | `includes/public/*.php` | Nhật | Bootstrap 5 | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 1.7 | `feature/admin-layout` | Sidebar + Topbar admin (menu có thêm mục Tenant Account, Tien Coc) | `includes/admin/*.php` | Nhật | Bootstrap Offcanvas | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 1.8 | `feature/dark-mode` | CSS Dark/Light toggle + localStorage key theo userID | `assets/css/dark-mode.css`, `main.js` | Nhật | CSS Custom Properties | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 1.9 | `feature/brand-css` | CSS brand colors, card, badge | `assets/css/style.css` | Nhật | CSS BEM | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **1.10** | **`feature/login-lockout`** | **Đếm lần đăng nhập sai, lockout 15 phút, ghi log IP** | **`includes/common/login_throttle.php`**, **`database/LOGIN_ATTEMPT`** | **Nhân** | **PDO, DateTime** | 🔴 | ✅ "Đã xong (BE)" |
| **1.11** | **`feature/mailer`** | **PHPMailer wrapper – gửi email thông báo** | **`includes/common/mailer.php`** | **Nhân** | **PHPMailer, SMTP** | 🔴 | ✅ "Đã xong (BE)" |

---

## 🗂️ KHU VỰC 2: AUTH & TRANG PUBLIC

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 2.1 | `feature/auth-login-ui` | Form đăng nhập chung: show/hide password, remember me, **hiển thị thông báo lockout còn X phút** | `dangnhap.php` | Nhật | Bootstrap, JS | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 2.2 | `feature/auth-login-submit` | Xử lý login: PDO, password_verify(), ghi Session, **gọi login_throttle.php**, **redirect theo role** | `dangnhap_submit.php` | Nhân | PDO, Session, throttle | 🔴 | ✅ "Đã xong (BE)" |
| 2.3 | `feature/auth-logout` | Hủy session + redirect | `dangxuat.php` | Nhân | session_destroy() | 🔴 | 🔍 "Cần rà soát (BE)" |
| **2.4** | **`feature/force-password-change`** | **Bắt đổi mật khẩu lần đầu đăng nhập (phai_doi_matkhau=1)** | **`modules/ho_so/doi_mat_khau_batbuoc.php`** | **Nhân** | **PDO UPDATE, redirect** | 🔴 | 🔍 "Cần rà soát (BE)" |
| 2.5 | `feature/public-index` | Trang chủ: hero banner, phòng nổi bật, filter | `index.php` | Nhật | Bootstrap carousel | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 2.6 | `feature/public-phong-trong` | Danh sách phòng trống + filter nâng cao | `phong_trong.php` | Nhật | PHP GET, phân trang | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 2.7 | `feature/public-chi-tiet` | Chi tiết phòng: gallery lightbox + form đăng ký | `chi_tiet_phong.php` | Nhật | JS lightbox | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 2.8 | `feature/public-dangky-submit` | INSERT YEU_CAU_THUE + **gửi email xác nhận kèm mã YC** | `dang_ky_thue_submit.php` | Nhân | PDO, mailer.php | 🔴 | ✅ "Đã xong (BE)" |
| 2.9 | `feature/public-gioi-thieu` | Trang giới thiệu & liên hệ | `gioi_thieu.php` | Nhật | Bootstrap, HTML | 🔴 | ⏳ "Chờ Nhật (FE)" |

---

## 🗂️ KHU VỰC 3: DASHBOARD & DANH MỤC

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 3.1 | `feature/dashboard-admin` | Dashboard admin: 4 KPI, Chart.js, bảng HĐ hết hạn | `modules/dashboard/admin.php` | Nhật | Chart.js, PDO | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 3.2 | `feature/dashboard-personal` | Dashboard cá nhân theo bộ phận | `modules/dashboard/personal.php` | Nhật | PHP Session role | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 3.3 | `feature/crud-cao-oc` | CRUD Cao ốc + Soft Delete | `modules/cao_oc/*.php` | Nhật | PDO JOIN | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 3.4 | `feature/crud-tang` | CRUD Tầng + heSoGia | `modules/tang/*.php` | Nhật | PDO, FK | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 3.5 | `feature/crud-phong-list` | Danh sách phòng: filter + DataTables | `modules/phong/phong_hienthi.php` | Nhật | DataTables BS5 | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 3.6 | `feature/crud-phong-form` | Form thêm/sửa phòng + tính giá real-time | `modules/phong/phong_them.php` | Nhật | JS oninput | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 3.7 | `feature/crud-phong-submit` | INSERT/UPDATE Phòng + Validate | `modules/phong/phong_them_submit.php` | Nhân | PDO, Validate | 🔴 | 🔍 "Cần rà soát (BE)" |
| 3.8 | `feature/phong-upload` | Upload gallery ảnh: validate + uniqid + preview | `modules/phong/phong_upload.php` | Nhật | PHP $_FILES | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 3.9 | `feature/phong-delete` | Soft Delete phòng + kiểm tra HĐ đang hiệu lực **và PHONG_LOCK** | `modules/phong/phong_xoa.php` | Nhân | PDO, deleted_at | 🔴 | 🔍 "Cần rà soát (BE)" |

---

## 🗂️ KHU VỰC 4: KHÁCH HÀNG, NHÂN VIÊN & TENANT ACCOUNT

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 4.1 | `feature/crud-khachhang` | CRUD Khách hàng + Modal confirm xóa + Soft Delete | `modules/khach_hang/*.php` | Nhật | Bootstrap Modal | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 4.2 | `feature/khachhang-history` | Tab lịch sử HĐ + Hóa đơn của khách | `modules/khach_hang/kh_lichsu.php` | Nhật | PDO JOIN, Bootstrap Tabs | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 4.3 | `feature/crud-nhanvien` | CRUD NV (Admin only) + **chặn xóa NV có hóa đơn ConNo** | `modules/nhan_vien/*.php` | Nhân | Session role check | 🔴 | 🔍 "Cần rà soát (BE)" |
| **4.4** | **`feature/tenant-account-crud`** | **CRUD tài khoản KH (Admin): tạo, reset mật khẩu, kích hoạt/khóa** | **`modules/khach_hang_account/*.php`** | **Nhân** | **PDO, password_hash** | 🔴 | 🔍 "Cần rà soát (BE)" |
| **4.5** | **`feature/tenant-login`** | **Đăng nhập riêng cho Khách hàng + lockout + buộc đổi MK** | **`modules/khach_hang_account/kh_dangnhap.php`** | **Nhân** | **PDO Session, throttle** | 🔴 | 🔍 "Cần rà soát (BE)" |
| 4.6 | `feature/ho-so-canhan` | Hồ sơ cá nhân + Đổi mật khẩu (mọi role) | `modules/ho_so/*.php` | Nhân | PDO UPDATE | 🔴 | 🔍 "Cần rà soát (BE)" |

---

## 🗂️ KHU VỰC 5: HỢP ĐỒNG – CORE LOGIC

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **5.0** | **`feature/room-lock`** | **API lock/unlock phòng tạm (AJAX) + heartbeat JS mỗi 3 phút** | **`modules/phong/phong_lock.php`**, **`assets/js/room-lock.js`** | **Nhân** | **PDO, AJAX, setTimeout** | 🔴 | ✅ "Đã xong (BE)" |
| 5.1 | `feature/hd-wizard-ui` | Wizard 4 bước UI + **gọi room-lock.js ở bước 2** | `modules/hop_dong/hd_them.php` | Nhân | JS step, Bootstrap | 🔴 | ✅ "Đã xong (BE)" |
| 5.2 | `feature/hd-wizard-autosave` | Autosave wizard vào localStorage key `wizard_{sessionID}_{userID}` | `assets/js/wizard.js` | Nhân | localStorage, JSON | 🔴 | ✅ "Đã xong (BE)" |
| **5.3** | **`feature/hd-them-submit`** | **Transaction + SELECT FOR UPDATE + trangThai='ChoDuyet' ban đầu** | **`modules/hop_dong/hd_them_submit.php`** | **Nhân** | **PDO Transaction, locking** | 🔴 | ✅ "Đã xong (BE)" |
| **5.4** | **`feature/hd-ky`** | **UC04: Ký hợp đồng → chuyển ChoDuyet → DangHieuLuc + unlock phòng** | **`modules/hop_dong/hd_ky.php`**, **`hd_ky_submit.php`** | **Nhân** | **PDO UPDATE, Transaction** | 🔴 | ✅ "Đã xong (BE)" |
| 5.5 | `feature/hd-list` | Danh sách HĐ + filter trạng thái + badge (bao gồm "Nháp") | `modules/hop_dong/hd_hienthi.php` | Nhân | PDO JOIN, DataTables | 🔴 | ✅ "Đã xong (BE)" |
| 5.6 | `feature/hd-giahan-ui` | Form gia hạn: 3 điều kiện stepper | `modules/hop_dong/hd_gia_han.php` | Nhân | JS real-time | 🔴 | ✅ "Đã xong (BE)" |
| 5.7 | `feature/hd-giahan-submit` | Transaction gia hạn | `modules/hop_dong/hd_gia_han_submit.php` | Nhân | PDO Transaction | 🔴 | ✅ "Đã xong (BE)" |
| **5.8** | **`feature/hd-duyet-giahan`** | **Duyệt yêu cầu gia hạn online từ KH (YEU_CAU_GIA_HAN)** | **`modules/hop_dong/hd_duyet_giahan.php`** | **Nhân** | **PDO, notification** | 🔴 | ✅ "Đã xong (BE)" |
| 5.9 | `feature/hd-ketthucle` | Kết thúc thuê phòng lẻ + Transaction | `modules/hop_dong/hd_ket_thuc_le*.php` | Nhân | PDO Transaction | 🔴 | ✅ "Đã xong (BE)" |
| 5.10 | `feature/hd-huy` | Hủy HĐ: kiểm tra nợ **và tiền cọc**, Transaction | `modules/hop_dong/hd_huy*.php` | Nhân | PDO Transaction | 🔴 | ✅ "Đã xong (BE)" |
| **5.11** | **`feature/tien-coc`** | **Theo dõi tiền cọc đã thu + luồng hoàn cọc khi HĐ kết thúc đúng hạn** | **`modules/tien_coc/*.php`** | **Nhân** | **PDO, business logic** | 🔴 | 🔍 "Cần rà soát (BE)" |

---

## 🗂️ KHU VỰC 6: THANH TOÁN & DỊCH VỤ

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **6.1** | **`feature/tt-bùtra-ui`** | **Form thanh toán: box bù trừ + chọn phương thức (TienMat/ChuyenKhoan/Vi) + mã GD** | **`modules/thanh_toan/tt_tao.php`** | **Nhân** | **JS oninput, SELECT** | 🔴 | ✅ "Đã xong (BE)" |
| **6.2** | **`feature/tt-submit`** | **SELECT FOR UPDATE trước khi tạo HĐ → tránh double payment. Gửi email hóa đơn cho KH** | **`modules/thanh_toan/tt_tao_submit.php`** | **Nhân** | **PDO locking, mailer** | 🔴 | ✅ "Đã xong (BE)" |
| **6.3** | **`feature/tt-void`** | **Void hóa đơn sai: tạo credit note, INSERT HOA_DON_VOID, Audit Log. Chỉ Admin/Trưởng BP** | **`modules/thanh_toan/tt_void*.php`** | **Nhân** | **PDO, business rule** | 🔴 | ✅ "Đã xong (BE)" |
| **6.4** | **`feature/dien-nuoc-ui`** | **Form ghi chỉ số: validate delta >= 0 cả client lẫn server. Hiển thị cảnh báo nếu delta > 9999** | **`modules/thanh_toan/dien_nuoc_ghi.php`** | **Nhân** | **JS validate, PHP CHECK** | 🔴 | ✅ "Đã xong (BE)" |
| 6.5 | `feature/dien-nuoc-submit` | INSERT CHI_SO + tạo HOA_DON dịch vụ + Transaction | `modules/thanh_toan/dien_nuoc_ghi_submit.php` | Nhân | PDO Transaction | 🔴 | ✅ "Đã xong (BE)" |
| **6.6** | **`feature/tranh-chap-hd`** | **Kế toán xử lý ticket tranh chấp hóa đơn từ KH: xem, phản hồi, đóng ticket** | **`modules/thanh_toan/tranh_chap*.php`** | **Nhật** | **PDO, notification** | 🔴 | ⏳ "Chờ Nhật (FE)" |

---

## 🗂️ KHU VỰC 7: TENANT DASHBOARD (Khách hàng đăng nhập)

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **7.1** | **`feature/tenant-dashboard`** | **Dashboard KH: tóm tắt HĐ, ngày hết hạn, số tiền nợ hiện tại** | **`modules/tenant/dashboard.php`** | **Nhật** | **PDO, Bootstrap** | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **7.2** | **`feature/tenant-hopdong`** | **Chi tiết HĐ + trạng thái từng phòng** | **`modules/tenant/hop_dong.php`** | **Nhật** | **PDO JOIN** | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **7.3** | **`feature/tenant-hoadon`** | **Lịch sử hóa đơn: từng kỳ, số tiền, trạng thái, nút "Yêu cầu kiểm tra lại"** | **`modules/tenant/hoa_don.php`** | **Nhật** | **PDO, Bootstrap Tabs** | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **7.4** | **`feature/tenant-yeu-cau-giahan`** | **Form gửi yêu cầu gia hạn online: chọn số tháng từng phòng → INSERT YEU_CAU_GIA_HAN** | **`modules/tenant/yeu_cau_giahan.php`** | **Nhật** | **JS, PDO** | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **7.5** | **`feature/tenant-tranh-chap`** | **Gửi tranh chấp hóa đơn → INSERT TRANH_CHAP_HOA_DON** | **`modules/tenant/tranh_chap.php`** | **Nhật** | **PDO, Validate** | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **7.6** | **`feature/tenant-maintenance`** | **Gửi yêu cầu sửa chữa + xem tiến độ timeline (từ tenant side)** | **`modules/tenant/maintenance.php`** | **Nhật** | **PDO, Bootstrap Steps** | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **7.7** | **`feature/tenant-dien-nuoc`** | **Lịch sử tiêu thụ điện/nước theo tháng + biểu đồ** | **`modules/tenant/dien_nuoc.php`** | **Nhật** | **Chart.js, PDO** | 🔴 | ⏳ "Chờ Nhật (FE)" |

---

## 🗂️ KHU VỰC 8: BÁO CÁO, TÌM KIẾM & THÔNG BÁO

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 8.1 | `feature/baocao-tabs` | 4 tab báo cáo | `modules/bao_cao/bao_cao.php` | Nhật | Bootstrap Tabs, SQL | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 8.2 | `feature/baocao-export` | Export CSV + In trang | `modules/bao_cao/export_csv.php` | Nhật | fputcsv(), Print CSS | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **8.3** | **`feature/global-search`** | **Tìm kiếm toàn cục dùng FULLTEXT INDEX thay vì LIKE** | **`tim_kiem.php`** | **Nhật** | **MATCH AGAINST, UNION** | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 8.4 | `feature/notification-center` | Chuông dropdown AJAX | `includes/admin/notifications.php` | Nhật | AJAX, PDO | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **8.5** | **`feature/email-notification`** | **Trigger gửi email: hóa đơn mới → KH, HĐ sắp hết hạn → QLN, YC được duyệt → KH** | **`modules/thong_bao/email_trigger.php`** | **Nhân** | **mailer.php, cron-like** | 🔴 | 🔍 "Cần rà soát (BE)" |
| 8.6 | `feature/yeu-cau-thue` | Danh sách YCT + Timeline stepper + Duyệt/Từ chối | `modules/yeu_cau_thue/*.php` | Nhật | PDO, Bootstrap Steps | 🔴 | ⏳ "Chờ Nhật (FE)" |

---

## 🗂️ KHU VỰC 9: TÍNH NĂNG NÂNG CAO & HOÀN THIỆN

| # | Branch | Task | File đầu ra | Người | Kỹ thuật | Ưu tiên | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 9.1 | `feature/export-pdf` | Export PDF hợp đồng & hóa đơn | `assets/js/export-pdf.js` | Nhật | html2pdf.js | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **9.2** | **`feature/qr-code-secure`** | **QR Code dùng JWT token 15 phút (không link public), tenant_portal verify token** | **`modules/tenant_portal/index.php`**, **`assets/js/qrcode-init.js`** | **Nhật** | **JWT (php-jwt), qrcode.js** | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 9.3 | `feature/maintenance-form` | Form gửi YC sửa chữa + **chọn priority** | `modules/maintenance/yc_them.php` | Nhân | PDO, SELECT | 🔴 | 🔍 "Cần rà soát (BE)" |
| **9.4** | **`feature/maintenance-sla`** | **Admin duyệt + tự tính sla_deadline từ CAU_HINH + countdown timer + gửi email khi status đổi** | **`modules/maintenance/yc_quan_ly.php`**, **`yc_notify.php`** | **Nhân** | **DateTime, mailer** | 🔴 | 🔍 "Cần rà soát (BE)" |
| 9.5 | `feature/soft-delete` | Thùng rác: xem + khôi phục | `modules/thung_rac/index.php` | Nhân | PDO deleted_at | 🔴 | ✅ "Đã xong (BE)" |
| **9.6** | **`feature/audit-log`** | **Xem Audit Log append-only: không có nút Delete** | **`modules/audit_log/index.php`** | **Nhân** | **PDO JOIN, DataTables** | 🔴 | ✅ "Đã xong (BE)" |
| 9.7 | `feature/cau-hinh` | Admin cấu hình hệ thống (bao gồm LOCK_PHONG_PHUT, SLA, LOCKOUT) | `modules/cau_hinh/index.php` | Nhân | PDO GET/UPDATE | 🔴 | 🔍 "Cần rà soát (BE)" |
| 9.8 | `feature/advanced-filter` | DataTables trên mọi bảng | `assets/js/datatables-init.js` | Nhật | DataTables BS5 | 🔴 | ⏳ "Chờ Nhật (FE)" |
| **9.9** | **`feature/realtime-js`** | **JS real-time: tính giá thuê, bù trừ nợ, validate delta điện/nước >= 0, cảnh báo delta bất thường** | **`assets/js/realtime-calc.js`** | **Nhân** | **Vanilla JS, oninput** | 🔴 | ✅ "Đã xong (BE)" |
| 9.10 | `feature/lang-toggle` | Toggle Tiếng Việt / English | `config/lang_vi.php`, `lang.js` | Nhân | PHP Session, JS | 🔴 | 🔍 "Cần rà soát (BE)" |
| 9.11 | `feature/ui-polish` | Responsive hoàn thiện, toast, loading spinner | `assets/css/style.css`, `main.js` | Nhật | CSS3, Bootstrap Toast | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 9.12 | `feature/chatbot` | Rule-based chatbot tra cứu phòng | `modules/chatbot/bot.php`, `chatbot.js` | Nhật | JS, PHP endpoint | 🔴 | ⏳ "Chờ Nhật (FE)" |
| 9.13 | `feature/fix-bugs` | Test toàn hệ thống, fix lỗi | Toàn bộ | Cả 2 | Debug, Test case | 🔴 | 🔍 "Cần rà soát (BE)" |

---

## 🟡 KHU VỰC 10: TÍNH NĂNG BỔ SUNG (Làm nếu kịp)

> Không ảnh hưởng điểm cốt lõi. Hoàn thành sẽ tạo ấn tượng khi demo.

| # | Branch | Task | File đầu ra | Người | Độ khó | Ghi chú | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- | --- |
| B1 | `feature/bonus-phat-cham` | Tính phạt trả chậm % / ngày từ CAU_HINH khi kỳ trước còn nợ quá hạn | `modules/thanh_toan/tt_tao.php` (bổ sung) | Nhân | Trung bình | Thêm vào form thanh toán, tính thêm tiền phạt nếu ConNo kỳ trước > 0 quá N ngày | 🔍 "Cần rà soát (BE)" |
| B2 | `feature/bonus-them-phong-hd` | Bổ sung phòng vào HĐ đang hiệu lực (UC12): INSERT CTHD mới | `modules/hop_dong/hd_them_phong.php` | Nhân | Trung bình | Tạo luồng riêng, không tạo HĐ mới | 🔍 "Cần rà soát (BE)" |
| B3 | `feature/bonus-chart-diennuoc` | Biểu đồ xu hướng tiêu thụ điện/nước theo tháng trong Tenant Dashboard | `modules/tenant/dien_nuoc.php` (Chart.js) | Nhật | Dễ | Chart.js line chart, dữ liệu từ CHI_SO_DIEN_NUOC | ⏳ "Chờ Nhật (FE)" |
| B4 | `feature/bonus-override-giahan` | Cho phép Trưởng BP bypass cửa sổ 30 ngày gia hạn với lý do + ghi Audit | `modules/hop_dong/hd_gia_han.php` (bổ sung) | Nhân | Dễ | Thêm checkbox "Override" chỉ hiện với role Trưởng BP | 🔍 "Cần rà soát (BE)" |
| B5 | `feature/bonus-checklist-phong` | Checklist kiểm tra phòng khi trả (UC10): form + upload ảnh + ký tên | `modules/hop_dong/hd_ket_thuc_le.php` (bổ sung) | Nhật | Dễ | Thêm bước cuối vào UC10 | ⏳ "Chờ Nhật (FE)" |
| B6 | `feature/bonus-bang-gia-dn` | Bảng giá điện/nước theo effective_date để tra đúng giá khi giá thay đổi giữa kỳ | `database/BANG_GIA_DICH_VU.sql`, `modules/cau_hinh/gia_dn.php` | Nhân | Khó | Cần bảng mới + logic tra giá theo ngày | 🔍 "Cần rà soát (BE)" |
| B7 | `feature/bonus-2fa` | 2FA cho Admin bằng TOTP (Google Authenticator) | `includes/common/totp.php`, `dangnhap.php` | Nhân | Khó | Dùng thư viện PHPGangsta/GoogleAuthenticator | 🔍 "Cần rà soát (BE)" |
| B8 | `feature/bonus-forecast` | Báo cáo dự báo doanh thu: extrapolate từ HĐ hiệu lực × tháng còn lại | `modules/bao_cao/du_bao.php` | Nhật | Trung bình | Thêm tab 5 vào bao_cao.php | ⏳ "Chờ Nhật (FE)" |
| B9 | `feature/bonus-bulk-email` | Gửi email hàng loạt cho HĐ sắp hết hạn trong tháng (nút từ báo cáo) | `modules/bao_cao/bulk_email.php` | Nhân | Dễ | Loop qua danh sách → gọi mailer.php | 🔍 "Cần rà soát (BE)" |
| B10 | `feature/bonus-import-excel` | Import dữ liệu Khách hàng / Phòng từ file Excel | `modules/import/import_excel.php` | Nhân | Khó | SheetJS parse phía client → POST JSON → PHP INSERT | 🔍 "Cần rà soát (BE)" |

---

## 📊 TỔNG KẾT TASK

### Task bắt buộc (🔴)

| Người                       | Khu vực chủ lực                         | Số task      | Tỉ lệ    |
| --------------------------- | --------------------------------------- | ------------ | -------- |
| Trần Trí Nhân (DTH235712)   | Backend, Logic, Security, Core business | ~42 task     | 50%      |
| Huỳnh Minh Nhật (DTH235713) | Frontend, UI, Tenant Dashboard, Reports | ~40 task     | 50%      |
| **Tổng**                    |                                         | **~82 task** | **100%** |

### Task bổ sung (🟡): 10 task, phân công khi có thời gian

---

## 📅 MILESTONE

| Tuần         | Mục tiêu                                                          | Task cần merge    |
| ------------ | ----------------------------------------------------------------- | ----------------- |
| **Tuần 1**   | Foundation + Auth (bao gồm lockout) + Layout + DB đầy đủ          | 1.1–1.11, 2.1–2.9 |
| **Tuần 2**   | Dashboard + Danh mục + Tenant Account + Tài khoản KH              | 3.1–3.9, 4.1–4.6  |
| **Tuần 3**   | Core: HĐ (lock phòng, nháp, ký, gia hạn, hủy, tiền cọc)           | 5.0–5.11          |
| **Tuần 4**   | Thanh toán (void, validate, tranh chấp) + Tenant Dashboard đầy đủ | 6.1–6.6, 7.1–7.7  |
| **Tuần 5**   | Báo cáo + Email + Tính năng nâng cao + Test + Slide               | 8.1–8.6, 9.1–9.13 |
| **Dự phòng** | Bug fix + Task bổ sung nếu kịp                                    | B1–B10 (chọn lọc) |

---

## ⚠️ CHECKLIST TRƯỚC KHI MERGE

### Code Quality

- [ ] Code chạy không lỗi syntax (`php -l file.php`)
- [ ] Không hardcode bất kỳ text/giá trị cố định nào
- [ ] Mọi query có tham số đều dùng PDO Prepared Statements
- [ ] Mọi form POST có CSRF token
- [ ] Mọi output ra HTML đi qua `htmlspecialchars()`
- [ ] `header("Location: ...")` có `exit()` ngay sau
- [ ] Upload file: validate định dạng, kích thước, đổi tên `uniqid()`

### Business Logic

- [ ] Soft Delete: dùng `UPDATE deleted_at` thay vì `DELETE`
- [ ] Audit Log: INSERT vào AUDIT_LOG sau mỗi thao tác quan trọng
- [ ] Transaction: mọi nghiệp vụ multi-table có BEGIN/COMMIT/ROLLBACK
- [ ] HĐ submit: có `SELECT ... FOR UPDATE` kiểm tra trạng thái phòng
- [ ] Phòng chỉ chuyển `DangThue` khi HĐ chuyển từ `ChoDuyet → DangHieuLuc`
- [ ] Chỉ số điện/nước: validate `cuoi >= dau` cả client AND server-side
- [ ] Void hóa đơn: không UPDATE bản ghi gốc, tạo credit note mới
- [ ] Wizard localStorage key phải bao gồm `sessionID + userID`
- [ ] Audit log module không có nút Delete

### UI/UX

- [ ] Test responsive trên Chrome mobile view (375px, 768px, 1440px)
- [ ] Dark mode không bị vỡ layout
- [ ] DataTables hiển thị đúng tiếng Việt
- [ ] Toast thông báo sau mọi action thành công/lỗi
- [ ] Form ghi chỉ số: hiển thị cảnh báo đỏ ngay khi `cuoi < dau`
- [ ] Trang đăng nhập: hiển thị thông báo "Tài khoản bị khóa X phút" rõ ràng

### Security

- [ ] Không có `MD5()` nào trong code
- [ ] Không có câu SQL ghép chuỗi từ `$_GET` / `$_POST`
- [ ] Không lộ thông tin DB trong error message
- [ ] Login throttle hoạt động: thử sai 5 lần → bị khóa 15 phút
- [ ] QR Code: dùng JWT token ngắn hạn, không phải link public

---

## 📝 QUY TẮC GIT COMMIT MESSAGE

```
[AREA] Mô tả ngắn gọn

AREA bổ sung mới:
  [LOCK]    – Room locking, race condition prevention
  [TENANT]  – Tenant account, tenant dashboard
  [VOID]    – Invoice void, credit note
  [COC]     – Tiền cọc, hoàn cọc
  [SEC]     – Security: lockout, JWT, throttle
  [EMAIL]   – Email notification
  [SLA]     – Maintenance SLA, priority
  [BONUS]   – Tính năng bổ sung (B1–B10)

Ví dụ:
  [LOCK] Thêm API lock/unlock phòng tạm với expire 10 phút
  [TENANT] Hoàn thành dashboard cá nhân Khách hàng
  [VOID] Tạo luồng void hóa đơn + credit note
  [SEC] Thêm login lockout sau 5 lần sai
  [BONUS] B3 – Chart tiêu thụ điện nước Tenant Dashboard
```

---

_Đồ án môn Lập trình Web – Lớp DH24TH2 – Khoa Công nghệ Thông tin – Đại học An Giang – 2026_
_Cập nhật v2 – Sau QA Review toàn hệ thống_
