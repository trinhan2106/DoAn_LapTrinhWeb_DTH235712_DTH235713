-- =========================================================================
-- SCRIPT AUTO-GEN SEED DATA CHO DATABASE `quan_ly_cao_oc`
-- YÊU CẦU: BAO PHỦ 28 BẢNG - FULL KỊCH BẢN NGHIỆP VỤ (SCENARIOS)
-- CÁCH DÙNG: Import trực tiếp file này chạy thẳng vào phpMyAdmin.
-- MẬT KHẨU TOÀN BỘ TÀI KHOẢN: 123456
-- =========================================================================

USE quan_ly_cao_oc;

-- Vô hiệu hóa Check Khóa Ngoại tạm thời để Insert không bị lỗi Ràng buộc chéo
SET FOREIGN_KEY_CHECKS = 0;

-- (Tùy chọn) Xóa dữ liệu cũ của các bảng nếu muốn một DB hoàn toàn mới
-- TRUNCATE TABLE KHACH_HANG_ACCOUNT;
-- TRUNCATE TABLE KHACH_HANG;
-- ... vv (Ở đây ta mặc định Insert thêm, nếu bạn đã có data cũ có thể cân nhắc)


-- =========================================================================
-- 1. CƠ SỞ VẬT CHẤT & NHÂN SỰ
-- =========================================================================

-- 1.1 Tạo 2 Cao ốc (Sử dụng IGNORE để bỏ qua lỗi nếu đã có Data sẵn từ script tạo DB)
INSERT IGNORE INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES
('CO-01', 'The Sapphire Tower', '123 Cách Mạng Tháng 8, Quận 1, TP.HCM', 15),
('CO-02', 'Ruby Plaza', '45 Lê Lợi, Bến Nghé, Quận 1, TP.HCM', 10);

-- 1.2 Tạo 4 Tầng thuộc 2 Cao ốc
INSERT IGNORE INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES
('T-S01', 'CO-01', 'Tầng Trệt Kinh Doanh (Khối A)', 1.25),
('T-S02', 'CO-01', 'Tầng Văn Phòng VIP (Khối A)', 1.00),
('T-R01', 'CO-02', 'Tầng Tích Hợp (Ruby)', 1.00),
('T-R02', 'CO-02', 'Tầng Thượng (Ruby)', 1.10);

-- 1.3 Tạo 10 Phòng (Đủ trạng thái trống, đang thuê, bị khóa, sửa chữa)
INSERT IGNORE INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai) VALUES
-- Tầng 1 Sapphire (T-S01)
('P-S01-01', 'T-S01', 'Sapphire Store 101', 'Mặt bằng kinh doanh', 100, 20, 300000, 30000000, 1),
('P-S01-02', 'T-S01', 'Sapphire Store 102', 'Mặt bằng kinh doanh', 80,  15, 300000, 24000000, 1),
('P-S01-03', 'T-S01', 'Sapphire 103 (Kho)', 'Kho lưu trữ', 50, 5, 100000, 5000000, 1),
-- Tầng 2 Sapphire (T-S02)
('P-S02-01', 'T-S02', 'Sapphire Exec 201', 'Văn phòng riêng', 120, 25, 350000, 42000000, 2), -- Đang thuê (Happy Path)
('P-S02-02', 'T-S02', 'Sapphire Exec 202', 'Văn phòng riêng', 100, 20, 350000, 35000000, 2), -- Đang thuê (Happy Path)
('P-S02-03', 'T-S02', 'Sapphire Locked 203', 'Văn phòng riêng', 60, 10, 350000, 21000000, 4), -- Lock (Admin khóa test)
-- Tầng 1 Ruby (T-R01)
('P-R01-01', 'T-R01', 'Ruby Biz 101', 'Văn phòng làm việc', 80, 15, 250000, 20000000, 2), -- Đang thuê (Nợ Waterfall)
('P-R01-02', 'T-R01', 'Ruby Biz 102', 'Văn phòng làm việc', 80, 15, 250000, 20000000, 1), -- Trống (HĐ đã bị hủy trước hạn)
-- Tầng 2 Ruby (T-R02)
('P-R02-01', 'T-R02', 'Ruby Premium 201', 'Văn phòng riêng', 140, 30, 250000, 35000000, 2), -- Đang thuê (Gia hạn HĐ)
('P-R02-02', 'T-R02', 'Ruby Premium 202', 'Văn phòng riêng', 90, 18, 250000, 22500000, 1);  -- Trống

-- 1.4 Tạo Nhân viên (Gồm Admin, Kế Toán, QLN, Nhân viên block)
INSERT IGNORE INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau, deleted_at) VALUES
('NV-AD01', 'Nguyễn Quản Trị', 'System Admin', '0901111111', 'admin@hesys.com', 'admin_sys', '$2y$10$./K3IxQKJmf94o3phKK4vObHXj0JsjnQa6vHhE/JYo0ox40jEk9x6', 1, 0, NULL),
('NV-QL01', 'Trần Quản Lý', 'Quản lý Toà nhà', '0902222222', 'quanly@hesys.com', 'quanly_01', '$2y$10$./K3IxQKJmf94o3phKK4vObHXj0JsjnQa6vHhE/JYo0ox40jEk9x6', 2, 0, NULL),
('NV-KT01', 'Lê Kế Toán', 'Kế toán trưởng', '0903333333', 'ketoan@hesys.com', 'ketoan_01', '$2y$10$./K3IxQKJmf94o3phKK4vObHXj0JsjnQa6vHhE/JYo0ox40jEk9x6', 3, 0, NULL),
('NV-LOCK', 'Phạm Bị Khóa', 'Kiểm soát nội bộ', '0904444444', 'khoa@hesys.com', 'nhanvien_lock', '$2y$10$./K3IxQKJmf94o3phKK4vObHXj0JsjnQa6vHhE/JYo0ox40jEk9x6', 2, 0, '2025-01-01 10:00:00');

-- 1.5 Tạo Khách Hàng (Các công ty/Tổ chức)
INSERT IGNORE INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) VALUES
('KH-001', 'Công ty TNHH Phần mềm Vui Vẻ', '012345678901', '0911000001', 'contact@vuive.com', '12 Nguyễn Trãi, Q5, TP.HCM'),
('KH-002', 'Công ty CP Kỹ Thuật Số Đỉnh Cao', '012345678902', '0911000002', 'info@dinhcao.vn', '34 Điện Biên Phủ, Q.Bình Thạnh, TP.HCM'),
('KH-003', 'Nguyễn Văn Nhượng (Hộ KD)', '012345678903', '0911000003', 'nhuongkd@gmail.com', '56 Châu Văn Liêm, Q5, TP.HCM');

-- 1.6 Bảng KHACH_HANG_ACCOUNT (Tài khoản login xem Bill cho Client)
INSERT IGNORE INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id) VALUES
('ACC-KH001', 'KH-001', 'vuivetech', '$2y$10$./K3IxQKJmf94o3phKK4vObHXj0JsjnQa6vHhE/JYo0ox40jEk9x6', 4),
('ACC-KH002', 'KH-002', 'dinhcaodigital', '$2y$10$./K3IxQKJmf94o3phKK4vObHXj0JsjnQa6vHhE/JYo0ox40jEk9x6', 4),
('ACC-KH003', 'KH-003', 'nhuongkd123', '$2y$10$./K3IxQKJmf94o3phKK4vObHXj0JsjnQa6vHhE/JYo0ox40jEk9x6', 4);


-- =========================================================================
-- 2. HỢP ĐỒNG & TIỀN CỌC
-- =========================================================================

-- 2.1 Bảng HOP_DONG
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES
('HD-2025-001', 'KH-001', 'NV-QL01', '2025-01-01', '2025-01-05', '2026-01-05', 100000000, 1, NULL, NULL),
('HD-2025-002', 'KH-002', 'NV-QL01', '2025-01-10', '2025-01-15', '2026-01-15', 50000000, 1, NULL, NULL),
('HD-2025-003', 'KH-003', 'NV-QL01', '2025-02-01', '2025-02-10', '2026-02-10', 40000000, 2, '2025-03-05', 'Khách hàng đồng ý mất cọc 50%'),
('HD-2024-004', 'KH-001', 'NV-QL01', '2024-05-01', '2024-05-15', '2025-05-15', 70000000, 1, NULL, NULL);

-- 2.2 Bảng CHI_TIET_HOP_DONG
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue) VALUES
('CTHD-01', 'HD-2025-001', 'P-S02-01', 42000000),
('CTHD-02', 'HD-2025-001', 'P-S02-02', 35000000),
('CTHD-03', 'HD-2025-002', 'P-R01-01', 20000000),
('CTHD-04', 'HD-2025-003', 'P-R01-02', 20000000),
('CTHD-05', 'HD-2024-004', 'P-R02-01', 35000000);

-- 2.3 Bảng TIEN_COC
INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES
('TC-01', 'HD-2025-001', 100000000, '2025-01-02', 'ChuyenKhoan', 'NV-KT01', 1),
('TC-02', 'HD-2025-002', 50000000, '2025-01-11', 'ChuyenKhoan', 'NV-KT01', 1),
('TC-03', 'HD-2025-003', 40000000, '2025-02-02', 'TienMat', 'NV-KT01', 4),
('TC-04', 'HD-2024-004', 70000000, '2024-05-02', 'ChuyenKhoan', 'NV-KT01', 1);


-- =========================================================================
-- 3. GIA HẠN HỢP ĐỒNG (Scenario)
-- =========================================================================
INSERT IGNORE INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) VALUES
('GH-001', 'HD-2024-004', '2025-04-10', 12, '2026-05-15', 'Gia hạn năm 2 cho KH-001');

INSERT IGNORE INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES
('CTGH-01', 'GH-001', 'P-R02-01', 35000000);


-- =========================================================================
-- 4. KỊCH BẢN TÀI CHÍNH (HÓA ĐƠN & PHIẾU THU)
-- =========================================================================

-- 4.1 Bảng HOA_DON (Ghi nhận nợ)
INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, loaiHoaDon, maNV, lyDo) VALUES
('INV-HD001-T1', 'HD-2025-001', 1, 2025, 77000000, 77000000, 0, 'DaThu', '01/2025', 'Chinh', 'NV-KT01', 'Tiền thuê văn phòng Tháng 1/2025'),
('INV-HD001-T2', 'HD-2025-001', 2, 2025, 77000000, 77000000, 0, 'DaThu', '02/2025', 'Chinh', 'NV-KT01', 'Tiền thuê văn phòng Tháng 2/2025'),
('INV-HD002-T1', 'HD-2025-002', 1, 2025, 20000000, 15000000, 5000000, 'DaThuMotPhan', '01/2025', 'Chinh', 'NV-KT01', 'Thu tiền T1/2025 (Còn nợ 5tr)'),
('INV-HD002-T2', 'HD-2025-002', 2, 2025, 25000000, 0, 25000000, 'ConNo', '02/2025', 'Chinh', 'NV-KT01', 'Tiền thuê T2 gộp luôn truy thu nợ T1'),
('INV-ERR-001', 'HD-2024-004', 12, 2024, 10000000, 0, 0, 'Void', '12/2024', 'Chinh', 'NV-KT01', 'Hóa đơn sai thông tin'),
('INV-CN-001',  'HD-2024-004', 12, 2024, -10000000, 0, 0, 'Void', '12/2024', 'CreditNote', 'NV-KT01', 'CreditNote đối ứng cho INV-ERR-001');

-- 4.2 Bảng PHIEU_THU
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maGiaoDich, maNV, ghiChu) VALUES
('PT-2501', '2025-01-20', 77000000, 'ChuyenKhoan', 'VCB-2391090123', 'NV-KT01', 'Đã chuyển tiền thu T1 HD-001'),
('PT-2502', '2025-02-20', 77000000, 'ChuyenKhoan', 'VCB-2391090456', 'NV-KT01', 'Đã chuyển tiền thu T2 HD-001'),
('PT-2503', '2025-01-22', 15000000, 'TienMat', NULL, 'NV-KT01', 'Khách trả TM một phần cho HD-002');

-- 4.3 Bảng PHIEU_THU_CHI_TIET
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (id, soPhieuThu, soHoaDon, soTienPhanBo) VALUES
(1001, 'PT-2501', 'INV-HD001-T1', 77000000),
(1002, 'PT-2502', 'INV-HD001-T2', 77000000),
(1003, 'PT-2503', 'INV-HD002-T1', 15000000);

-- 4.4 Bảng HOA_DON_VOID
INSERT IGNORE INTO HOA_DON_VOID (id, soPhieu, lyDoVoid, maNV_Void, ngayVoid) VALUES
(101, 'INV-ERR-001', 'Phát hiện ghi sai nước', 'NV-KT01', '2024-12-10 14:00:00');


-- =========================================================================
-- 5. ĐO LƯỜNG CHỈ SỐ ĐIỆN NƯỚC  
-- =========================================================================
INSERT IGNORE INTO CHI_SO_DIEN_NUOC (maChiSo, maPhong, thangGhi, namGhi, chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi, donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc) VALUES
('CSDN-01', 'P-S02-01', 1, 2025, 1000, 1150, 50, 60, 3500, 20000, 525000, 200000),
('CSDN-02', 'P-S02-02', 1, 2025, 2000, 2100, 40, 45, 3500, 20000, 350000, 100000);


-- =========================================================================
-- 6. RECORD DỮ LIỆU PHỤ
-- =========================================================================
INSERT IGNORE INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT, created_at) VALUES
('MR-2025-001', 'P-S02-01', 'Máy lạnh nhánh A chảy nước', 'KH-001', 1, 3, '2025-02-15 08:00:00'),
('MR-2025-002', 'P-R01-01', 'Đèn tuýt LED nhấp nháy', 'KH-002', 2, 1, '2025-01-20 09:00:00');

INSERT IGNORE INTO MAINTENANCE_STATUS_LOG (id, request_id, trangThaiCu, trangThaiMoi, nguoiCapNhat, created_at) VALUES
(201, 'MR-2025-001', 0, 1, 'NV-QL01', '2025-02-15 08:30:00'),
(202, 'MR-2025-002', 0, 2, 'NV-QL01', '2025-01-21 14:00:00');

INSERT IGNORE INTO AUDIT_LOG (id, maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, ipAddress, thoiGian) VALUES
(301, 'NV-AD01', 'UPDATE', 'HOP_DONG', 'HD-2025-003', 'Huy hop dong', '192.168.1.100', '2025-03-05 10:00:00'),
(302, 'NV-AD01', 'UPDATE', 'TIEN_COC', 'TC-03', 'Sang Cho Xu Ly (4)', '192.168.1.100', '2025-03-05 10:01:00');

INSERT IGNORE INTO LOGIN_ATTEMPT (id, username, ip_address, attempt_time, status) VALUES
(401, 'nhanvien_lock', '118.69.231.12', '2025-01-01 09:55:00', 0),
(402, 'nhanvien_lock', '118.69.231.12', '2025-01-01 09:56:00', 0),
(403, 'nhanvien_lock', '118.69.231.12', '2025-01-01 09:57:00', 0),
(404, 'nhanvien_lock', '118.69.231.12', '2025-01-01 09:58:00', 0),
(405, 'nhanvien_lock', '118.69.231.12', '2025-01-01 09:59:00', 0);

-- Mở lại Check Binding Tự Động
SET FOREIGN_KEY_CHECKS = 1;
