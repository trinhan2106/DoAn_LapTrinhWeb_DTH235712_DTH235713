-- =========================================================================
-- SCRIPT SEED DATA TOÀN DIỆN: HỆ THỐNG QUẢN LÝ VẬN HÀNH CHO THUÊ CAO ỐC
-- PHỦ ĐẦY ĐỦ 28 BẢNG — KỊCH BẢN NGHIỆP VỤ ĐẦY ĐỦ
-- MẬT KHẨU TẤT CẢ TÀI KHOẢN: 123456
-- BCRYPT HASH (Cost 10): $2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u
-- CÁCH SỬ DỤNG: Chạy trực tiếp trong phpMyAdmin — KHÔNG cần xử lý thêm.
-- PHIÊN BẢN: 3.0 — Ngày tạo: 2026-04-20
-- =========================================================================

USE quan_ly_cao_oc;

-- =========================================================================
-- BƯỚC 0: VÔ HIỆU HOÁ KIỂM TRA KHÓA NGOẠI & XÓA DỮ LIỆU CŨ
-- Thứ tự TRUNCATE phải inverse với thứ tự phụ thuộc FK
-- =========================================================================
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE PHIEU_THU_CHI_TIET;
TRUNCATE TABLE PHIEU_THU;
TRUNCATE TABLE HOA_DON_VOID;
TRUNCATE TABLE TRANH_CHAP_HOA_DON;
TRUNCATE TABLE HOA_DON;
TRUNCATE TABLE CHI_SO_DIEN_NUOC;
TRUNCATE TABLE MAINTENANCE_STATUS_LOG;
TRUNCATE TABLE MAINTENANCE_REQUEST;
TRUNCATE TABLE YEU_CAU_GIA_HAN;
TRUNCATE TABLE YEU_CAU_THUE;
TRUNCATE TABLE CHI_TIET_GIA_HAN;
TRUNCATE TABLE GIA_HAN_HOP_DONG;
TRUNCATE TABLE TIEN_COC;
TRUNCATE TABLE CHI_TIET_HOP_DONG;
TRUNCATE TABLE HOP_DONG;
TRUNCATE TABLE KHACH_HANG_ACCOUNT;
TRUNCATE TABLE THONG_BAO;
TRUNCATE TABLE LOGIN_ATTEMPT;
TRUNCATE TABLE PHONG_LOCK;
TRUNCATE TABLE PHONG_HINH_ANH;
TRUNCATE TABLE PHONG;
TRUNCATE TABLE TANG;
TRUNCATE TABLE CAO_OC;
TRUNCATE TABLE KHACH_HANG;
TRUNCATE TABLE NHAN_VIEN;
TRUNCATE TABLE AUDIT_LOG;
TRUNCATE TABLE ACTIVITY_LOG;
TRUNCATE TABLE CAU_HINH_HE_THONG;


-- =========================================================================
-- PHẦN 1: CƠ SỞ VẬT CHẤT — TÒA NHÀ, TẦNG, PHÒNG
-- =========================================================================

-- 1.1 CAO_OC — 3 Tòa cao ốc
INSERT INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES
('CO-01', 'Khối A — The Meridian Tower',  '12 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM', 10),
('CO-02', 'Khối B — Pacific Business Hub',  '45 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM',  8),
('CO-03', 'Tòa Nhà Trung Tâm — Saigon Center', '29 Lê Duẩn, Phường Bến Nghé, Quận 1, TP.HCM', 12);


-- 1.2 TANG — 10 Tầng phân bổ cho 3 tòa nhà
INSERT INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES
-- Khối A
('T-A01', 'CO-01', 'Tầng 1 — Khu Vực Kinh Doanh & Tiếp Khách (Khối A)',     1.30),
('T-A02', 'CO-01', 'Tầng 2 — Văn Phòng Hạng A (Khối A)',                     1.10),
('T-A03', 'CO-01', 'Tầng 3 — Văn Phòng Hạng A Premium (Khối A)',             1.20),
-- Khối B
('T-B01', 'CO-02', 'Tầng 1 — Khu Trệt Thương Mại (Khối B)',                  1.25),
('T-B02', 'CO-02', 'Tầng 2 — Văn Phòng Tiêu Chuẩn (Khối B)',                 1.00),
-- Tòa Trung Tâm
('T-C01', 'CO-03', 'Tầng 5 — Tầng Doanh Nghiệp Vừa (Trung Tâm)',            1.00),
('T-C02', 'CO-03', 'Tầng 8 — Tầng Executive (Trung Tâm)',                     1.15),
('T-C03', 'CO-03', 'Tầng 10 — Tầng Penthouse Doanh Nghiệp (Trung Tâm)',      1.40),
('T-C04', 'CO-03', 'Tầng 12 — Tầng Kỹ Thuật & Kho Vật Tư (Trung Tâm)',      0.80),
('T-A04', 'CO-01', 'Tầng Hầm — Bãi Giữ Xe & Kỹ Thuật (Khối A)',             0.70);


-- 1.3 PHONG — 30 Phòng (15 Trống, 10 Đang thuê, 3 Sửa chữa, 2 Lock)
-- trangThai: 1=Trống | 2=Đang Thuê | 3=Đang Sửa Chữa | 4=Lock
INSERT INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, moTaViTri, donGiaM2, giaThue, trangThai) VALUES

-- === KHỐI A TẦNG 1 (Kinh doanh) ===
('A01-101', 'T-A01', 'Mặt Bằng Kinh Doanh A101',  'Mặt bằng kinh doanh', 120.00, 30,
 'Góc phố đắc địa, view Nguyễn Huệ, tiếp giáp thang máy chính', 400000, 48000000, 1),
('A01-102', 'T-A01', 'Gian Hàng Thương Mại A102',  'Mặt bằng kinh doanh', 80.00, 20,
 'Kế bên quầy thông tin tòa nhà, nhiều lượt khách vãng lai', 400000, 32000000, 2),   -- Đang thuê
('A01-103', 'T-A01', 'Phòng Trưng Bày A103',        'Showroom', 100.00, 25,
 'Trần cao 4m, hệ thống đèn chiếu sáng hiện đại', 380000, 38000000, 2),              -- Đang thuê
('A01-104', 'T-A01', 'Kho Hàng Lớn A104',           'Kho lưu trữ', 60.00, 8,
 'Khu vực kho phía sau, có thang chuyên dụng', 150000, 9000000, 1),                  -- Trống

-- === KHỐI A TẦNG 2 (VP Hạng A) ===
('A02-201', 'T-A02', 'Văn Phòng Hạng A — Suite 201', 'Văn phòng riêng', 150.00, 35,
 'View nhìn thẳng ra sông Sài Gòn, nội thất cao cấp sẵn', 350000, 52500000, 2),     -- Đang thuê
('A02-202', 'T-A02', 'Văn Phòng Hạng A — Suite 202', 'Văn phòng riêng', 120.00, 28,
 'Phòng góc, 2 mặt kính trong suốt, ánh sáng tự nhiên tốt', 350000, 42000000, 2),   -- Đang thuê
('A02-203', 'T-A02', 'Văn Phòng Mở A203',            'Không gian làm việc mở', 200.00, 50,
 'Thiết kế open-office, phù hợp startup công nghệ', 320000, 64000000, 1),            -- Trống
('A02-204', 'T-A02', 'Phòng Họp Hội Đồng A204',      'Phòng họp', 50.00, 20,
 'Thiết bị họp thông minh, màn hình 85 inch, âm thanh cách ly', 300000, 15000000, 3), -- Sửa chữa

-- === KHỐI A TẦNG 3 (VP Premium) ===
('A03-301', 'T-A03', 'Premium Suite 301 — Góc VIP',  'Văn phòng riêng', 180.00, 40,
 'Tầng cao, view toàn cảnh đô thị, có phòng tiếp khách riêng', 420000, 75600000, 2), -- Đang thuê
('A03-302', 'T-A03', 'Premium Suite 302',            'Văn phòng riêng', 130.00, 30,
 'Kế bên Suite 301, dễ mở văn phòng liên thông nếu cần', 420000, 54600000, 1),       -- Trống
('A03-303', 'T-A03', 'Flex Office A303',             'Văn phòng riêng', 90.00, 20,
 'Bố cục linh hoạt, phù hợp văn phòng đại diện', 400000, 36000000, 4),              -- Lock (Admin khóa)

-- === KHỐI B TẦNG 1 (Thương mại) ===
('B01-101', 'T-B01', 'Mặt Bằng Thương Mại B101',    'Mặt bằng kinh doanh', 90.00, 20,
 'Mặt tiền đường Lê Lợi, lưu lượng khách đông đúc nhất khu', 380000, 34200000, 1),  -- Trống
('B01-102', 'T-B01', 'Gian Ẩm Thực B102',            'F&B / Nhà hàng', 140.00, 60,
 'Có bếp sẵn, hệ thống hút khói, đã có giấy phép kinh doanh ăn uống', 350000, 49000000, 2), -- Đang thuê
('B01-103', 'T-B01', 'Phòng Dịch Vụ B103',           'Dịch vụ tiêu dùng', 55.00, 10,
 'Phù hợp salon, spa, phòng khám chuyên khoa nhỏ', 320000, 17600000, 3),             -- Sửa chữa

-- === KHỐI B TẦNG 2 (VP Tiêu Chuẩn) ===
('B02-201', 'T-B02', 'Văn Phòng Tiêu Chuẩn B201',   'Văn phòng riêng', 80.00, 18,
 'Bố trí gọn gàng, phù hợp văn phòng 15-20 nhân sự', 280000, 22400000, 1),          -- Trống
('B02-202', 'T-B02', 'Văn Phòng Tiêu Chuẩn B202',   'Văn phòng riêng', 100.00, 22,
 'Đã lắp sẵn vách ngăn và kệ lưu trữ', 280000, 28000000, 2),                        -- Đang thuê
('B02-203', 'T-B02', 'Văn Phòng Tiêu Chuẩn B203',   'Văn phòng riêng', 75.00, 16,
 'Phòng yên tĩnh, phù hợp văn phòng tư vấn, luật sư', 280000, 21000000, 1),         -- Trống
('B02-204', 'T-B02', 'Phòng Kho B204 — Lối Sau',    'Kho lưu trữ', 40.00, 5,
 'Kho phụ kết hợp phòng server, điều kiện không khí ổn định', 130000, 5200000, 1),  -- Trống

-- === TRUNG TÂM TẦNG 5 ===
('C05-501', 'T-C01', 'Suite SME Central 501',         'Văn phòng riêng', 110.00, 25,
 'Tầng 5, ban công nhìn ra khu vực xanh nội khu', 300000, 33000000, 1),              -- Trống
('C05-502', 'T-C01', 'Suite SME Central 502',         'Văn phòng riêng', 110.00, 25,
 'Đối xứng với phòng 501, tiện liên thông hoặc độc lập', 300000, 33000000, 1),       -- Trống
('C05-503', 'T-C01', 'Phòng Huấn Luyện Central 503', 'Phòng đào tạo/hội thảo', 160.00, 80,
 'Thiết kế phòng hội thảo rạp chiếu, micro và máy chiếu 4K sẵn', 250000, 40000000, 3), -- Sửa chữa

-- === TRUNG TÂM TẦNG 8 (Executive) ===
('C08-801', 'T-C02', 'Executive Suite 801',           'Văn phòng riêng', 200.00, 45,
 'Tầng 8, view toàn cảnh Công viên 23/9 và khu trung tâm', 450000, 90000000, 2),    -- Đang thuê (HD đặc biệt)
('C08-802', 'T-C02', 'Executive Suite 802',           'Văn phòng riêng', 160.00, 35,
 'Phòng góc tầng 8, nội thất gỗ tự nhiên cao cấp nguyên bản', 450000, 72000000, 4), -- Lock

-- === TRUNG TÂM TẦNG 10 (Penthouse) ===
('C10-1001', 'T-C03', 'Penthouse Corporate 1001',    'Văn phòng cao cấp', 350.00, 80,
 'Tầng thượng penthouse, sân thượng riêng nhìn 360 độ TP.HCM', 600000, 210000000, 1), -- Trống
('C10-1002', 'T-C03', 'Penthouse Sky Lounge 1002',   'Không gian đa chức năng', 200.00, 60,
 'Kết hợp văn phòng & lounge VIP, bar counter, phù hợp làm showroom thương hiệu', 580000, 116000000, 1), -- Trống

-- === TRUNG TÂM TẦNG KỸ THUẬT (T12) ===
('C12-KT01', 'T-C04', 'Kho Kỹ Thuật & Vật Tư KT01',  'Kho kỹ thuật', 80.00, 5,
 'Khu vực kho vật tư tòa nhà, hạn chế khách thuê thương mại', 100000, 8000000, 1),  -- Trống (nội bộ)

-- === KHỐI A TẦNG HẦM ===
('A00-B01', 'T-A04', 'Bãi Giữ Xe Tầng Hầm B01 (20 slot)', 'Bãi đỗ xe', 200.00, 2,
 '20 chỗ ô tô, 50 chỗ xe máy, hệ thống camera 24/7', 50000, 10000000, 1),             -- Trống
('A00-B02', 'T-A04', 'Kho Kỹ Thuật Tầng Hầm B02',    'Kho kỹ thuật', 100.00, 3,
 'Phòng điện, máy bơm, máy phát — không cho thuê thương mại', 80000, 8000000, 1);    -- Trống (nội bộ)


-- =========================================================================
-- PHẦN 2: NHÂN VIÊN — 5 tài khoản (1 Admin, 2 QLN, 2 Kế toán)
-- =========================================================================
-- MẬT KHẨU: 123456 → BCrypt hash: $2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u
INSERT INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau, deleted_at) VALUES
('NV-01', 'Phạm Minh Quân',   'Quản trị viên Hệ thống',    '0901111111', 'quanpham@meridian.vn',    'admin',    '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 1, 0, NULL),
('NV-02', 'Nguyễn Thị Hương', 'Quản lý Tòa nhà Khối A',    '0902222222', 'huongnguyen@meridian.vn', 'qln_01',   '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 2, 0, NULL),
('NV-03', 'Trần Đức Thắng',   'Quản lý Tòa nhà Khối B & TT','0903333333', 'thangtran@meridian.vn',   'qln_02',   '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 2, 0, NULL),
('NV-04', 'Lê Thị Bích Ngọc', 'Kế toán trưởng',             '0904444444', 'ngocle@meridian.vn',      'ketoan_01','$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 3, 0, NULL),
('NV-05', 'Hoàng Văn Khải',   'Kế toán viên',               '0905555555', 'khaihv@meridian.vn',      'ketoan_02','$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 3, 0, NULL);


-- =========================================================================
-- PHẦN 3: KHÁCH HÀNG — 6 công ty thuê phòng
-- =========================================================================
INSERT INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) VALUES
('KH-01', 'Công ty TNHH Giải Pháp Công Nghệ VietSoft',      '0123456789001', '0911000001', 'contact@vietsoft.com.vn',    '123 Nguyễn Trãi, Phường 2, Quận 5, TP.HCM'),
('KH-02', 'Công ty Cổ Phần Thương Mại Phú Gia Hưng',        '0123456789002', '0911000002', 'info@phugiahung.vn',         '56 Điện Biên Phủ, Phường 15, Bình Thạnh, TP.HCM'),
('KH-03', 'Công ty TNHH Dịch Vụ Ẩm Thực Ngon & Lành',      '0123456789003', '0911000003', 'info@ngon-lanh.vn',          '78 Châu Văn Liêm, Phường 14, Quận 5, TP.HCM'),
('KH-04', 'Công ty CP Tư Vấn Đầu Tư Minh Phát',            '0123456789004', '0911000004', 'admin@minhphat-invest.com',  '34 Lý Tự Trọng, Phường Bến Nghé, Quận 1, TP.HCM'),
('KH-05', 'Tập Đoàn Truyền Thông Đa Phương Tiện MediaVN',  '0123456789005', '0911000005', 'ceo@mediavn.com.vn',         '88 Hoàng Diệu, Phường 13, Quận 4, TP.HCM'),
('KH-06', 'Công ty TNHH SkyLab Innovations (Startup AI)',   '0123456789006', '0911000006', 'hello@skylab.ai',            '10 Nguyễn Bỉnh Khiêm, Phường Đa Kao, Quận 1, TP.HCM');


-- =========================================================================
-- PHẦN 4: TÀI KHOẢN KHÁCH HÀNG (Portal đăng nhập xem hóa đơn)
-- =========================================================================
-- 5 tài khoản phai_doi_matkhau = 0 ; 1 tài khoản phai_doi_matkhau = 1
INSERT INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id, phai_doi_matkhau) VALUES
('ACC-KH01', 'KH-01', 'kh01', '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 4, 0),
('ACC-KH02', 'KH-02', 'kh02', '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 4, 0),
('ACC-KH03', 'KH-03', 'kh03', '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 4, 0),
('ACC-KH04', 'KH-04', 'kh04', '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 4, 0),
('ACC-KH05', 'KH-05', 'kh05', '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 4, 0),
('ACC-KH06', 'KH-06', 'kh06', '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 4, 1); -- ← BẮT BUỘC ĐỔI MẬT KHẨU


-- =========================================================================
-- PHẦN 5: HỢP ĐỒNG — 5 HĐ (3 Đang Hiệu Lực, 1 Chờ Duyệt, 1 Đã Hủy)
-- trangThai: 1=Hiệu lực | 0=Kết thúc | 2=Hủy | 3=Chờ Duyệt
-- =========================================================================
INSERT INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES
-- HD-2025-001: KH-01 (VietSoft) thuê Suite 201+202 Khối A Tầng 2 — Đang hiệu lực
('HD-2025-001', 'KH-01', 'NV-02', '2025-01-03', '2025-01-10', '2026-01-10', 94500000.00, 1, NULL, NULL),
-- HD-2025-002: KH-02 (Phú Gia Hưng) thuê Suite 301 Khối A Tầng 3 — Đang hiệu lực
('HD-2025-002', 'KH-02', 'NV-02', '2025-02-01', '2025-02-05', '2026-02-05', 75600000.00, 1, NULL, NULL),
-- HD-2025-003: KH-05 (MediaVN) thuê Executive Suite 801 — Đang hiệu lực
('HD-2025-003', 'KH-05', 'NV-03', '2025-03-01', '2025-03-10', '2026-03-10', 90000000.00, 1, NULL, NULL),
-- HD-2025-004: KH-06 (SkyLab) thuê các phòng kinh doanh Khối A + Ẩm thực Khối B — CHỜ DUYỆT
('HD-2025-004', 'KH-06', 'NV-02', '2025-04-15', '2025-05-01', '2026-05-01', 49000000.00, 3, NULL, NULL),
-- HD-2024-005: KH-03 (Ngon & Lành) thuê gian ẩm thực — ĐÃ HỦY (Trả phòng sớm)
('HD-2024-005', 'KH-03', 'NV-03', '2024-06-01', '2024-06-15', '2025-06-15', 49000000.00, 2, '2024-11-20', 'Khách hàng thu hẹp quy mô hoạt động, đóng cửa nhà hàng trước hạn. Đã thỏa thuận bồi thường 2 tháng tiền thuê.');


-- =========================================================================
-- PHẦN 6: CHI TIẾT HỢP ĐỒNG — Mapping phòng vào HĐ
-- =========================================================================
INSERT INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue) VALUES
-- HD-2025-001: VietSoft thuê 2 phòng (A02-201 + A02-202)
('CTHD-001', 'HD-2025-001', 'A02-201', 52500000.00),
('CTHD-002', 'HD-2025-001', 'A02-202', 42000000.00),
-- HD-2025-002: Phú Gia Hưng thuê A03-301
('CTHD-003', 'HD-2025-002', 'A03-301', 75600000.00),
-- HD-2025-003: MediaVN thuê Executive C08-801
('CTHD-004', 'HD-2025-003', 'C08-801', 90000000.00),
-- HD-2025-004 (Chờ duyệt): SkyLab (A01-103 + B01-102)
('CTHD-005', 'HD-2025-004', 'A01-103', 38000000.00),
('CTHD-006', 'HD-2025-004', 'B01-102', 49000000.00),
-- HD-2024-005 (Đã hủy): Ngon & Lành thuê B01-102
('CTHD-007', 'HD-2024-005', 'B01-102', 49000000.00);


-- =========================================================================
-- PHẦN 7: TIỀN CỌC — Đủ 4 trạng thái
-- trangThai: 1=Đang giữ | 2=Đã hoàn | 3=Tịch thu | 4=Chờ xử lý (HĐ hủy)
-- =========================================================================
INSERT INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES
-- HD-2025-001: Cọc đang giữ bình thường
('TC-001', 'HD-2025-001', 94500000.00, '2025-01-05 09:30:00', 'ChuyenKhoan', 'NV-04', 1),
-- HD-2025-002: Cọc đang giữ
('TC-002', 'HD-2025-002', 75600000.00, '2025-02-03 10:00:00', 'ChuyenKhoan', 'NV-04', 1),
-- HD-2025-003: Cọc đang giữ
('TC-003', 'HD-2025-003', 90000000.00, '2025-03-05 14:00:00', 'TienMat',     'NV-04', 1),
-- HD-2024-005: Cọc chờ xử lý (Hợp đồng đã hủy, chưa xét duyệt hoàn/tịch thu)
('TC-004', 'HD-2024-005', 49000000.00, '2024-06-13 08:00:00', 'ChuyenKhoan', 'NV-04', 4);


-- =========================================================================
-- PHẦN 8: GIA HẠN HỢP ĐỒNG (Scenario cho HD-2025-001 đã gia hạn 1 lần)
-- =========================================================================
INSERT INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) VALUES
('GH-001', 'HD-2025-001', '2025-12-01', 12, '2027-01-10', 'Gia hạn năm 2 cho VietSoft. Giá thuê giữ nguyên theo thỏa thuận.');

INSERT INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES
('CTGH-001', 'GH-001', 'A02-201', 55000000.00),
('CTGH-002', 'GH-001', 'A02-202', 44000000.00);

-- Yêu cầu gia hạn từ khách Portal
INSERT INTO YEU_CAU_GIA_HAN (maYeuCauGH, soHopDong, thoiGianYeuCau, soThangDeXuat, lyDo, trangThai) VALUES
('YCGH-001', 'HD-2025-002', '2025-11-15 10:00:00', 12, 'Hoạt động kinh doanh ổn định, mong muốn gia hạn thêm 1 năm với mức giá hợp lý.', 0),
('YCGH-002', 'HD-2025-003', '2026-01-20 14:30:00', 6,  'Đang triển khai chiến dịch mùa hè, cần thêm 6 tháng để hoàn tất.', 1);


-- =========================================================================
-- PHẦN 9: HÓA ĐƠN — 10+ HĐ đa dạng (ConNo, DaThu, Void, CreditNote)
-- =========================================================================
INSERT INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, loaiHoaDon, maNV, lyDo, ngayLap) VALUES

-- === HD-2025-001 (VietSoft) — Tháng 1,2,3 đã thanh toán; Tháng 4 còn nợ ===
('INV-2025-001-T01', 'HD-2025-001', 1, 2025, 94500000.00, 94500000.00, 0.00,         'DaThu',        '01/2025', 'Chinh', 'NV-04', 'Tiền thuê văn phòng Suite 201+202 Tháng 01/2025', '2025-01-31 09:00:00'),
('INV-2025-001-T02', 'HD-2025-001', 2, 2025, 94500000.00, 94500000.00, 0.00,         'DaThu',        '02/2025', 'Chinh', 'NV-04', 'Tiền thuê văn phòng Suite 201+202 Tháng 02/2025', '2025-02-28 09:00:00'),
('INV-2025-001-T03', 'HD-2025-001', 3, 2025, 94500000.00, 94500000.00, 0.00,         'DaThu',        '03/2025', 'Chinh', 'NV-04', 'Tiền thuê văn phòng Suite 201+202 Tháng 03/2025', '2025-03-31 09:00:00'),
('INV-2025-001-T04', 'HD-2025-001', 4, 2025, 98500000.00, 50000000.00, 48500000.00, 'DaThuMotPhan', '04/2025', 'Chinh', 'NV-04', 'Tiền thuê T4 (đã cộng thêm tiền điện nước 4tr)', '2025-04-30 09:00:00'),

-- === HD-2025-002 (Phú Gia Hưng — Suite 301) ===
('INV-2025-002-T02', 'HD-2025-002', 2, 2025, 75600000.00, 75600000.00, 0.00,         'DaThu',        '02/2025', 'Chinh', 'NV-04', 'Tiền thuê Premium Suite 301 Tháng 02/2025', '2025-02-28 10:00:00'),
('INV-2025-002-T03', 'HD-2025-002', 3, 2025, 75600000.00, 0.00,         75600000.00, 'ConNo',        '03/2025', 'Chinh', 'NV-04', 'Tiền thuê Premium Suite 301 Tháng 03/2025 — Chưa thanh toán', '2025-03-31 10:00:00'),
('INV-2025-002-T04', 'HD-2025-002', 4, 2025, 75600000.00, 0.00,         75600000.00, 'ConNo',        '04/2025', 'Chinh', 'NV-04', 'Tiền thuê Premium Suite 301 Tháng 04/2025 — Chưa thanh toán', '2025-04-30 10:00:00'),

-- === HD-2025-003 (MediaVN — Executive 801) ===
('INV-2025-003-T03', 'HD-2025-003', 3, 2025, 90000000.00, 90000000.00, 0.00,         'DaThu',        '03/2025', 'Chinh', 'NV-04', 'Tiền thuê Executive Suite 801 Tháng 03/2025', '2025-03-31 11:00:00'),

-- === Hóa đơn SAI → VOID & CreditNote (Cho HD-2025-001) ===
('INV-VOID-ERR01',  'HD-2025-001', 4, 2025, 5000000.00,  0.00,          0.00,         'Void',         '04/2025', 'Chinh',      'NV-04', 'Hóa đơn phụ phí ghi sai số đơn vị điện. Đã void theo yêu cầu KH-01.', '2025-04-15 14:00:00'),
('INV-CN-ERR01',    'HD-2025-001', 4, 2025,-5000000.00,  0.00,          0.00,         'Void',         '04/2025', 'CreditNote', 'NV-04', 'CreditNote đối ứng để xử lý nội bộ cho INV-VOID-ERR01.', '2025-04-15 14:05:00');


-- =========================================================================
-- PHẦN 10: PHIẾU THU & CHI TIẾT PHÂN BỔ (Waterfall)
-- =========================================================================

-- 10.1 Bảng PHIEU_THU
INSERT INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maGiaoDich, maNV, ghiChu) VALUES
-- Thanh toán loạt T1/2025 cho VietSoft
('PT-250101', '2025-01-20 10:15:00', 94500000.00, 'ChuyenKhoan', 'VCB-HD001-20250120', 'NV-04', 'VietSoft chuyển khoản tiền thuê T1/2025 đúng hạn, đầy đủ.'),
-- Thanh toán T2/2025 cho VietSoft
('PT-250201', '2025-02-20 09:30:00', 94500000.00, 'ChuyenKhoan', 'VCB-HD001-20250220', 'NV-04', 'VietSoft chuyển khoản tiền thuê T2/2025 đúng hạn.'),
-- Thanh toán T3/2025 cho VietSoft
('PT-250301', '2025-03-19 14:00:00', 94500000.00, 'ChuyenKhoan', 'VCB-HD001-20250319', 'NV-04', 'VietSoft chuyển khoản tiền thuê T3/2025 sớm 12 ngày.'),
-- Thanh toán T4 một phần cho VietSoft (còn nợ 48.5tr)
('PT-250401', '2025-04-25 11:00:00', 50000000.00, 'TienMat',     NULL,                  'NV-05', 'VietSoft nộp tiền mặt trực tiếp tại văn phòng, còn thiếu 48.5tr.'),
-- Thanh toán T2 cho Phú Gia Hưng
('PT-250202', '2025-02-25 15:30:00', 75600000.00, 'ChuyenKhoan', 'TCB-HD002-20250225', 'NV-04', 'Phú Gia Hưng chuyển khoản T2/2025 đầy đủ.'),
-- Thanh toán T3 cho MediaVN (Executive)
('PT-250302', '2025-03-31 16:00:00', 90000000.00, 'ChuyenKhoan', 'ACB-HD003-20250331', 'NV-04', 'MediaVN chuyển khoản cuối tháng cho T3/2025.');

-- 10.2 Bảng PHIEU_THU_CHI_TIET — Phân bổ waterfall vào từng hóa đơn
INSERT INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES
-- PT-250101 → INV T1 HD-001
('PT-250101', 'INV-2025-001-T01', 94500000.00),
-- PT-250201 → INV T2 HD-001
('PT-250201', 'INV-2025-001-T02', 94500000.00),
-- PT-250301 → INV T3 HD-001
('PT-250301', 'INV-2025-001-T03', 94500000.00),
-- PT-250401 → phân bổ vào INV T4 HD-001 (mới nộp 50tr)
('PT-250401', 'INV-2025-001-T04', 50000000.00),
-- PT-250202 → INV T2 HD-002
('PT-250202', 'INV-2025-002-T02', 75600000.00),
-- PT-250302 → INV T3 HD-003
('PT-250302', 'INV-2025-003-T03', 90000000.00);

-- 10.3 Bảng HOA_DON_VOID — Ghi nhận lý do void
INSERT INTO HOA_DON_VOID (soPhieu, lyDoVoid, maNV_Void, ngayVoid) VALUES
('INV-VOID-ERR01', 'Hóa đơn ghi sai số kW điện do nhập liệu nhầm chỉ số. Kế toán đã xác nhận sai số lệch 50kW. CreditNote tương ứng đã được tạo.', 'NV-04', '2025-04-15 14:00:00');


-- =========================================================================
-- PHẦN 11: CHỈ SỐ ĐIỆN NƯỚC — Các phòng đang thuê (Chỉ số mới > Chỉ số cũ)
-- =========================================================================
INSERT INTO CHI_SO_DIEN_NUOC (maChiSo, maPhong, thangGhi, namGhi, chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi, donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc) VALUES
-- A02-201 (VietSoft Suite 201) — T1, T2, T3
('CSDN-A02201-T012025', 'A02-201', 1, 2025, 3500.00, 3820.00, 120.00, 134.00, 3500.00, 25000.00, 1120000.00, 350000.00),
('CSDN-A02201-T022025', 'A02-201', 2, 2025, 3820.00, 4150.00, 134.00, 149.00, 3500.00, 25000.00, 1155000.00, 375000.00),
('CSDN-A02201-T032025', 'A02-201', 3, 2025, 4150.00, 4490.00, 149.00, 165.00, 3500.00, 25000.00, 1190000.00, 400000.00),
-- A02-202 (VietSoft Suite 202) — T1, T2, T3
('CSDN-A02202-T012025', 'A02-202', 1, 2025, 2800.00, 3050.00, 95.00,  108.00, 3500.00, 25000.00,  875000.00, 325000.00),
('CSDN-A02202-T022025', 'A02-202', 2, 2025, 3050.00, 3310.00, 108.00, 122.00, 3500.00, 25000.00,  910000.00, 350000.00),
('CSDN-A02202-T032025', 'A02-202', 3, 2025, 3310.00, 3580.00, 122.00, 138.00, 3500.00, 25000.00,  945000.00, 400000.00),
-- A03-301 (Phú Gia Hưng — Premium Suite 301) — T2, T3
('CSDN-A03301-T022025', 'A03-301', 2, 2025, 5100.00, 5480.00, 200.00, 218.00, 3500.00, 25000.00, 1330000.00, 450000.00),
('CSDN-A03301-T032025', 'A03-301', 3, 2025, 5480.00, 5860.00, 218.00, 237.00, 3500.00, 25000.00, 1330000.00, 475000.00),
-- C08-801 (MediaVN — Executive Suite 801) — T3
('CSDN-C08801-T032025', 'C08-801', 3, 2025, 8900.00, 9450.00, 310.00, 335.00, 3500.00, 25000.00, 1925000.00, 625000.00),
-- A01-102 (Gian hàng kinh doanh A102 đang thuê — chưa rõ KH, ghi chỉ số vẫn theo dõi)
('CSDN-A01102-T032025', 'A01-102', 3, 2025, 1200.00, 1310.00, 45.00,  51.00,  3500.00, 25000.00,  385000.00, 150000.00),
-- B01-102 (Gian ẩm thực B102 đang thuê)
('CSDN-B01102-T032025', 'B01-102', 3, 2025, 6800.00, 7250.00, 280.00, 308.00, 3500.00, 25000.00, 1575000.00, 700000.00);


-- =========================================================================
-- PHẦN 12: YÊU CẦU THUÊ TỪ PUBLIC (5 yêu cầu — các trạng thái khác nhau)
-- =========================================================================
INSERT INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email, ngayYeuCau, trangThai) VALUES
('YCT-001', 'A02-203', 'Bùi Thanh Tùng',          '0977100001', 'buithanhtung@gmail.com',   '2025-04-10 09:15:00', 0),  -- Chờ duyệt
('YCT-002', 'C05-501', 'Danh Quỳnh Anh',           '0977100002', 'quynhanh.dk@outlook.com',  '2025-04-11 14:00:00', 1),  -- Đã liên hệ
('YCT-003', 'B02-201', 'Nguyễn Hữu Tài',           '0977100003', 'nguyenhuutai92@gmail.com', '2025-04-12 11:30:00', 0),  -- Chờ duyệt
('YCT-004', 'C10-1001','Trần Thị Mỹ Hạnh — HĐQT', '0977100004', 'myhanh@corp-abc.vn',       '2025-04-14 16:00:00', 1),  -- Đã liên hệ (Penthouse)
('YCT-005', 'A01-101', 'Lê Quang Vinh',            '0977100005', 'lqvinh.biz@yahoo.com',     '2025-04-16 08:45:00', 2);  -- Đã hủy (không liên lạc được)


-- =========================================================================
-- PHẦN 13: MAINTENANCE REQUEST & STATUS LOG (3 yêu cầu bảo trì)
-- =========================================================================
INSERT INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT, created_at) VALUES
-- MR-001: Máy lạnh hỏng — Đang xử lý — Ưu tiên Cao
('MR-2025-001', 'A02-201', 'Hệ thống điều hòa trung tâm phòng 201 hỏng quạt dàn lạnh, nhiệt độ không xuống dưới 28°C, ảnh hưởng nghiêm trọng đến làm việc.',  'KH-01', 1, 3, '2025-03-20 08:30:00'),
-- MR-002: Đèn nhấp nháy — Hoàn thành — Ưu tiên Thấp
('MR-2025-002', 'A03-301', 'Đèn LED hành lang phòng 301 nhấp nháy không đều khi bật vào buổi sáng, gây khó chịu cho nhân viên.', 'KH-02', 2, 1, '2025-03-05 09:00:00'),
-- MR-003: Rò rỉ nước — Chờ tiếp nhận — Ưu tiên Khẩn cấp
('MR-2025-003', 'B01-102', 'Ống nước nóng khu bếp nhà hàng B102 bị rò rỉ, nước thấm xuống tường bên dưới. Nguy cơ hư hại vật tư bếp. YÊU CẦU XỬ LÝ GẤP.', 'KH-03', 0, 4, '2025-04-18 07:15:00');

INSERT INTO MAINTENANCE_STATUS_LOG (request_id, trangThaiCu, trangThaiMoi, nguoiCapNhat, created_at) VALUES
-- MR-001: Tiến trình xử lý (0→1)
('MR-2025-001', 0, 1, 'NV-03', '2025-03-20 10:00:00'),
-- MR-002: Đã hoàn thành (0→1→2)
('MR-2025-002', 0, 1, 'NV-03', '2025-03-05 14:00:00'),
('MR-2025-002', 1, 2, 'NV-03', '2025-03-06 16:00:00'),
-- MR-003: Mới tạo, chưa có log chuyển trạng thái (để test)
('MR-2025-003', NULL, 0, 'KH-03', '2025-04-18 07:15:00');


-- =========================================================================
-- PHẦN 14: TRANH CHẤP HÓA ĐƠN (2 khiếu nại)
-- =========================================================================
INSERT INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai, ngayTao) VALUES
-- Khiếu nại 1: KH-01 phản đối chỉ số điện T4 (Đang xử lý)
('TC-HD-001', 'INV-2025-001-T04', 'Chúng tôi phản đối mức tiền điện ghi trong hóa đơn tháng 04/2025 là 4,000,000 VNĐ. Theo đo lường nội bộ của chúng tôi, mức tiêu thụ chỉ khoảng 280 kWh, tương đương khoảng 980,000 VNĐ. Đề nghị kiểm tra lại đồng hồ điện khu vực Suite 201-202.', 1, '2025-04-26 10:00:00'),
-- Khiếu nại 2: KH-02 khiếu nại hóa đơn T3 còn nợ (Mới tạo — chưa xử lý)
('TC-HD-002', 'INV-2025-002-T03', 'Hóa đơn tháng 03/2025 liệt kê phí dịch vụ phát sinh 3,500,000 VNĐ nhưng chúng tôi không nhận được thông báo trước về khoản phí này theo đúng điều khoản hợp đồng. Yêu cầu xuất trình chứng từ chi phí cụ thể.', 0, '2025-04-10 15:30:00');


-- =========================================================================
-- PHẦN 15: PHÒNG - HÌNH ẢNH (Thumbnail cho các phòng đang thuê)
-- =========================================================================
INSERT INTO PHONG_HINH_ANH (id, maPhong, urlHinhAnh, is_thumbnail) VALUES
('IMG-A02201-01', 'A02-201', 'uploads/phong/a02-201/thumb.jpg',  TRUE),
('IMG-A02201-02', 'A02-201', 'uploads/phong/a02-201/view_1.jpg', FALSE),
('IMG-A02201-03', 'A02-201', 'uploads/phong/a02-201/view_2.jpg', FALSE),
('IMG-A03301-01', 'A03-301', 'uploads/phong/a03-301/thumb.jpg',  TRUE),
('IMG-A03301-02', 'A03-301', 'uploads/phong/a03-301/view_1.jpg', FALSE),
('IMG-C08801-01', 'C08-801', 'uploads/phong/c08-801/thumb.jpg',  TRUE),
('IMG-C10-1001-01','C10-1001','uploads/phong/c10-1001/thumb.jpg', TRUE),
('IMG-C10-1001-02','C10-1001','uploads/phong/c10-1001/terrace.jpg', FALSE),
('IMG-B01102-01', 'B01-102', 'uploads/phong/b01-102/thumb.jpg',  TRUE),
('IMG-A01101-01', 'A01-101', 'uploads/phong/a01-101/thumb.jpg',  TRUE);


-- =========================================================================
-- PHẦN 16: THÔNG BÁO HỆ THỐNG (Push đến khách thuê)
-- =========================================================================
INSERT INTO THONG_BAO (maThongBao, tieuDe, noiDung, ngayGui, nguoiNhan, loaiThongBao, daDoc) VALUES
('TB-001', 'Hóa đơn tháng 04/2025 đã sẵn sàng',
 'Kính gửi Quý Công ty, hóa đơn tiền thuê văn phòng tháng 04/2025 đã được phát hành. Vui lòng đăng nhập Cổng Khách Hàng để xem chi tiết và thanh toán trước ngày 20/04/2025.',
 '2025-04-30 08:00:00', 'KH-01', 'HoaDon', TRUE),
('TB-002', 'Nhắc nhở: Hóa đơn tháng 03/2025 chưa thanh toán',
 'Kính gửi Quý Công ty Phú Gia Hưng, chúng tôi ghi nhận hóa đơn tháng 03/2025 trị giá 75,600,000 VNĐ vẫn chưa được thanh toán. Phí trễ hạn sẽ phát sinh từ ngày 21/03/2025. Vui lòng liên hệ ngay để tránh phát sinh thêm chi phí.',
 '2025-04-05 09:00:00', 'KH-02', 'NhacNo', FALSE),
('TB-003', 'Lịch bảo trì định kỳ hệ thống điện — Khối A (20/04/2025)',
 'Kính thông báo: Tòa nhà sẽ tiến hành bảo trì định kỳ hệ thống điện Khối A vào ngày 20/04/2025 từ 08:00 đến 12:00. Điện sẽ bị cắt trong khoảng 2 giờ. Ban Quản Lý xin lỗi vì sự bất tiện này.',
 '2025-04-15 10:00:00', 'KH-01', 'BaoTri', TRUE),
('TB-004', 'Chào mừng gia nhập Hệ thống Cổng Khách Hàng',
 'Kính gửi Quý Công ty MediaVN, tài khoản Cổng Khách Hàng của Quý Công ty đã được kích hoạt. Đăng nhập tại https://meridian.vn/portal với tài khoản: kh05 và mật khẩu mặc định. Vui lòng đổi mật khẩu ngay sau lần đăng nhập đầu tiên.',
 '2025-03-11 08:00:00', 'KH-05', 'TaiKhoan', TRUE);


-- =========================================================================
-- PHẦN 17: PHÒNG LOCK — 2 phòng đang bị khóa (tương ứng trangThai=4)
-- =========================================================================
INSERT INTO PHONG_LOCK (id, maPhong, session_id, locked_at, expire_at) VALUES
('LOCK-A0303', 'A03-303', 'admin-manual-lock-20250401', '2025-04-01 09:00:00', '2099-12-31 23:59:59'),
('LOCK-C0802', 'C08-802', 'admin-manual-lock-20250410', '2025-04-10 14:00:00', '2099-12-31 23:59:59');


-- =========================================================================
-- PHẦN 18: CẤU HÌNH HỆ THỐNG
-- =========================================================================
INSERT INTO CAU_HINH_HE_THONG (key_name, key_value, description) VALUES
('ten_toa_nha',         'The Meridian Tower & Pacific Business Hub', 'Tên hệ thống tòa nhà hiển thị trên báo cáo'),
('don_gia_dien_mac_dinh','3500',   'Đơn giá điện mặc định (VNĐ/kWh) áp dụng khi chưa cài đặt riêng'),
('don_gia_nuoc_mac_dinh','25000',  'Đơn giá nước mặc định (VNĐ/m³) áp dụng khi chưa cài đặt riêng'),
('phi_tre_han_phan_tram','5',      'Phí phạt nộp trễ theo % trên số tiền nợ, áp dụng sau 10 ngày quá hạn'),
('ngay_xuat_hoa_don',   '28',      'Ngày trong tháng để tự động tạo hóa đơn (28 hàng tháng)'),
('ngay_hanh_han_tt',    '20',      'Hạn thanh toán: ngày 20 của tháng tiếp theo'),
('email_bao_cao',        'bqldoan@meridian.vn', 'Email nhận báo cáo tổng hợp hàng tháng'),
('phien_lock_phut',      '10',     'Thời gian giữ phòng tạm thời (phút) khi khách đang xem xét'),
('max_login_attempts',   '5',      'Số lần đăng nhập sai tối đa trước khi tạm khóa IP 30 phút'),
('version_he_thong',     '2.5.0',  'Phiên bản hiện tại của hệ thống');


-- =========================================================================
-- PHẦN 19: AUDIT LOG — Nhật ký thao tác hệ thống
-- =========================================================================
INSERT INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, ipAddress, thoiGian) VALUES
('NV-01', 'CREATE',   'HOP_DONG',       'HD-2025-001', 'Tạo hợp đồng mới cho KH VietSoft, 2 phòng Suite 201+202, 12 tháng.', '192.168.1.10', '2025-01-03 09:00:00'),
('NV-02', 'CREATE',   'HOP_DONG',       'HD-2025-002', 'Tạo hợp đồng mới cho KH Phú Gia Hưng, phòng Premium Suite 301.', '192.168.1.12', '2025-02-01 10:30:00'),
('NV-03', 'CREATE',   'HOP_DONG',       'HD-2025-003', 'Tạo hợp đồng mới cho KH MediaVN, Executive Suite 801.', '192.168.1.15', '2025-03-01 09:15:00'),
('NV-02', 'UPDATE',   'HOP_DONG',       'HD-2024-005', 'Hủy hợp đồng HD-2024-005 (KH Ngon & Lành). Lý do: Thu hẹp hoạt động. Tiền cọc chuyển trạng thái → ChoXuLy.', '192.168.1.12', '2024-11-20 16:00:00'),
('NV-04', 'VOID',     'HOA_DON',        'INV-VOID-ERR01', 'Void hóa đơn phụ phí điện ghi sai. Tạo CreditNote đối ứng INV-CN-ERR01.', '192.168.1.20', '2025-04-15 14:00:00'),
('NV-01', 'LOCK',     'PHONG',          'A03-303', 'Admin khóa phòng A03-303 theo yêu cầu cải tạo nội thất. Dự kiến mở lại 01/06/2025.', '192.168.1.10', '2025-04-01 09:00:00'),
('NV-01', 'LOCK',     'PHONG',          'C08-802', 'Admin khóa phòng C08-802 để dành cho đối tác chiến lược đàm phán độc quyền.', '192.168.1.10', '2025-04-10 14:00:00'),
('NV-04', 'CREATE',   'PHIEU_THU',      'PT-250101', 'Ghi nhận phiếu thu 94,500,000 VNĐ từ VietSoft, tiền thuê T1/2025.', '192.168.1.20', '2025-01-20 10:15:00'),
('NV-01', 'CREATE',   'NHAN_VIEN',      'NV-05', 'Tạo tài khoản nhân viên mới cho kế toán viên Hoàng Văn Khải.', '192.168.1.10', '2025-03-15 08:00:00'),
('NV-01', 'UPDATE',   'CAU_HINH_HE_THONG', 'don_gia_dien_mac_dinh', 'Cập nhật đơn giá điện từ 3200 lên 3500 VNĐ/kWh theo quyết định mới.', '192.168.1.10', '2025-01-01 07:00:00');


-- =========================================================================
-- PHẦN 20: ACTIVITY LOG — Nhật ký truy cập nhân sự
-- =========================================================================
INSERT INTO ACTIVITY_LOG (user_id, ip_address, action, created_at) VALUES
('NV-01', '192.168.1.10', 'Đăng nhập hệ thống thành công', '2025-04-20 07:58:00'),
('NV-02', '192.168.1.12', 'Đăng nhập hệ thống thành công', '2025-04-20 08:01:00'),
('NV-04', '192.168.1.20', 'Đăng nhập hệ thống thành công', '2025-04-20 08:05:00'),
('NV-04', '192.168.1.20', 'Xuất báo cáo tài chính tháng 03/2025',  '2025-04-20 08:30:00'),
('NV-02', '192.168.1.12', 'Xem danh sách phòng trống Khối A',       '2025-04-20 09:15:00'),
('NV-01', '192.168.1.10', 'Duyệt yêu cầu bảo trì MR-2025-003',    '2025-04-20 10:00:00');


-- =========================================================================
-- PHẦN 21: LOGIN ATTEMPT — Giả lập Brute Force attack
-- =========================================================================
INSERT INTO LOGIN_ATTEMPT (username, ip_address, attempt_time, status) VALUES
('kh06', '118.69.12.55', '2025-04-18 23:00:00', 0),
('kh06', '118.69.12.55', '2025-04-18 23:00:30', 0),
('kh06', '118.69.12.55', '2025-04-18 23:01:00', 0),
('kh06', '118.69.12.55', '2025-04-18 23:01:30', 0),
('kh06', '118.69.12.55', '2025-04-18 23:02:00', 0),
('admin','203.162.88.10', '2025-04-19 02:10:00', 0),
('admin','203.162.88.10', '2025-04-19 02:10:15', 0),
('admin','203.162.88.10', '2025-04-19 02:10:30', 0),
('NV-01','192.168.1.10',  '2025-04-20 07:57:45', 0),  -- Nhập sai 1 lần trước khi đăng nhập đúng
('NV-01','192.168.1.10',  '2025-04-20 07:58:00', 1);  -- Đăng nhập thành công lần sau


-- =========================================================================
-- BƯỚC CUỐI: BẬT LẠI CHECK KHÓA NGOẠI
-- =========================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- KIỂM TRA NHANH (CHẠY SAU KHI IMPORT ĐỂ XÁC NHẬN)
-- =========================================================================
-- SELECT 'CAO_OC'             AS `Bảng`, COUNT(*) AS `Số bản ghi` FROM CAO_OC
-- UNION ALL SELECT 'TANG',        COUNT(*) FROM TANG
-- UNION ALL SELECT 'PHONG',       COUNT(*) FROM PHONG
-- UNION ALL SELECT 'NHAN_VIEN',   COUNT(*) FROM NHAN_VIEN
-- UNION ALL SELECT 'KHACH_HANG',  COUNT(*) FROM KHACH_HANG
-- UNION ALL SELECT 'KHACH_HANG_ACCOUNT', COUNT(*) FROM KHACH_HANG_ACCOUNT
-- UNION ALL SELECT 'HOP_DONG',    COUNT(*) FROM HOP_DONG
-- UNION ALL SELECT 'CHI_TIET_HOP_DONG', COUNT(*) FROM CHI_TIET_HOP_DONG
-- UNION ALL SELECT 'TIEN_COC',    COUNT(*) FROM TIEN_COC
-- UNION ALL SELECT 'HOA_DON',     COUNT(*) FROM HOA_DON
-- UNION ALL SELECT 'PHIEU_THU',   COUNT(*) FROM PHIEU_THU
-- UNION ALL SELECT 'PHIEU_THU_CHI_TIET', COUNT(*) FROM PHIEU_THU_CHI_TIET
-- UNION ALL SELECT 'CHI_SO_DIEN_NUOC', COUNT(*) FROM CHI_SO_DIEN_NUOC
-- UNION ALL SELECT 'MAINTENANCE_REQUEST', COUNT(*) FROM MAINTENANCE_REQUEST
-- UNION ALL SELECT 'TRANH_CHAP_HOA_DON', COUNT(*) FROM TRANH_CHAP_HOA_DON
-- UNION ALL SELECT 'YEU_CAU_THUE', COUNT(*) FROM YEU_CAU_THUE;
