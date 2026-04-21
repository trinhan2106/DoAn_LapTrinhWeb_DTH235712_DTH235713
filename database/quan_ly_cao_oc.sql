-- ==========================================================
-- SCRIPT TẠO DATABASE: HỆ THỐNG QUẢN LÝ VẬN HÀNH CHO THUÊ CAO ỐC
-- ==========================================================
CREATE DATABASE IF NOT EXISTS quan_ly_cao_oc 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE quan_ly_cao_oc;

-- ==========================================================
-- PHẦN 1: CÁC BẢNG CỐT LÕI (11 BẢNG)
-- ==========================================================

-- 1. Bảng CAO_OC
CREATE TABLE CAO_OC (
    maCaoOc VARCHAR(20) PRIMARY KEY,
    tenCaoOc VARCHAR(255) NOT NULL,
    diaChi TEXT,
    soTang INT DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng TANG
CREATE TABLE TANG (
    maTang VARCHAR(20) PRIMARY KEY,
    maCaoOc VARCHAR(20) NOT NULL,
    tenTang VARCHAR(100) NOT NULL,
    heSoGia DECIMAL(4,2) DEFAULT 1.00,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_tang_cao_oc FOREIGN KEY (maCaoOc) REFERENCES CAO_OC(maCaoOc) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng PHONG
CREATE TABLE PHONG (
    maPhong VARCHAR(50) PRIMARY KEY,
    maTang VARCHAR(20) NOT NULL,
    tenPhong VARCHAR(100) NOT NULL,
    loaiPhong VARCHAR(50),
    dienTich DECIMAL(10,2),
    soChoLamViec INT DEFAULT 0,
    moTaViTri VARCHAR(200) NULL,
    donGiaM2 DECIMAL(15,2) DEFAULT 0.00,
    giaThue DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    trangThai TINYINT DEFAULT 1 COMMENT '1: Trong, 2: Da thue, 3: Dang sua chua, 4: Lock',
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_phong_tang FOREIGN KEY (maTang) REFERENCES TANG(maTang) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm FULLTEXT INDEX cho tìm kiếm phòng (MySQL 5.6+ InnoDB hỗ trợ Fulltext)
ALTER TABLE PHONG ADD FULLTEXT INDEX ft_phong_search(maPhong, tenPhong);
ALTER TABLE PHONG ADD FULLTEXT INDEX ft_phong_mota(moTaViTri);


-- 4. Bảng KHACH_HANG
CREATE TABLE KHACH_HANG (
    maKH VARCHAR(20) PRIMARY KEY,
    tenKH VARCHAR(100) NOT NULL,
    cccd VARCHAR(20) UNIQUE,
    sdt VARCHAR(15),
    email VARCHAR(100),
    diaChi TEXT,
    deleted_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm FULLTEXT INDEX cho tên Khách Hàng
ALTER TABLE KHACH_HANG ADD FULLTEXT INDEX ft_khach_hang_search(tenKH);


-- 5. Bảng NHAN_VIEN (Đã được bổ sung vào cốt lõi để làm rễ của các liên kết)
CREATE TABLE NHAN_VIEN (
    maNV VARCHAR(20) PRIMARY KEY,
    tenNV VARCHAR(100) NOT NULL,
    chucVu VARCHAR(50),
    sdt VARCHAR(15),
    email VARCHAR(100),
    username VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    role_id INT COMMENT '1: Admin, 2: QLN, 3: Ke Toan',
    phai_doi_matkhau BOOLEAN DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. Bảng HOP_DONG
CREATE TABLE HOP_DONG (
    soHopDong VARCHAR(50) PRIMARY KEY,
    maKH VARCHAR(20) NOT NULL,
    maNV VARCHAR(20),
    ngayLap DATE,
    ngayBatDau DATE,
    ngayThanhToanDauTien DATE NULL,
    ngayHetHanCuoiCung DATE NULL,
    ngayKetThuc DATE,
    tienTienCoc DECIMAL(15,2) DEFAULT 0.00,
    trangThai TINYINT DEFAULT 1 COMMENT '1: Hieu luc, 0: Ket thuc, 2: Huy, 3: ChoDuyet',
    ngayHuy DATE NULL,
    lyDoHuy TEXT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_hop_dong_kh FOREIGN KEY (maKH) REFERENCES KHACH_HANG(maKH),
    CONSTRAINT fk_hop_dong_nv FOREIGN KEY (maNV) REFERENCES NHAN_VIEN(maNV)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm FULLTEXT INDEX cho số Hợp Đồng
ALTER TABLE HOP_DONG ADD FULLTEXT INDEX ft_hop_dong_search(soHopDong);


-- 7. Bảng CHI_TIET_HOP_DONG
CREATE TABLE CHI_TIET_HOP_DONG (
    maCTHD VARCHAR(50) PRIMARY KEY,
    soHopDong VARCHAR(50) NOT NULL,
    maPhong VARCHAR(50) NOT NULL,
    giaThue DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    ngayBatDau DATE NULL,
    ngayHetHan DATE NULL,
    trangThai VARCHAR(20) DEFAULT 'DangThue' COMMENT 'DangThue | DaKetThuc',
    CONSTRAINT fk_cthd_hd FOREIGN KEY (soHopDong) REFERENCES HOP_DONG(soHopDong) ON DELETE CASCADE,
    CONSTRAINT fk_cthd_phong FOREIGN KEY (maPhong) REFERENCES PHONG(maPhong)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 8. Bảng GIA_HAN_HOP_DONG
CREATE TABLE GIA_HAN_HOP_DONG (
    maGiaHan VARCHAR(50) PRIMARY KEY,
    soHopDong VARCHAR(50) NOT NULL,
    ngayGiaHan DATE,
    soThangGiaHan INT NOT NULL,
    ngayKetThucMoi DATE,
    ghiChu TEXT,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_ghhd_hd FOREIGN KEY (soHopDong) REFERENCES HOP_DONG(soHopDong) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 9. Bảng CHI_TIET_GIA_HAN
CREATE TABLE CHI_TIET_GIA_HAN (
    maCTGH VARCHAR(50) PRIMARY KEY,
    maGiaHan VARCHAR(50) NOT NULL,
    maPhong VARCHAR(50) NOT NULL,
    giaThueMoi DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_ctgh_giahan FOREIGN KEY (maGiaHan) REFERENCES GIA_HAN_HOP_DONG(maGiaHan) ON DELETE CASCADE,
    CONSTRAINT fk_ctgh_phong FOREIGN KEY (maPhong) REFERENCES PHONG(maPhong)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 10. Bảng HOA_DON
CREATE TABLE HOA_DON (
    soHoaDon VARCHAR(50) PRIMARY KEY,
    soHopDong VARCHAR(50) NOT NULL,
    thang INT NOT NULL,
    nam INT NOT NULL,
    tongTien DECIMAL(15,2) DEFAULT 0.00,
    soTienDaNop DECIMAL(15,2) DEFAULT 0.00,
    soTienConNo DECIMAL(15,2) DEFAULT 0.00,
    trangThai VARCHAR(20) NOT NULL DEFAULT 'ConNo' COMMENT 'ConNo | DaThuMotPhan | DaThu | Void | Huy',
    kyThanhToan VARCHAR(20) NOT NULL COMMENT 'Dinh dang MM/YYYY',
    lyDo TEXT NULL,
    maNV VARCHAR(50) NULL,
    loaiHoaDon VARCHAR(20) DEFAULT 'Chinh' COMMENT 'Chinh | CreditNote',
    ngayLap DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_hoa_don_hd FOREIGN KEY (soHopDong) REFERENCES HOP_DONG(soHopDong),
    CONSTRAINT fk_hd_nv FOREIGN KEY (maNV) REFERENCES NHAN_VIEN(maNV)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 11. Bảng CHI_SO_DIEN_NUOC
CREATE TABLE CHI_SO_DIEN_NUOC (
    maChiSo VARCHAR(50) PRIMARY KEY,
    maPhong VARCHAR(50) NOT NULL,
    thangGhi INT NOT NULL,
    namGhi INT NOT NULL,
    chiSoDienCu DECIMAL(10,2) DEFAULT 0,
    chiSoDienMoi DECIMAL(10,2) DEFAULT 0,
    chiSoNuocCu DECIMAL(10,2) DEFAULT 0,
    chiSoNuocMoi DECIMAL(10,2) DEFAULT 0,
    donGiaDien DECIMAL(15,2) DEFAULT 0.00,
    donGiaNuoc DECIMAL(15,2) DEFAULT 0.00,
    thanhTienDien DECIMAL(15,2) DEFAULT 0.00,
    thanhTienNuoc DECIMAL(15,2) DEFAULT 0.00,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_csdn_phong FOREIGN KEY (maPhong) REFERENCES PHONG(maPhong),
    -- Yêu cầu bắt buộc: Ràng buộc UNIQUE cho (maPhong, tháng, năm)
    CONSTRAINT uk_chiso_phong_thang_nam UNIQUE (maPhong, thangGhi, namGhi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- PHẦN 2: CÁC BẢNG BỔ SUNG CHO WEB (15 BẢNG)
-- ==========================================================

-- 12. Bảng PHONG_HINH_ANH
CREATE TABLE PHONG_HINH_ANH (
    id VARCHAR(50) PRIMARY KEY,
    maPhong VARCHAR(50) NOT NULL,
    urlHinhAnh VARCHAR(255) NOT NULL,
    is_thumbnail BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_hinh_anh_phong FOREIGN KEY (maPhong) REFERENCES PHONG(maPhong) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 13. Bảng PHONG_LOCK (Lock phòng tạm thời trong 10 phút — FIX-07: thêm UNIQUE maPhong cho atomic upsert)
CREATE TABLE PHONG_LOCK (
    id VARCHAR(50) PRIMARY KEY,
    maPhong VARCHAR(50) NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    locked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expire_at DATETIME NOT NULL COMMENT 'Thoi gian giu phong tam ket thuc',
    CONSTRAINT uk_phong_lock UNIQUE (maPhong),
    CONSTRAINT fk_lock_phong FOREIGN KEY (maPhong) REFERENCES PHONG(maPhong) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 14. Bảng YEU_CAU_THUE
CREATE TABLE YEU_CAU_THUE (
    maYeuCau VARCHAR(50) PRIMARY KEY,
    maPhong VARCHAR(50) NOT NULL,
    hoTen VARCHAR(100) NOT NULL,
    sdt VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    ngayYeuCau DATETIME DEFAULT CURRENT_TIMESTAMP,
    trangThai TINYINT DEFAULT 0 COMMENT '0: Cho duyet, 1: Da lien he, 2: Huy',
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_yeucauthue_phong FOREIGN KEY (maPhong) REFERENCES PHONG(maPhong)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 15. Bảng YEU_CAU_GIA_HAN
CREATE TABLE YEU_CAU_GIA_HAN (
    maYeuCauGH VARCHAR(50) PRIMARY KEY,
    soHopDong VARCHAR(50) NOT NULL,
    thoiGianYeuCau DATETIME DEFAULT CURRENT_TIMESTAMP,
    soThangDeXuat INT NOT NULL,
    lyDo TEXT,
    trangThai TINYINT DEFAULT 0 COMMENT '0: Cho duyet, 1: Dong y, 2: Tu choi',
    CONSTRAINT fk_yeucaugh_hd FOREIGN KEY (soHopDong) REFERENCES HOP_DONG(soHopDong)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 16. Bảng TIEN_COC
CREATE TABLE TIEN_COC (
    maTienCoc VARCHAR(50) PRIMARY KEY,
    soHopDong VARCHAR(50) NOT NULL,
    soTien DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    ngayNop DATETIME DEFAULT CURRENT_TIMESTAMP,
    phuongThuc VARCHAR(50),
    nguoiThu VARCHAR(20),
    trangThai TINYINT DEFAULT 1 COMMENT '1: Da thu, 2: Da hoan, 3: Tich thu, 4: ChoXuLy (HD da huy)',
    CONSTRAINT fk_tiencoc_hd FOREIGN KEY (soHopDong) REFERENCES HOP_DONG(soHopDong),
    CONSTRAINT fk_tiencoc_nv FOREIGN KEY (nguoiThu) REFERENCES NHAN_VIEN(maNV)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 17. Bảng HOA_DON_VOID (Hủy Hóa đơn)
CREATE TABLE HOA_DON_VOID (
id INT AUTO_INCREMENT PRIMARY KEY,
soPhieu VARCHAR(50) NOT NULL,
lyDoVoid TEXT NOT NULL,
maNV_Void VARCHAR(50) NOT NULL,
ngayVoid DATETIME DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_hdvoid_hd FOREIGN KEY (soPhieu) REFERENCES HOA_DON(soHoaDon) ON DELETE CASCADE,
CONSTRAINT fk_hdvoid_nv FOREIGN KEY (maNV_Void) REFERENCES NHAN_VIEN(maNV) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 18. Bảng KHACH_HANG_ACCOUNT (Tài khoản login của khách thuê để xem Bill)
CREATE TABLE KHACH_HANG_ACCOUNT (
    accountId VARCHAR(50) PRIMARY KEY,
    maKH VARCHAR(20) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT DEFAULT 4 COMMENT '4 tương đương ROLE_KHACH_HANG',
    phai_doi_matkhau BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_account_kh FOREIGN KEY (maKH) REFERENCES KHACH_HANG(maKH) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 19. Bảng LOGIN_ATTEMPT (Theo dõi chống Brute Force)
CREATE TABLE LOGIN_ATTEMPT (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100),
    ip_address VARCHAR(45),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TINYINT COMMENT '1: Thanh cong, 0: That bai'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 20. Bảng TRANH_CHAP_HOA_DON (Giải quyết khiếu nại/Kiểm tra lại hóa đơn từ khách thuê)
DROP TABLE IF EXISTS TRANH_CHAP_HOA_DON;
CREATE TABLE TRANH_CHAP_HOA_DON (
    id VARCHAR(50) PRIMARY KEY,
    maHoaDon VARCHAR(50) NOT NULL,
    noiDung TEXT NOT NULL,
    trangThai TINYINT DEFAULT 0 COMMENT '0: Moi tao, 1: Dang xu ly, 2: Hoan thanh, 3: Da bac bo',
    phanHoi TEXT NULL,
    ngayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tranh_chap_hd FOREIGN KEY (maHoaDon) REFERENCES HOA_DON(soHoaDon) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 21. Bảng THONG_BAO (Thông báo đẩy trên web hoặc qua mail)
CREATE TABLE THONG_BAO (
    maThongBao VARCHAR(50) PRIMARY KEY,
    tieuDe VARCHAR(255) NOT NULL,
    noiDung TEXT NOT NULL,
    ngayGui DATETIME DEFAULT CURRENT_TIMESTAMP,
    nguoiNhan VARCHAR(20),
    loaiThongBao VARCHAR(50),
    daDoc BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_thongbao_kh FOREIGN KEY (nguoiNhan) REFERENCES KHACH_HANG(maKH)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 22. Bảng AUDIT_LOG (Ghi sự kiện Thay đổi/Xoá Dữ Liệu nhạy cảm — FIX-20: thêm ipAddress)
CREATE TABLE AUDIT_LOG (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maNguoiDung VARCHAR(50),
    hanhDong VARCHAR(50) NOT NULL,
    bangBiTacDong VARCHAR(50) NOT NULL,
    recordId VARCHAR(50),
    chiTiet TEXT,
    ipAddress VARCHAR(45) NULL COMMENT 'IP client khi thuc hien hanh dong',
    thoiGian DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 23. Bảng ACTIVITY_LOG (Nhật ký truy cập hệ thống của nhân sự)
CREATE TABLE ACTIVITY_LOG (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    ip_address VARCHAR(45),
    action VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 24. Bảng CAU_HINH_HE_THONG
CREATE TABLE CAU_HINH_HE_THONG (
    key_name VARCHAR(100) PRIMARY KEY,
    key_value TEXT,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 25. Bảng MAINTENANCE_REQUEST (Yêu cầu bảo trì kỹ thuật)
CREATE TABLE MAINTENANCE_REQUEST (
    id VARCHAR(50) PRIMARY KEY,
    maPhong VARCHAR(50) NOT NULL,
    moTa TEXT NOT NULL,
    nguoiYeuCau VARCHAR(20),
    trangThai TINYINT DEFAULT 0 COMMENT '0: Cho tiep nhan, 1: Dang xu ly, 2: Hoan thanh, 3: Huy',
    mucDoUT TINYINT DEFAULT 1 COMMENT '1: Thap, 2: Trung Binh, 3: Cao, 4: Khan cap',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_maintenance_phong FOREIGN KEY (maPhong) REFERENCES PHONG(maPhong)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 26. Bảng MAINTENANCE_STATUS_LOG (Track tiến độ xử lý bảo trì)
CREATE TABLE MAINTENANCE_STATUS_LOG (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(50) NOT NULL,
    trangThaiCu TINYINT,
    trangThaiMoi TINYINT NOT NULL,
    nguoiCapNhat VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenacne_log_req FOREIGN KEY (request_id) REFERENCES MAINTENANCE_REQUEST(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 26.5 Bảng LIEN_HE (Lưu thông tin khách hàng vãng lai liên hệ)
CREATE TABLE IF NOT EXISTS LIEN_HE (
    maLienHe INT AUTO_INCREMENT PRIMARY KEY,
    hoTen VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    soDienThoai VARCHAR(20),
    noiDung TEXT,
    ngayGui DATETIME DEFAULT CURRENT_TIMESTAMP,
    trangThai TINYINT DEFAULT 0 COMMENT '0: Chưa xử lý, 1: Đã xử lý'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Bảng PHIEU_THU (Ledger giao dịch thanh toán)
CREATE TABLE PHIEU_THU (
    soPhieuThu VARCHAR(50) PRIMARY KEY,
    ngayThu DATETIME DEFAULT CURRENT_TIMESTAMP,
    tongTienThu DECIMAL(15,2) NOT NULL,
    phuongThuc VARCHAR(30) NOT NULL COMMENT 'TienMat | ChuyenKhoan | Vi',
    maGiaoDich VARCHAR(100) NULL,
    maNV VARCHAR(50) NOT NULL,
    ghiChu TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pt_nv FOREIGN KEY (maNV) REFERENCES NHAN_VIEN(maNV)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 28. Bảng PHIEU_THU_CHI_TIET (Mapping waterfall phân bổ tiền vào hóa đơn)
CREATE TABLE PHIEU_THU_CHI_TIET (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soPhieuThu VARCHAR(50) NOT NULL,
    soHoaDon VARCHAR(50) NOT NULL,
    soTienPhanBo DECIMAL(15,2) NOT NULL,
    CONSTRAINT fk_ptct_pt FOREIGN KEY (soPhieuThu) REFERENCES PHIEU_THU(soPhieuThu),
    CONSTRAINT fk_ptct_hd FOREIGN KEY (soHoaDon) REFERENCES HOA_DON(soHoaDon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





-- ==========================================================


-- PHẦN 3: DỮ LIỆU KHỞI TẠO (SEED DATA)

-- === SEED DEMO CŨ ===
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
TRUNCATE TABLE LIEN_HE;
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
('A02-201', 'T-A02', 'Văn Phòng Hạng A — Suite 201', 'Văn phòng hạng A', 150.00, 35,
 'View nhìn thẳng ra sông Sài Gòn, nội thất cao cấp sẵn', 350000, 52500000, 2),     -- Đang thuê
('A02-202', 'T-A02', 'Văn Phòng Hạng A — Suite 202', 'Văn phòng hạng A', 120.00, 28,
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
('B02-201', 'T-B02', 'Văn Phòng Hạng C — B201',   'Văn phòng hạng C', 80.00, 18,
 'Bố trí gọn gàng, phù hợp văn phòng 15-20 nhân sự', 280000, 22400000, 1),          -- Trống
('B02-202', 'T-B02', 'Văn Phòng Hạng B — B202',   'Văn phòng hạng B', 100.00, 22,
 'Đã lắp sẵn vách ngăn và kệ lưu trữ', 280000, 28000000, 2),                        -- Đang thuê
('B02-203', 'T-B02', 'Văn Phòng Hạng C — B203',   'Văn phòng hạng C', 75.00, 16,
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
('C10-1001', 'T-C03', 'Penthouse Corporate 1001',    'Văn phòng hạng A', 350.00, 80,
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
('NV-01', 'Trần Trí Nhân',   'Quản trị viên Hệ thống',    '0901111111', 'nhantran@meridian.vn',    'admin',    '$2y$10$iIK1JeV47U3vI7dfxs8R6uG8BC/oEtafVvz/Kg3A87mhCHirRcK/u', 1, 0, NULL),
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
INSERT INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayThanhToanDauTien, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES
-- HD-2025-001: KH-01 (VietSoft) thuê Suite 201+202 Khối A Tầng 2 — Đang hiệu lực
('HD-2025-001', 'KH-01', 'NV-02', '2025-01-03', '2025-01-10', '2025-01-15', '2026-01-10', '2026-01-10', 94500000.00, 1, NULL, NULL),
-- HD-2025-002: KH-02 (Phú Gia Hưng) thuê Suite 301 Khối A Tầng 3 — Đang hiệu lực
('HD-2025-002', 'KH-02', 'NV-02', '2025-02-01', '2025-02-05', '2025-02-10', '2026-02-05', '2026-02-05', 75600000.00, 1, NULL, NULL),
-- HD-2025-003: KH-05 (MediaVN) thuê Executive Suite 801 — Đang hiệu lực
('HD-2025-003', 'KH-05', 'NV-03', '2025-03-01', '2025-03-10', '2025-03-15', '2026-03-10', '2026-03-10', 90000000.00, 1, NULL, NULL),
-- HD-2025-004: KH-06 (SkyLab) thuê các phòng kinh doanh Khối A + Ẩm thực Khối B — CHỜ DUYỆT
('HD-2025-004', 'KH-06', 'NV-02', '2025-04-15', '2025-05-01', '2025-05-05', '2026-05-01', '2026-05-01', 49000000.00, 3, NULL, NULL),
-- HD-2024-005: KH-03 (Ngon & Lành) thuê gian ẩm thực — ĐÃ HỦY (Trả phòng sớm)
('HD-2024-005', 'KH-03', 'NV-03', '2024-06-01', '2024-06-15', '2024-06-20', '2025-06-15', '2025-06-15', 49000000.00, 2, '2024-11-20', 'Khách hàng thu hẹp quy mô hoạt động, đóng cửa nhà hàng trước hạn. Đã thỏa thuận bồi thường 2 tháng tiền thuê.');


-- =========================================================================
-- PHẦN 6: CHI TIẾT HỢP ĐỒNG — Mapping phòng vào HĐ (Đã bổ sung Ngày & Trạng thái)
-- =========================================================================
INSERT INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES
-- HD-2025-001: VietSoft thuê 2 phòng (Đang thuê)
('CTHD-001', 'HD-2025-001', 'A02-201', 52500000.00, '2025-01-10', '2026-01-10', 'DangThue'),
('CTHD-002', 'HD-2025-001', 'A02-202', 42000000.00, '2025-01-10', '2026-01-10', 'DangThue'),

-- HD-2025-002: Phú Gia Hưng thuê A03-301 (Đang thuê)
('CTHD-003', 'HD-2025-002', 'A03-301', 75600000.00, '2025-02-05', '2026-02-05', 'DangThue'),

-- HD-2025-003: MediaVN thuê Executive C08-801 (Đang thuê)
('CTHD-004', 'HD-2025-003', 'C08-801', 90000000.00, '2025-03-10', '2026-03-10', 'DangThue'),

-- HD-2025-004 (Chờ duyệt): SkyLab (Ước tính ngày thuê tương lai)
('CTHD-005', 'HD-2025-004', 'A01-103', 38000000.00, '2025-05-01', '2026-05-01', 'DangThue'),
('CTHD-006', 'HD-2025-004', 'B01-102', 49000000.00, '2025-05-01', '2026-05-01', 'DangThue'),

-- HD-2024-005 (Đã hủy): Ngon & Lành (Đã kết thúc)
('CTHD-007', 'HD-2024-005', 'B01-102', 49000000.00, '2024-06-15', '2025-06-15', 'DaKetThuc');


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


-- === CHUỖI MỞ RỘNG ĐỒ SỘ (GOLDEN SEED 6 BẢN) ===
-- =========================================================================
-- GOLDEN SEED DATA SCRIPT (THEO MỐC THÁNG 04/2026)
-- HỖ TRỢ TEST 8 KỊCH BẢN VẬN HÀNH BUILDING MANAGEMENT SYSTEM
-- =========================================================================

-- Tắt kiểm tra khóa ngoại tạm thời để Reset (nếu cần) nhưng các lệnh INSERT dưới đây đã được xếp ĐÚNG THỨ TỰ.
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu cũ (Tùy chọn, uncomment nếu muốn clean db trước khi chèn)
-- TRUNCATE TABLE PHIEU_THU_CHI_TIET;
-- TRUNCATE TABLE PHIEU_THU;
-- TRUNCATE TABLE HOA_DON_VOID;
-- TRUNCATE TABLE HOA_DON;
-- TRUNCATE TABLE TIEN_COC;
-- ... vv

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. CƠ SỞ VẬT CHẤT (CAO ỐC, TẦNG, PHÒNG)
-- =========================================================================
INSERT IGNORE INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES 
('BLSK', 'THE SAPPHIRE', '123 Cách Mạng Tháng 8, Quận 10', 10),
('GRLN', 'Green Land Building', '456 Võ Văn Kiệt, Quận 1', 15);

INSERT IGNORE INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES 
('BLSK-T1', 'BLSK', 'Tầng 1 (Bán lẻ)', 1.50),
('BLSK-T2', 'BLSK', 'Tầng 2 (Văn phòng)', 1.20),
('BLSK-T3', 'BLSK', 'Tầng 3 (Văn phòng VIP)', 1.50),
('GRLN-T1', 'GRLN', 'Tầng 1 (Sảnh Thương mại)', 2.00),
('GRLN-T2', 'GRLN', 'Tầng 2 (Coworking)', 1.00);

-- Sinh 20 Phòng đa dạng (Trong, DangThue, DangSuaChua)
-- Giá thuê = donGiaM2 * dienTich * heSoGia (Giả định)
INSERT IGNORE INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai) VALUES 
('P101', 'BLSK-T1', 'Cửa hàng tiện lợi 1', 'ThuongMai', 50.00, 10, 500000, 25000000, 2), -- Đang thuê (Happy Case 1)
('P102', 'BLSK-T1', 'Cửa hàng mặt tiền', 'ThuongMai', 100.00, 20, 600000, 60000000, 2), -- Đang thuê (Happy Case 1)
('P103', 'BLSK-T2', 'Văn phòng 1', 'VanPhong', 80.00, 15, 300000, 24000000, 1), -- Trước đó chờ duyệt, chuyển thành Trống/Lock (TC2)
('P104', 'BLSK-T2', 'Văn phòng 2', 'VanPhong', 80.00, 15, 300000, 24000000, 2), -- Đang thuê (Gia hạn lẻ)
('P105', 'BLSK-T2', 'Văn phòng 3 VIP', 'VanPhong', 100.00, 20, 350000, 35000000, 2), -- Đang thuê (Gia hạn lẻ)
('P106', 'BLSK-T3', 'Văn phòng 4', 'VanPhong', 60.00, 10, 300000, 18000000, 1), -- Đã trả phòng về Trống (Kết thúc lẻ)
('P107', 'BLSK-T3', 'Văn phòng 5', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P108', 'BLSK-T3', 'Văn phòng 6', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P109', 'BLSK-T3', 'Văn phòng 7', 'VanPhong', 120.00, 25, 300000, 36000000, 1), -- Hợp đồng đã Hủy, trả về Trống
('P110', 'BLSK-T3', 'Văn phòng 8', 'VanPhong', 80.00, 15, 300000, 24000000, 3), -- Đang sửa chữa
('R201', 'GRLN-T1', 'Mặt Bằng 1', 'ThuongMai', 200.00, 40, 800000, 160000000, 2), -- Đang thuê (Happy Case 2)
('R202', 'GRLN-T1', 'Mặt Bằng 2', 'ThuongMai', 150.00, 30, 800000, 120000000, 2), -- Đang thuê (Sắp hết hạn)
('R203', 'GRLN-T2', 'Coworking A', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R204', 'GRLN-T2', 'Coworking B', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R205', 'GRLN-T2', 'Coworking C', 'DichVu', 50.00, 15, 400000, 20000000, 3), -- Đang sửa chữa
('R206', 'GRLN-T2', 'Coworking D', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R207', 'GRLN-T2', 'Coworking E', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R208', 'GRLN-T2', 'Coworking F', 'DichVu', 100.00, 30, 400000, 40000000, 2), -- Gắn cho Waterfall HD008
('R209', 'GRLN-T2', 'Coworking G', 'DichVu', 100.00, 30, 400000, 40000000, 1),
('R210', 'GRLN-T2', 'Coworking VIP', 'DichVu', 150.00, 45, 500000, 75000000, 1);

-- =========================================================================
-- 2. NHÂN SỰ & QUẢN LÝ (ROLE & TÀI KHOẢN) - MK: '123456'
-- =========================================================================
-- Chú ý: hash '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a' là 123456
INSERT IGNORE INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau) VALUES 
('NV-ADMIN2', 'Trần Văn Admin', 'Giám Đốc HT', '0900000001', 'admin_sys@bluesky.com', 'admin', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 1, 0),
('NV-QLN', 'Lê Thị Quản Lý', 'Quản Lý Nhà', '0900000002', 'qln@bluesky.com', 'quanlynha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 2, 0),
('NV-KT1', 'Phạm Kế Toán', 'Kế Toán Viên', '0900000003', 'kt1@bluesky.com', 'ketoan1', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0),
('NV-KT2', 'Vũ Trưởng Toán', 'Kế Toán Trưởng', '0900000004', 'kt2@bluesky.com', 'ketoan2', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0);

-- =========================================================================
-- 3. KHÁCH HÀNG & TÀI KHOẢN KHÁCH (KHACH_HANG_ACCOUNT)
-- =========================================================================
INSERT IGNORE INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) VALUES 
('KH01', 'CTY TNHH HAPPY C', '079200000001', '0911000001', 'happy_c@gmail.com', 'Quận 1, TP.HCM'),
('KH02', 'CTY CỔ PHẦN ALPHA', '079200000002', '0911000002', 'alpha_cp@gmail.com', 'Quận 3, TP.HCM'),
('KH03', 'Ông Hoàng Văn Chờ', '079200000003', '0911000003', 'cho_duyet@gmail.com', 'Quận 5, TP.HCM'),
('KH04', 'Bà Nguyễn Thị Hạn', '079200000004', '0911000004', 'han_cuoi@gmail.com', 'Quận 10, TP.HCM'),
('KH05', 'CTY TNHH GIA HẠN LẺ', '079200000005', '0911000005', 'ext_le@gmail.com', 'Bình Thạnh, TP.HCM'),
('KH06', 'CTY CỔ PHẦN RÚT LUI', '079200000006', '0911000006', 'rutlui_1phan@gmail.com', 'Gò Vấp, TP.HCM'),
('KH07', 'Ông Trần Hủy Kèo', '079200000007', '0911000007', 'huy_keo@gmail.com', 'Tân Bình, TP.HCM'),
('KH08', 'CTY TNHH WATERFALL', '079200000008', '0911000008', 'waterfall_no@gmail.com', 'Phú Nhuận, TP.HCM'),
('KH09', 'Ông Lý Hóa Đơn Lỗi', '079200000009', '0911000009', 'void_bill@gmail.com', 'Quận 2, TP.HCM'),
('KH10', 'Bà Lê Vãng Lai', '079200000010', '0911000010', 'vanglai@gmail.com', 'Quận 9, TP.HCM');

-- Cấp account cho 3 khách hàng (MK: 123456)
INSERT IGNORE INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id) VALUES 
('ACC-KH01', 'KH01', 'kh01_happy', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH02', 'KH02', 'kh02_alpha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH08', 'KH08', 'kh08_water', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4);

-- =========================================================================
-- 4. CẤU HÌNH HỆ THỐNG
-- =========================================================================
INSERT IGNORE INTO CAU_HINH_HE_THONG (key_name, key_value, description) VALUES 
('LOCK_PHONG_PHUT', '10', 'Thời gian đóng băng khóa phòng (Phút)'),
('THUE_TOI_THIEU', '6', 'Số tháng thuê tối thiểu'),
('VAT_PERCENT', '10', 'Phần trăm thuế VAT theo quy định');

-- =========================================================================
-- 5. KỊCH BẢN HỢP ĐỒNG (HOP_DONG, CHI_TIET_HOP_DONG, TIEN_COC)
-- =========================================================================

-- Kịch bản 1 (Happy Case): 2 Hợp đồng hiệu lực
-- HD001: KH01 thuê P101, P102 (Từ 01/01/2026 - 31/12/2026).
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD001', 'KH01', 'NV-QLN', '2025-12-25', '2026-01-01', '2026-12-31', '2026-12-31', 100000000, 1),
('HD002', 'KH02', 'NV-QLN', '2026-01-15', '2026-02-01', '2027-02-01', '2027-02-01', 50000000, 1);

INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-001', 'HD001', 'P101', 25000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-002', 'HD001', 'P102', 60000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-003', 'HD002', 'R201', 160000000, '2026-02-01', '2027-02-01', 'DangThue');

INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-001', 'HD001', 100000000, '2025-12-25', 'ChuyenKhoan', 'NV-QLN', 1),
('COC-002', 'HD002', 50000000, '2026-01-15', 'TienMat', 'NV-QLN', 1);


-- Kịch bản 2 (Cho Duyệt): Chưa ký, cờ 3
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD003', 'KH03', 'NV-QLN', '2026-04-10', '2026-05-01', '2027-05-01', '2027-05-01', 20000000, 3);
-- Chưa chèn CTHD vì chưa duyệt xong


-- Kịch bản 3 (Sắp hết hạn): Tháng 05/2026 hết hạn
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD004', 'KH04', 'NV-QLN', '2025-05-01', '2025-05-05', '2026-05-05', '2026-05-05', 40000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-004', 'HD004', 'R202', 120000000, '2025-05-05', '2026-05-05', 'DangThue');


-- Kịch bản 4 (Gia hạn lẻ): Hợp đồng HD005 có P104 (Hết hạn 06/2026), P105 (Gia hạn thêm 3 tháng tới 09/2026)
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD005', 'KH05', 'NV-QLN', '2025-06-01', '2025-06-01', '2026-09-01', '2026-06-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-005', 'HD005', 'P104', 24000000, '2025-06-01', '2026-06-01', 'DangThue'),
('CTHD-006', 'HD005', 'P105', 35000000, '2025-06-01', '2026-09-01', 'DangThue');

INSERT IGNORE INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) VALUES 
('GH-001', 'HD005', '2026-04-05', 3, '2026-09-01', 'Khách muốn gia hạn riêng P105 thêm 3 tháng');
INSERT IGNORE INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES 
('CTGH-001', 'GH-001', 'P105', 35000000);


-- Kịch bản 5 (Kết thúc lẻ): Hợp đồng HD006 thuê P106, P107, P108. P106 đã kết thúc sớm.
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD006', 'KH06', 'NV-QLN', '2025-08-01', '2025-08-01', '2026-08-01', '2026-08-01', 80000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-007', 'HD006', 'P106', 18000000, '2025-08-01', '2026-04-15', 'DaKetThuc'),
('CTHD-008', 'HD006', 'P107', 18000000, '2025-08-01', '2026-08-01', 'DangThue'),
('CTHD-009', 'HD006', 'P108', 18000000, '2025-08-01', '2026-08-01', 'DangThue');


-- Kịch bản 6 (Đã Hủy): Hợp đồng HD007 bị hủy giữa chừng
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES 
('HD007', 'KH07', 'NV-QLN', '2025-10-01', '2025-10-01', '2026-10-01', '2026-10-01', 30000000, 2, '2026-03-01', 'Công ty phá sản, thanh lý sớm');
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-010', 'HD007', 'P109', 36000000, '2025-10-01', '2026-03-01', 'DaKetThuc');
INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-007', 'HD007', 30000000, '2025-10-01', 'TienMat', 'NV-QLN', 4); -- 4: ChoXuLy


-- =========================================================================
-- 6. THANH TOÁN (Waterfall & Void Kịch Bản)
-- =========================================================================

-- Sinh Hóa Đơn đàng hoàng cho HD001, Tháng 03/2026 (Đã thu)
INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD001-0326', 'HD001', 3, 2026, 85000000, 85000000, 0, 'DaThu', '03/2026', 'NV-KT1', '2026-03-01 08:00:00');
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-001', '2026-03-05', 85000000, 'ChuyenKhoan', 'NV-KT1', 'Thu tiền HĐ001 tháng 3/2026');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-001', 'BILL-HD001-0326', 85000000);


-- Waterfall (HD008): Tháng 03 nợ 20tr. Tháng 04 thêm 40tr. Nộp PT 50tr -> Waterfall ưu tiên nhét đầy T03 (20tr) và T04 (30tr, còn nợ 10tr).
-- Setup HD008:
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD008', 'KH08', 'NV-QLN', '2026-01-01', '2026-01-01', '2027-01-01', '2027-01-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-011', 'HD008', 'R208', 40000000, '2026-01-01', '2027-01-01', 'DangThue');

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD008-0326', 'HD008', 3, 2026, 40000000, 20000000, 20000000, 'DaThuMotPhan', '03/2026', 'NV-KT1', '2026-03-01 08:00:00'),
('BILL-HD008-0426', 'HD008', 4, 2026, 40000000, 30000000, 10000000, 'DaThuMotPhan', '04/2026', 'NV-KT1', '2026-04-01 08:00:00');

-- 1 Phiếu thu gánh cả 2 Invoice:
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-002', '2026-04-10', 50000000, 'ChuyenKhoan', 'NV-KT2', 'Waterfall Đóng bù T03 và trả trước T04');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-002', 'BILL-HD008-0326', 20000000),
('PT-002', 'BILL-HD008-0426', 30000000);


-- Void (HD009): Bị sai thông tin Kế toán
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD009', 'KH09', 'NV-QLN', '2026-02-01', '2026-02-01', '2027-02-01', '2027-02-01', 10000000, 1);

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD009-VOID', 'HD009', 3, 2026, 10000000, 0, 10000000, 'Void', '03/2026', 'NV-KT1', '2026-03-01 08:00:00');

INSERT IGNORE INTO HOA_DON_VOID (soPhieu, lyDoVoid, maNV_Void, ngayVoid) VALUES 
('BILL-HD009-VOID', 'Trùng lắp hóa đơn, gõ sai hệ số điện nước', 'NV-KT2', '2026-03-02 09:00:00');


-- =========================================================================
-- 7. CHỈ SỐ ĐIỆN NƯỚC (Tháng 03, 04 / 2026)
-- =========================================================================
INSERT IGNORE INTO CHI_SO_DIEN_NUOC (maChiSo, maPhong, thangGhi, namGhi, chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi, donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc) VALUES 
('CS-P101-0326', 'P101', 3, 2026, 100, 250, 10, 20, 3500, 15000, 525000, 150000),
('CS-P101-0426', 'P101', 4, 2026, 250, 400, 20, 32, 3500, 18000, 525000, 216000),
('CS-P102-0326', 'P102', 3, 2026, 500, 800, 50, 85, 3500, 15000, 1050000, 525000),
('CS-P104-0326', 'P104', 3, 2026, 120, 200, 15, 20, 3500, 15000, 280000, 75000),
('CS-R201-0326', 'R201', 3, 2026, 1000, 1300, 40, 60, 3500, 15000, 1050000, 300000);


-- =========================================================================
-- 8. YÊU CẦU THUÊ, BẢO TRÌ & TRANH CHẤP
-- =========================================================================
INSERT IGNORE INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email, ngayYeuCau, trangThai) VALUES 
('YCT-001', 'P103', 'Nguyễn Thị A', '0988000001', 'nga@gmail.com', '2026-04-15 10:00:00', 0),
('YCT-002', 'R209', 'Phạm B', '0988000002', 'phamb@gmail.com', '2026-04-18 14:30:00', 1);

INSERT IGNORE INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT, created_at) VALUES 
('M-P101-01', 'P101', 'Cháy bóng đèn sảnh', 'KH01', 1, 1, '2026-04-10 09:00:00'),
('M-R201-02', 'R201', 'Máy lạnh chảy nước', 'KH02', 0, 3, '2026-04-19 16:00:00');

INSERT IGNORE INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai, ngayTao) VALUES 
('TC-HD008', 'BILL-HD008-0426', 'Khách báo chuyển khoản rồi nhưng hệ thống vẫn báo nợ', 1, '2026-04-12 08:30:00');


-- =========================================================================
-- 9. LOGS (AUDIT VÀ LOGIN)
-- =========================================================================
INSERT IGNORE INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, ipAddress, thoiGian) VALUES 
('NV-ADMIN', 'VOIDING_INVOICE', 'HOA_DON_VOID', 'BILL-HD009-VOID', 'Xóa nhầm bill hóa đơn 3/2026', '127.0.0.1', '2026-03-02 09:05:00'),
('NV-QLN', 'KÝ_HỢP_ĐỒNG', 'HOP_DONG', 'HD008', 'Ký hợp đồng Waterfall', '192.168.1.100', '2026-01-01 08:00:00');

INSERT IGNORE INTO LOGIN_ATTEMPT (username, ip_address, attempt_time, status) VALUES 
('admin', '127.0.0.1', '2026-04-20 08:00:00', 1),
('hacker', '192.168.1.99', '2026-04-20 14:00:00', 0),
('ketoan1', '192.168.1.5', '2026-04-20 08:30:00', 1);

-- HOÀN THIỆN KỊCH BẢN SEED DATA.


-- ================================ GOLDEN SEED VERSION 1 ================================
-- =========================================================================
-- GOLDEN SEED DATA SCRIPT (THEO MỐC THÁNG 04/2026)
-- HỖ TRỢ TEST 8 KỊCH BẢN VẬN HÀNH BUILDING MANAGEMENT SYSTEM
-- =========================================================================

-- Tắt kiểm tra khóa ngoại tạm thời để Reset (nếu cần) nhưng các lệnh INSERT dưới đây đã được xếp ĐÚNG THỨ TỰ.
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu cũ (Tùy chọn, uncomment nếu muốn clean db trước khi chèn)
-- TRUNCATE TABLE PHIEU_THU_CHI_TIET;
-- TRUNCATE TABLE PHIEU_THU;
-- TRUNCATE TABLE HOA_DON_VOID;
-- TRUNCATE TABLE HOA_DON;
-- TRUNCATE TABLE TIEN_COC;
-- ... vv

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. CƠ SỞ VẬT CHẤT (CAO ỐC, TẦNG, PHÒNG)
-- =========================================================================
INSERT IGNORE INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES 
('BLSK-V1', 'THE SAPPHIRE', '123 Cách Mạng Tháng 8, Quận 10', 10),
('GRLN-V1', 'Green Land Building', '456 Võ Văn Kiệt, Quận 1', 15);

INSERT IGNORE INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES 
('BLSK-T1-V1', 'BLSK-V1', 'Tầng 1 (Bán lẻ)', 1.50),
('BLSK-T2-V1', 'BLSK-V1', 'Tầng 2 (Văn phòng)', 1.20),
('BLSK-T3-V1', 'BLSK-V1', 'Tầng 3 (Văn phòng VIP)', 1.50),
('GRLN-T1-V1', 'GRLN-V1', 'Tầng 1 (Sảnh Thương mại)', 2.00),
('GRLN-T2-V1', 'GRLN-V1', 'Tầng 2 (Coworking)', 1.00);

-- Sinh 20 Phòng đa dạng (Trong, DangThue, DangSuaChua)
-- Giá thuê = donGiaM2 * dienTich * heSoGia (Giả định)
INSERT IGNORE INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai) VALUES 
('P101-V1', 'BLSK-T1-V1', 'Cửa hàng tiện lợi 1', 'ThuongMai', 50.00, 10, 500000, 25000000, 2), -- Đang thuê (Happy Case 1)
('P102-V1', 'BLSK-T1-V1', 'Cửa hàng mặt tiền', 'ThuongMai', 100.00, 20, 600000, 60000000, 2), -- Đang thuê (Happy Case 1)
('P103-V1', 'BLSK-T2-V1', 'Văn phòng 1', 'VanPhong', 80.00, 15, 300000, 24000000, 1), -- Trước đó chờ duyệt, chuyển thành Trống/Lock (TC2)
('P104-V1', 'BLSK-T2-V1', 'Văn phòng 2', 'VanPhong', 80.00, 15, 300000, 24000000, 2), -- Đang thuê (Gia hạn lẻ)
('P105-V1', 'BLSK-T2-V1', 'Văn phòng 3 VIP', 'VanPhong', 100.00, 20, 350000, 35000000, 2), -- Đang thuê (Gia hạn lẻ)
('P106-V1', 'BLSK-T3-V1', 'Văn phòng 4', 'VanPhong', 60.00, 10, 300000, 18000000, 1), -- Đã trả phòng về Trống (Kết thúc lẻ)
('P107-V1', 'BLSK-T3-V1', 'Văn phòng 5', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P108-V1', 'BLSK-T3-V1', 'Văn phòng 6', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P109-V1', 'BLSK-T3-V1', 'Văn phòng 7', 'VanPhong', 120.00, 25, 300000, 36000000, 1), -- Hợp đồng đã Hủy, trả về Trống
('P110-V1', 'BLSK-T3-V1', 'Văn phòng 8', 'VanPhong', 80.00, 15, 300000, 24000000, 3), -- Đang sửa chữa
('R201-V1', 'GRLN-T1-V1', 'Mặt Bằng 1', 'ThuongMai', 200.00, 40, 800000, 160000000, 2), -- Đang thuê (Happy Case 2)
('R202-V1', 'GRLN-T1-V1', 'Mặt Bằng 2', 'ThuongMai', 150.00, 30, 800000, 120000000, 2), -- Đang thuê (Sắp hết hạn)
('R203-V1', 'GRLN-T2-V1', 'Coworking A', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R204-V1', 'GRLN-T2-V1', 'Coworking B', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R205-V1', 'GRLN-T2-V1', 'Coworking C', 'DichVu', 50.00, 15, 400000, 20000000, 3), -- Đang sửa chữa
('R206-V1', 'GRLN-T2-V1', 'Coworking D', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R207-V1', 'GRLN-T2-V1', 'Coworking E', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R208-V1', 'GRLN-T2-V1', 'Coworking F', 'DichVu', 100.00, 30, 400000, 40000000, 2), -- Gắn cho Waterfall HD008
('R209-V1', 'GRLN-T2-V1', 'Coworking G', 'DichVu', 100.00, 30, 400000, 40000000, 1),
('R210-V1', 'GRLN-T2-V1', 'Coworking VIP', 'DichVu', 150.00, 45, 500000, 75000000, 1);

-- =========================================================================
-- 2. NHÂN SỰ & QUẢN LÝ (ROLE & TÀI KHOẢN) - MK: '123456'
-- =========================================================================
-- Chú ý: hash '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a' là 123456
INSERT IGNORE INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau) VALUES 
('NV-ADMIN2-V1', 'Trần Văn Admin', 'Giám Đốc HT', '0900000001', 'admin_sys@bluesky.com', 'admin', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 1, 0),
('NV-QLN-V1', 'Lê Thị Quản Lý', 'Quản Lý Nhà', '0900000002', 'qln@bluesky.com', 'quanlynha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 2, 0),
('NV-KT1-V1', 'Phạm Kế Toán', 'Kế Toán Viên', '0900000003', 'kt1@bluesky.com', 'ketoan1', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0),
('NV-KT2-V1', 'Vũ Trưởng Toán', 'Kế Toán Trưởng', '0900000004', 'kt2@bluesky.com', 'ketoan2', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0);

-- =========================================================================
-- 3. KHÁCH HÀNG & TÀI KHOẢN KHÁCH (KHACH_HANG_ACCOUNT)
-- =========================================================================
INSERT IGNORE INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) VALUES 
('KH01-V1', 'CTY TNHH HAPPY C', '079200000001', '0911000001', 'happy_c@gmail.com', 'Quận 1, TP.HCM'),
('KH02-V1', 'CTY CỔ PHẦN ALPHA', '079200000002', '0911000002', 'alpha_cp@gmail.com', 'Quận 3, TP.HCM'),
('KH03-V1', 'Ông Hoàng Văn Chờ', '079200000003', '0911000003', 'cho_duyet@gmail.com', 'Quận 5, TP.HCM'),
('KH04-V1', 'Bà Nguyễn Thị Hạn', '079200000004', '0911000004', 'han_cuoi@gmail.com', 'Quận 10, TP.HCM'),
('KH05-V1', 'CTY TNHH GIA HẠN LẺ', '079200000005', '0911000005', 'ext_le@gmail.com', 'Bình Thạnh, TP.HCM'),
('KH06-V1', 'CTY CỔ PHẦN RÚT LUI', '079200000006', '0911000006', 'rutlui_1phan@gmail.com', 'Gò Vấp, TP.HCM'),
('KH07-V1', 'Ông Trần Hủy Kèo', '079200000007', '0911000007', 'huy_keo@gmail.com', 'Tân Bình, TP.HCM'),
('KH08-V1', 'CTY TNHH WATERFALL', '079200000008', '0911000008', 'waterfall_no@gmail.com', 'Phú Nhuận, TP.HCM'),
('KH09-V1', 'Ông Lý Hóa Đơn Lỗi', '079200000009', '0911000009', 'void_bill@gmail.com', 'Quận 2, TP.HCM'),
('KH10-V1', 'Bà Lê Vãng Lai', '079200000010', '0911000010', 'vanglai@gmail.com', 'Quận 9, TP.HCM');

-- Cấp account cho 3 khách hàng (MK: 123456)
INSERT IGNORE INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id) VALUES 
('ACC-KH01-V1', 'KH01-V1', 'kh01_happy', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH02-V1', 'KH02-V1', 'kh02_alpha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH08-V1', 'KH08-V1', 'kh08_water', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4);

-- =========================================================================
-- 4. CẤU HÌNH HỆ THỐNG
-- =========================================================================
INSERT IGNORE INTO CAU_HINH_HE_THONG (key_name, key_value, description) VALUES 
('LOCK_PHONG_PHUT', '10', 'Thời gian đóng băng khóa phòng (Phút)'),
('THUE_TOI_THIEU', '6', 'Số tháng thuê tối thiểu'),
('VAT_PERCENT', '10', 'Phần trăm thuế VAT theo quy định');

-- =========================================================================
-- 5. KỊCH BẢN HỢP ĐỒNG (HOP_DONG, CHI_TIET_HOP_DONG, TIEN_COC)
-- =========================================================================

-- Kịch bản 1 (Happy Case): 2 Hợp đồng hiệu lực
-- HD001: KH01 thuê P101, P102 (Từ 01/01/2026 - 31/12/2026).
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD001-V1', 'KH01-V1', 'NV-QLN-V1', '2025-12-25', '2026-01-01', '2026-12-31', '2026-12-31', 100000000, 1),
('HD002-V1', 'KH02-V1', 'NV-QLN-V1', '2026-01-15', '2026-02-01', '2027-02-01', '2027-02-01', 50000000, 1);

INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-001-V1', 'HD001-V1', 'P101-V1', 25000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-002-V1', 'HD001-V1', 'P102-V1', 60000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-003-V1', 'HD002-V1', 'R201-V1', 160000000, '2026-02-01', '2027-02-01', 'DangThue');

INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-001-V1', 'HD001-V1', 100000000, '2025-12-25', 'ChuyenKhoan', 'NV-QLN-V1', 1),
('COC-002-V1', 'HD002-V1', 50000000, '2026-01-15', 'TienMat', 'NV-QLN-V1', 1);


-- Kịch bản 2 (Cho Duyệt): Chưa ký, cờ 3
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD003-V1', 'KH03-V1', 'NV-QLN-V1', '2026-04-10', '2026-05-01', '2027-05-01', '2027-05-01', 20000000, 3);
-- Chưa chèn CTHD vì chưa duyệt xong


-- Kịch bản 3 (Sắp hết hạn): Tháng 05/2026 hết hạn
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD004-V1', 'KH04-V1', 'NV-QLN-V1', '2025-05-01', '2025-05-05', '2026-05-05', '2026-05-05', 40000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-004-V1', 'HD004-V1', 'R202-V1', 120000000, '2025-05-05', '2026-05-05', 'DangThue');


-- Kịch bản 4 (Gia hạn lẻ): Hợp đồng HD005 có P104 (Hết hạn 06/2026), P105 (Gia hạn thêm 3 tháng tới 09/2026)
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD005-V1', 'KH05-V1', 'NV-QLN-V1', '2025-06-01', '2025-06-01', '2026-09-01', '2026-06-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-005-V1', 'HD005-V1', 'P104-V1', 24000000, '2025-06-01', '2026-06-01', 'DangThue'),
('CTHD-006-V1', 'HD005-V1', 'P105-V1', 35000000, '2025-06-01', '2026-09-01', 'DangThue');

INSERT IGNORE INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) VALUES 
('GH-001-V1', 'HD005-V1', '2026-04-05', 3, '2026-09-01', 'Khách muốn gia hạn riêng P105 thêm 3 tháng');
INSERT IGNORE INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES 
('CTGH-001-V1', 'GH-001-V1', 'P105-V1', 35000000);


-- Kịch bản 5 (Kết thúc lẻ): Hợp đồng HD006 thuê P106, P107, P108. P106 đã kết thúc sớm.
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD006-V1', 'KH06-V1', 'NV-QLN-V1', '2025-08-01', '2025-08-01', '2026-08-01', '2026-08-01', 80000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-007-V1', 'HD006-V1', 'P106-V1', 18000000, '2025-08-01', '2026-04-15', 'DaKetThuc'),
('CTHD-008-V1', 'HD006-V1', 'P107-V1', 18000000, '2025-08-01', '2026-08-01', 'DangThue'),
('CTHD-009-V1', 'HD006-V1', 'P108-V1', 18000000, '2025-08-01', '2026-08-01', 'DangThue');


-- Kịch bản 6 (Đã Hủy): Hợp đồng HD007 bị hủy giữa chừng
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES 
('HD007-V1', 'KH07-V1', 'NV-QLN-V1', '2025-10-01', '2025-10-01', '2026-10-01', '2026-10-01', 30000000, 2, '2026-03-01', 'Công ty phá sản, thanh lý sớm');
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-010-V1', 'HD007-V1', 'P109-V1', 36000000, '2025-10-01', '2026-03-01', 'DaKetThuc');
INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-007-V1', 'HD007-V1', 30000000, '2025-10-01', 'TienMat', 'NV-QLN-V1', 4); -- 4: ChoXuLy


-- =========================================================================
-- 6. THANH TOÁN (Waterfall & Void Kịch Bản)
-- =========================================================================

-- Sinh Hóa Đơn đàng hoàng cho HD001, Tháng 03/2026 (Đã thu)
INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD001-V1-0326', 'HD001-V1', 3, 2026, 85000000, 85000000, 0, 'DaThu', '03/2026', 'NV-KT1-V1', '2026-03-01 08:00:00');
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-001-V1', '2026-03-05', 85000000, 'ChuyenKhoan', 'NV-KT1-V1', 'Thu tiền HĐ001 tháng 3/2026');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-001-V1', 'BILL-HD001-V1-0326', 85000000);


-- Waterfall (HD008): Tháng 03 nợ 20tr. Tháng 04 thêm 40tr. Nộp PT 50tr -> Waterfall ưu tiên nhét đầy T03 (20tr) và T04 (30tr, còn nợ 10tr).
-- Setup HD008:
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD008-V1', 'KH08-V1', 'NV-QLN-V1', '2026-01-01', '2026-01-01', '2027-01-01', '2027-01-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-011-V1', 'HD008-V1', 'R208-V1', 40000000, '2026-01-01', '2027-01-01', 'DangThue');

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD008-V1-0326', 'HD008-V1', 3, 2026, 40000000, 20000000, 20000000, 'DaThuMotPhan', '03/2026', 'NV-KT1-V1', '2026-03-01 08:00:00'),
('BILL-HD008-V1-0426', 'HD008-V1', 4, 2026, 40000000, 30000000, 10000000, 'DaThuMotPhan', '04/2026', 'NV-KT1-V1', '2026-04-01 08:00:00');

-- 1 Phiếu thu gánh cả 2 Invoice:
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-002-V1', '2026-04-10', 50000000, 'ChuyenKhoan', 'NV-KT2-V1', 'Waterfall Đóng bù T03 và trả trước T04');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-002-V1', 'BILL-HD008-V1-0326', 20000000),
('PT-002-V1', 'BILL-HD008-V1-0426', 30000000);


-- Void (HD009): Bị sai thông tin Kế toán
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD009-V1', 'KH09-V1', 'NV-QLN-V1', '2026-02-01', '2026-02-01', '2027-02-01', '2027-02-01', 10000000, 1);

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD009-V1-VOID', 'HD009-V1', 3, 2026, 10000000, 0, 10000000, 'Void', '03/2026', 'NV-KT1-V1', '2026-03-01 08:00:00');

INSERT IGNORE INTO HOA_DON_VOID (soPhieu, lyDoVoid, maNV_Void, ngayVoid) VALUES 
('BILL-HD009-V1-VOID', 'Trùng lắp hóa đơn, gõ sai hệ số điện nước', 'NV-KT2-V1', '2026-03-02 09:00:00');


-- =========================================================================
-- 7. CHỈ SỐ ĐIỆN NƯỚC (Tháng 03, 04 / 2026)
-- =========================================================================
INSERT IGNORE INTO CHI_SO_DIEN_NUOC (maChiSo, maPhong, thangGhi, namGhi, chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi, donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc) VALUES 
('CS-P101-V1-0326', 'P101-V1', 3, 2026, 100, 250, 10, 20, 3500, 15000, 525000, 150000),
('CS-P101-V1-0426', 'P101-V1', 4, 2026, 250, 400, 20, 32, 3500, 18000, 525000, 216000),
('CS-P102-V1-0326', 'P102-V1', 3, 2026, 500, 800, 50, 85, 3500, 15000, 1050000, 525000),
('CS-P104-V1-0326', 'P104-V1', 3, 2026, 120, 200, 15, 20, 3500, 15000, 280000, 75000),
('CS-R201-V1-0326', 'R201-V1', 3, 2026, 1000, 1300, 40, 60, 3500, 15000, 1050000, 300000);


-- =========================================================================
-- 8. YÊU CẦU THUÊ, BẢO TRÌ & TRANH CHẤP
-- =========================================================================
INSERT IGNORE INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email, ngayYeuCau, trangThai) VALUES 
('YCT-001-V1', 'P103-V1', 'Nguyễn Thị A', '0988000001', 'nga@gmail.com', '2026-04-15 10:00:00', 0),
('YCT-002-V1', 'R209-V1', 'Phạm B', '0988000002', 'phamb@gmail.com', '2026-04-18 14:30:00', 1);

INSERT IGNORE INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT, created_at) VALUES 
('M-P101-V1-01', 'P101-V1', 'Cháy bóng đèn sảnh', 'KH01-V1', 1, 1, '2026-04-10 09:00:00'),
('M-R201-V1-02', 'R201-V1', 'Máy lạnh chảy nước', 'KH02-V1', 0, 3, '2026-04-19 16:00:00');

INSERT IGNORE INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai, ngayTao) VALUES 
('TC-HD008-V1', 'BILL-HD008-V1-0426', 'Khách báo chuyển khoản rồi nhưng hệ thống vẫn báo nợ', 1, '2026-04-12 08:30:00');


-- =========================================================================
-- 9. LOGS (AUDIT VÀ LOGIN)
-- =========================================================================
INSERT IGNORE INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, ipAddress, thoiGian) VALUES 
('NV-ADMIN', 'VOIDING_INVOICE', 'HOA_DON_VOID', 'BILL-HD009-V1-VOID', 'Xóa nhầm bill hóa đơn 3/2026', '127.0.0.1', '2026-03-02 09:05:00'),
('NV-QLN-V1', 'KÝ_HỢP_ĐỒNG', 'HOP_DONG', 'HD008-V1', 'Ký hợp đồng Waterfall', '192.168.1.100', '2026-01-01 08:00:00');

INSERT IGNORE INTO LOGIN_ATTEMPT (username, ip_address, attempt_time, status) VALUES 
('admin', '127.0.0.1', '2026-04-20 08:00:00', 1),
('hacker', '192.168.1.99', '2026-04-20 14:00:00', 0),
('ketoan1', '192.168.1.5', '2026-04-20 08:30:00', 1);

-- HOÀN THIỆN KỊCH BẢN SEED DATA.


-- ================================ GOLDEN SEED VERSION 2 ================================
-- =========================================================================
-- GOLDEN SEED DATA SCRIPT (THEO MỐC THÁNG 04/2026)
-- HỖ TRỢ TEST 8 KỊCH BẢN VẬN HÀNH BUILDING MANAGEMENT SYSTEM
-- =========================================================================

-- Tắt kiểm tra khóa ngoại tạm thời để Reset (nếu cần) nhưng các lệnh INSERT dưới đây đã được xếp ĐÚNG THỨ TỰ.
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu cũ (Tùy chọn, uncomment nếu muốn clean db trước khi chèn)
-- TRUNCATE TABLE PHIEU_THU_CHI_TIET;
-- TRUNCATE TABLE PHIEU_THU;
-- TRUNCATE TABLE HOA_DON_VOID;
-- TRUNCATE TABLE HOA_DON;
-- TRUNCATE TABLE TIEN_COC;
-- ... vv

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. CƠ SỞ VẬT CHẤT (CAO ỐC, TẦNG, PHÒNG)
-- =========================================================================
INSERT IGNORE INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES 
('BLSK-V2', 'THE SAPPHIRE', '123 Cách Mạng Tháng 8, Quận 10', 10),
('GRLN-V2', 'Green Land Building', '456 Võ Văn Kiệt, Quận 1', 15);

INSERT IGNORE INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES 
('BLSK-T1-V2', 'BLSK-V2', 'Tầng 1 (Bán lẻ)', 1.50),
('BLSK-T2-V2', 'BLSK-V2', 'Tầng 2 (Văn phòng)', 1.20),
('BLSK-T3-V2', 'BLSK-V2', 'Tầng 3 (Văn phòng VIP)', 1.50),
('GRLN-T1-V2', 'GRLN-V2', 'Tầng 1 (Sảnh Thương mại)', 2.00),
('GRLN-T2-V2', 'GRLN-V2', 'Tầng 2 (Coworking)', 1.00);

-- Sinh 20 Phòng đa dạng (Trong, DangThue, DangSuaChua)
-- Giá thuê = donGiaM2 * dienTich * heSoGia (Giả định)
INSERT IGNORE INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai) VALUES 
('P101-V2', 'BLSK-T1-V2', 'Cửa hàng tiện lợi 1', 'ThuongMai', 50.00, 10, 500000, 25000000, 2), -- Đang thuê (Happy Case 1)
('P102-V2', 'BLSK-T1-V2', 'Cửa hàng mặt tiền', 'ThuongMai', 100.00, 20, 600000, 60000000, 2), -- Đang thuê (Happy Case 1)
('P103-V2', 'BLSK-T2-V2', 'Văn phòng 1', 'VanPhong', 80.00, 15, 300000, 24000000, 1), -- Trước đó chờ duyệt, chuyển thành Trống/Lock (TC2)
('P104-V2', 'BLSK-T2-V2', 'Văn phòng 2', 'VanPhong', 80.00, 15, 300000, 24000000, 2), -- Đang thuê (Gia hạn lẻ)
('P105-V2', 'BLSK-T2-V2', 'Văn phòng 3 VIP', 'VanPhong', 100.00, 20, 350000, 35000000, 2), -- Đang thuê (Gia hạn lẻ)
('P106-V2', 'BLSK-T3-V2', 'Văn phòng 4', 'VanPhong', 60.00, 10, 300000, 18000000, 1), -- Đã trả phòng về Trống (Kết thúc lẻ)
('P107-V2', 'BLSK-T3-V2', 'Văn phòng 5', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P108-V2', 'BLSK-T3-V2', 'Văn phòng 6', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P109-V2', 'BLSK-T3-V2', 'Văn phòng 7', 'VanPhong', 120.00, 25, 300000, 36000000, 1), -- Hợp đồng đã Hủy, trả về Trống
('P110-V2', 'BLSK-T3-V2', 'Văn phòng 8', 'VanPhong', 80.00, 15, 300000, 24000000, 3), -- Đang sửa chữa
('R201-V2', 'GRLN-T1-V2', 'Mặt Bằng 1', 'ThuongMai', 200.00, 40, 800000, 160000000, 2), -- Đang thuê (Happy Case 2)
('R202-V2', 'GRLN-T1-V2', 'Mặt Bằng 2', 'ThuongMai', 150.00, 30, 800000, 120000000, 2), -- Đang thuê (Sắp hết hạn)
('R203-V2', 'GRLN-T2-V2', 'Coworking A', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R204-V2', 'GRLN-T2-V2', 'Coworking B', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R205-V2', 'GRLN-T2-V2', 'Coworking C', 'DichVu', 50.00, 15, 400000, 20000000, 3), -- Đang sửa chữa
('R206-V2', 'GRLN-T2-V2', 'Coworking D', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R207-V2', 'GRLN-T2-V2', 'Coworking E', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R208-V2', 'GRLN-T2-V2', 'Coworking F', 'DichVu', 100.00, 30, 400000, 40000000, 2), -- Gắn cho Waterfall HD008
('R209-V2', 'GRLN-T2-V2', 'Coworking G', 'DichVu', 100.00, 30, 400000, 40000000, 1),
('R210-V2', 'GRLN-T2-V2', 'Coworking VIP', 'DichVu', 150.00, 45, 500000, 75000000, 1);

-- =========================================================================
-- 2. NHÂN SỰ & QUẢN LÝ (ROLE & TÀI KHOẢN) - MK: '123456'
-- =========================================================================
-- Chú ý: hash '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a' là 123456
INSERT IGNORE INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau) VALUES 
('NV-ADMIN2-V2', 'Trần Văn Admin', 'Giám Đốc HT', '0900000001', 'admin_sys@bluesky.com', 'admin', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 1, 0),
('NV-QLN-V2', 'Lê Thị Quản Lý', 'Quản Lý Nhà', '0900000002', 'qln@bluesky.com', 'quanlynha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 2, 0),
('NV-KT1-V2', 'Phạm Kế Toán', 'Kế Toán Viên', '0900000003', 'kt1@bluesky.com', 'ketoan1', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0),
('NV-KT2-V2', 'Vũ Trưởng Toán', 'Kế Toán Trưởng', '0900000004', 'kt2@bluesky.com', 'ketoan2', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0);

-- =========================================================================
-- 3. KHÁCH HÀNG & TÀI KHOẢN KHÁCH (KHACH_HANG_ACCOUNT)
-- =========================================================================
INSERT IGNORE INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) VALUES 
('KH01-V2', 'CTY TNHH HAPPY C', '079200000001', '0911000001', 'happy_c@gmail.com', 'Quận 1, TP.HCM'),
('KH02-V2', 'CTY CỔ PHẦN ALPHA', '079200000002', '0911000002', 'alpha_cp@gmail.com', 'Quận 3, TP.HCM'),
('KH03-V2', 'Ông Hoàng Văn Chờ', '079200000003', '0911000003', 'cho_duyet@gmail.com', 'Quận 5, TP.HCM'),
('KH04-V2', 'Bà Nguyễn Thị Hạn', '079200000004', '0911000004', 'han_cuoi@gmail.com', 'Quận 10, TP.HCM'),
('KH05-V2', 'CTY TNHH GIA HẠN LẺ', '079200000005', '0911000005', 'ext_le@gmail.com', 'Bình Thạnh, TP.HCM'),
('KH06-V2', 'CTY CỔ PHẦN RÚT LUI', '079200000006', '0911000006', 'rutlui_1phan@gmail.com', 'Gò Vấp, TP.HCM'),
('KH07-V2', 'Ông Trần Hủy Kèo', '079200000007', '0911000007', 'huy_keo@gmail.com', 'Tân Bình, TP.HCM'),
('KH08-V2', 'CTY TNHH WATERFALL', '079200000008', '0911000008', 'waterfall_no@gmail.com', 'Phú Nhuận, TP.HCM'),
('KH09-V2', 'Ông Lý Hóa Đơn Lỗi', '079200000009', '0911000009', 'void_bill@gmail.com', 'Quận 2, TP.HCM'),
('KH10-V2', 'Bà Lê Vãng Lai', '079200000010', '0911000010', 'vanglai@gmail.com', 'Quận 9, TP.HCM');

-- Cấp account cho 3 khách hàng (MK: 123456)
INSERT IGNORE INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id) VALUES 
('ACC-KH01-V2', 'KH01-V2', 'kh01_happy', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH02-V2', 'KH02-V2', 'kh02_alpha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH08-V2', 'KH08-V2', 'kh08_water', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4);

-- =========================================================================
-- 4. CẤU HÌNH HỆ THỐNG
-- =========================================================================
INSERT IGNORE INTO CAU_HINH_HE_THONG (key_name, key_value, description) VALUES 
('LOCK_PHONG_PHUT', '10', 'Thời gian đóng băng khóa phòng (Phút)'),
('THUE_TOI_THIEU', '6', 'Số tháng thuê tối thiểu'),
('VAT_PERCENT', '10', 'Phần trăm thuế VAT theo quy định');

-- =========================================================================
-- 5. KỊCH BẢN HỢP ĐỒNG (HOP_DONG, CHI_TIET_HOP_DONG, TIEN_COC)
-- =========================================================================

-- Kịch bản 1 (Happy Case): 2 Hợp đồng hiệu lực
-- HD001: KH01 thuê P101, P102 (Từ 01/01/2026 - 31/12/2026).
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD001-V2', 'KH01-V2', 'NV-QLN-V2', '2025-12-25', '2026-01-01', '2026-12-31', '2026-12-31', 100000000, 1),
('HD002-V2', 'KH02-V2', 'NV-QLN-V2', '2026-01-15', '2026-02-01', '2027-02-01', '2027-02-01', 50000000, 1);

INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-001-V2', 'HD001-V2', 'P101-V2', 25000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-002-V2', 'HD001-V2', 'P102-V2', 60000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-003-V2', 'HD002-V2', 'R201-V2', 160000000, '2026-02-01', '2027-02-01', 'DangThue');

INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-001-V2', 'HD001-V2', 100000000, '2025-12-25', 'ChuyenKhoan', 'NV-QLN-V2', 1),
('COC-002-V2', 'HD002-V2', 50000000, '2026-01-15', 'TienMat', 'NV-QLN-V2', 1);


-- Kịch bản 2 (Cho Duyệt): Chưa ký, cờ 3
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD003-V2', 'KH03-V2', 'NV-QLN-V2', '2026-04-10', '2026-05-01', '2027-05-01', '2027-05-01', 20000000, 3);
-- Chưa chèn CTHD vì chưa duyệt xong


-- Kịch bản 3 (Sắp hết hạn): Tháng 05/2026 hết hạn
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD004-V2', 'KH04-V2', 'NV-QLN-V2', '2025-05-01', '2025-05-05', '2026-05-05', '2026-05-05', 40000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-004-V2', 'HD004-V2', 'R202-V2', 120000000, '2025-05-05', '2026-05-05', 'DangThue');


-- Kịch bản 4 (Gia hạn lẻ): Hợp đồng HD005 có P104 (Hết hạn 06/2026), P105 (Gia hạn thêm 3 tháng tới 09/2026)
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD005-V2', 'KH05-V2', 'NV-QLN-V2', '2025-06-01', '2025-06-01', '2026-09-01', '2026-06-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-005-V2', 'HD005-V2', 'P104-V2', 24000000, '2025-06-01', '2026-06-01', 'DangThue'),
('CTHD-006-V2', 'HD005-V2', 'P105-V2', 35000000, '2025-06-01', '2026-09-01', 'DangThue');

INSERT IGNORE INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) VALUES 
('GH-001-V2', 'HD005-V2', '2026-04-05', 3, '2026-09-01', 'Khách muốn gia hạn riêng P105 thêm 3 tháng');
INSERT IGNORE INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES 
('CTGH-001-V2', 'GH-001-V2', 'P105-V2', 35000000);


-- Kịch bản 5 (Kết thúc lẻ): Hợp đồng HD006 thuê P106, P107, P108. P106 đã kết thúc sớm.
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD006-V2', 'KH06-V2', 'NV-QLN-V2', '2025-08-01', '2025-08-01', '2026-08-01', '2026-08-01', 80000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-007-V2', 'HD006-V2', 'P106-V2', 18000000, '2025-08-01', '2026-04-15', 'DaKetThuc'),
('CTHD-008-V2', 'HD006-V2', 'P107-V2', 18000000, '2025-08-01', '2026-08-01', 'DangThue'),
('CTHD-009-V2', 'HD006-V2', 'P108-V2', 18000000, '2025-08-01', '2026-08-01', 'DangThue');


-- Kịch bản 6 (Đã Hủy): Hợp đồng HD007 bị hủy giữa chừng
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES 
('HD007-V2', 'KH07-V2', 'NV-QLN-V2', '2025-10-01', '2025-10-01', '2026-10-01', '2026-10-01', 30000000, 2, '2026-03-01', 'Công ty phá sản, thanh lý sớm');
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-010-V2', 'HD007-V2', 'P109-V2', 36000000, '2025-10-01', '2026-03-01', 'DaKetThuc');
INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-007-V2', 'HD007-V2', 30000000, '2025-10-01', 'TienMat', 'NV-QLN-V2', 4); -- 4: ChoXuLy


-- =========================================================================
-- 6. THANH TOÁN (Waterfall & Void Kịch Bản)
-- =========================================================================

-- Sinh Hóa Đơn đàng hoàng cho HD001, Tháng 03/2026 (Đã thu)
INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD001-V2-0326', 'HD001-V2', 3, 2026, 85000000, 85000000, 0, 'DaThu', '03/2026', 'NV-KT1-V2', '2026-03-01 08:00:00');
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-001-V2', '2026-03-05', 85000000, 'ChuyenKhoan', 'NV-KT1-V2', 'Thu tiền HĐ001 tháng 3/2026');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-001-V2', 'BILL-HD001-V2-0326', 85000000);


-- Waterfall (HD008): Tháng 03 nợ 20tr. Tháng 04 thêm 40tr. Nộp PT 50tr -> Waterfall ưu tiên nhét đầy T03 (20tr) và T04 (30tr, còn nợ 10tr).
-- Setup HD008:
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD008-V2', 'KH08-V2', 'NV-QLN-V2', '2026-01-01', '2026-01-01', '2027-01-01', '2027-01-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-011-V2', 'HD008-V2', 'R208-V2', 40000000, '2026-01-01', '2027-01-01', 'DangThue');

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD008-V2-0326', 'HD008-V2', 3, 2026, 40000000, 20000000, 20000000, 'DaThuMotPhan', '03/2026', 'NV-KT1-V2', '2026-03-01 08:00:00'),
('BILL-HD008-V2-0426', 'HD008-V2', 4, 2026, 40000000, 30000000, 10000000, 'DaThuMotPhan', '04/2026', 'NV-KT1-V2', '2026-04-01 08:00:00');

-- 1 Phiếu thu gánh cả 2 Invoice:
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-002-V2', '2026-04-10', 50000000, 'ChuyenKhoan', 'NV-KT2-V2', 'Waterfall Đóng bù T03 và trả trước T04');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-002-V2', 'BILL-HD008-V2-0326', 20000000),
('PT-002-V2', 'BILL-HD008-V2-0426', 30000000);


-- Void (HD009): Bị sai thông tin Kế toán
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD009-V2', 'KH09-V2', 'NV-QLN-V2', '2026-02-01', '2026-02-01', '2027-02-01', '2027-02-01', 10000000, 1);

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD009-V2-VOID', 'HD009-V2', 3, 2026, 10000000, 0, 10000000, 'Void', '03/2026', 'NV-KT1-V2', '2026-03-01 08:00:00');

INSERT IGNORE INTO HOA_DON_VOID (soPhieu, lyDoVoid, maNV_Void, ngayVoid) VALUES 
('BILL-HD009-V2-VOID', 'Trùng lắp hóa đơn, gõ sai hệ số điện nước', 'NV-KT2-V2', '2026-03-02 09:00:00');


-- =========================================================================
-- 7. CHỈ SỐ ĐIỆN NƯỚC (Tháng 03, 04 / 2026)
-- =========================================================================
INSERT IGNORE INTO CHI_SO_DIEN_NUOC (maChiSo, maPhong, thangGhi, namGhi, chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi, donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc) VALUES 
('CS-P101-V2-0326', 'P101-V2', 3, 2026, 100, 250, 10, 20, 3500, 15000, 525000, 150000),
('CS-P101-V2-0426', 'P101-V2', 4, 2026, 250, 400, 20, 32, 3500, 18000, 525000, 216000),
('CS-P102-V2-0326', 'P102-V2', 3, 2026, 500, 800, 50, 85, 3500, 15000, 1050000, 525000),
('CS-P104-V2-0326', 'P104-V2', 3, 2026, 120, 200, 15, 20, 3500, 15000, 280000, 75000),
('CS-R201-V2-0326', 'R201-V2', 3, 2026, 1000, 1300, 40, 60, 3500, 15000, 1050000, 300000);


-- =========================================================================
-- 8. YÊU CẦU THUÊ, BẢO TRÌ & TRANH CHẤP
-- =========================================================================
INSERT IGNORE INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email, ngayYeuCau, trangThai) VALUES 
('YCT-001-V2', 'P103-V2', 'Nguyễn Thị A', '0988000001', 'nga@gmail.com', '2026-04-15 10:00:00', 0),
('YCT-002-V2', 'R209-V2', 'Phạm B', '0988000002', 'phamb@gmail.com', '2026-04-18 14:30:00', 1);

INSERT IGNORE INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT, created_at) VALUES 
('M-P101-V2-01', 'P101-V2', 'Cháy bóng đèn sảnh', 'KH01-V2', 1, 1, '2026-04-10 09:00:00'),
('M-R201-V2-02', 'R201-V2', 'Máy lạnh chảy nước', 'KH02-V2', 0, 3, '2026-04-19 16:00:00');

INSERT IGNORE INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai, ngayTao) VALUES 
('TC-HD008-V2', 'BILL-HD008-V2-0426', 'Khách báo chuyển khoản rồi nhưng hệ thống vẫn báo nợ', 1, '2026-04-12 08:30:00');


-- =========================================================================
-- 9. LOGS (AUDIT VÀ LOGIN)
-- =========================================================================
INSERT IGNORE INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, ipAddress, thoiGian) VALUES 
('NV-ADMIN', 'VOIDING_INVOICE', 'HOA_DON_VOID', 'BILL-HD009-V2-VOID', 'Xóa nhầm bill hóa đơn 3/2026', '127.0.0.1', '2026-03-02 09:05:00'),
('NV-QLN-V2', 'KÝ_HỢP_ĐỒNG', 'HOP_DONG', 'HD008-V2', 'Ký hợp đồng Waterfall', '192.168.1.100', '2026-01-01 08:00:00');

INSERT IGNORE INTO LOGIN_ATTEMPT (username, ip_address, attempt_time, status) VALUES 
('admin', '127.0.0.1', '2026-04-20 08:00:00', 1),
('hacker', '192.168.1.99', '2026-04-20 14:00:00', 0),
('ketoan1', '192.168.1.5', '2026-04-20 08:30:00', 1);

-- HOÀN THIỆN KỊCH BẢN SEED DATA.


-- ================================ GOLDEN SEED VERSION 3 ================================
-- =========================================================================
-- GOLDEN SEED DATA SCRIPT (THEO MỐC THÁNG 04/2026)
-- HỖ TRỢ TEST 8 KỊCH BẢN VẬN HÀNH BUILDING MANAGEMENT SYSTEM
-- =========================================================================

-- Tắt kiểm tra khóa ngoại tạm thời để Reset (nếu cần) nhưng các lệnh INSERT dưới đây đã được xếp ĐÚNG THỨ TỰ.
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu cũ (Tùy chọn, uncomment nếu muốn clean db trước khi chèn)
-- TRUNCATE TABLE PHIEU_THU_CHI_TIET;
-- TRUNCATE TABLE PHIEU_THU;
-- TRUNCATE TABLE HOA_DON_VOID;
-- TRUNCATE TABLE HOA_DON;
-- TRUNCATE TABLE TIEN_COC;
-- ... vv

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. CƠ SỞ VẬT CHẤT (CAO ỐC, TẦNG, PHÒNG)
-- =========================================================================
INSERT IGNORE INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES 
('BLSK-V3', 'THE SAPPHIRE', '123 Cách Mạng Tháng 8, Quận 10', 10),
('GRLN-V3', 'Green Land Building', '456 Võ Văn Kiệt, Quận 1', 15);

INSERT IGNORE INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES 
('BLSK-T1-V3', 'BLSK-V3', 'Tầng 1 (Bán lẻ)', 1.50),
('BLSK-T2-V3', 'BLSK-V3', 'Tầng 2 (Văn phòng)', 1.20),
('BLSK-T3-V3', 'BLSK-V3', 'Tầng 3 (Văn phòng VIP)', 1.50),
('GRLN-T1-V3', 'GRLN-V3', 'Tầng 1 (Sảnh Thương mại)', 2.00),
('GRLN-T2-V3', 'GRLN-V3', 'Tầng 2 (Coworking)', 1.00);

-- Sinh 20 Phòng đa dạng (Trong, DangThue, DangSuaChua)
-- Giá thuê = donGiaM2 * dienTich * heSoGia (Giả định)
INSERT IGNORE INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai) VALUES 
('P101-V3', 'BLSK-T1-V3', 'Cửa hàng tiện lợi 1', 'ThuongMai', 50.00, 10, 500000, 25000000, 2), -- Đang thuê (Happy Case 1)
('P102-V3', 'BLSK-T1-V3', 'Cửa hàng mặt tiền', 'ThuongMai', 100.00, 20, 600000, 60000000, 2), -- Đang thuê (Happy Case 1)
('P103-V3', 'BLSK-T2-V3', 'Văn phòng 1', 'VanPhong', 80.00, 15, 300000, 24000000, 1), -- Trước đó chờ duyệt, chuyển thành Trống/Lock (TC2)
('P104-V3', 'BLSK-T2-V3', 'Văn phòng 2', 'VanPhong', 80.00, 15, 300000, 24000000, 2), -- Đang thuê (Gia hạn lẻ)
('P105-V3', 'BLSK-T2-V3', 'Văn phòng 3 VIP', 'VanPhong', 100.00, 20, 350000, 35000000, 2), -- Đang thuê (Gia hạn lẻ)
('P106-V3', 'BLSK-T3-V3', 'Văn phòng 4', 'VanPhong', 60.00, 10, 300000, 18000000, 1), -- Đã trả phòng về Trống (Kết thúc lẻ)
('P107-V3', 'BLSK-T3-V3', 'Văn phòng 5', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P108-V3', 'BLSK-T3-V3', 'Văn phòng 6', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P109-V3', 'BLSK-T3-V3', 'Văn phòng 7', 'VanPhong', 120.00, 25, 300000, 36000000, 1), -- Hợp đồng đã Hủy, trả về Trống
('P110-V3', 'BLSK-T3-V3', 'Văn phòng 8', 'VanPhong', 80.00, 15, 300000, 24000000, 3), -- Đang sửa chữa
('R201-V3', 'GRLN-T1-V3', 'Mặt Bằng 1', 'ThuongMai', 200.00, 40, 800000, 160000000, 2), -- Đang thuê (Happy Case 2)
('R202-V3', 'GRLN-T1-V3', 'Mặt Bằng 2', 'ThuongMai', 150.00, 30, 800000, 120000000, 2), -- Đang thuê (Sắp hết hạn)
('R203-V3', 'GRLN-T2-V3', 'Coworking A', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R204-V3', 'GRLN-T2-V3', 'Coworking B', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R205-V3', 'GRLN-T2-V3', 'Coworking C', 'DichVu', 50.00, 15, 400000, 20000000, 3), -- Đang sửa chữa
('R206-V3', 'GRLN-T2-V3', 'Coworking D', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R207-V3', 'GRLN-T2-V3', 'Coworking E', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R208-V3', 'GRLN-T2-V3', 'Coworking F', 'DichVu', 100.00, 30, 400000, 40000000, 2), -- Gắn cho Waterfall HD008
('R209-V3', 'GRLN-T2-V3', 'Coworking G', 'DichVu', 100.00, 30, 400000, 40000000, 1),
('R210-V3', 'GRLN-T2-V3', 'Coworking VIP', 'DichVu', 150.00, 45, 500000, 75000000, 1);

-- =========================================================================
-- 2. NHÂN SỰ & QUẢN LÝ (ROLE & TÀI KHOẢN) - MK: '123456'
-- =========================================================================
-- Chú ý: hash '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a' là 123456
INSERT IGNORE INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau) VALUES 
('NV-ADMIN2-V3', 'Trần Văn Admin', 'Giám Đốc HT', '0900000001', 'admin_sys@bluesky.com', 'admin', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 1, 0),
('NV-QLN-V3', 'Lê Thị Quản Lý', 'Quản Lý Nhà', '0900000002', 'qln@bluesky.com', 'quanlynha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 2, 0),
('NV-KT1-V3', 'Phạm Kế Toán', 'Kế Toán Viên', '0900000003', 'kt1@bluesky.com', 'ketoan1', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0),
('NV-KT2-V3', 'Vũ Trưởng Toán', 'Kế Toán Trưởng', '0900000004', 'kt2@bluesky.com', 'ketoan2', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0);

-- =========================================================================
-- 3. KHÁCH HÀNG & TÀI KHOẢN KHÁCH (KHACH_HANG_ACCOUNT)
-- =========================================================================
INSERT IGNORE INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) VALUES 
('KH01-V3', 'CTY TNHH HAPPY C', '079200000001', '0911000001', 'happy_c@gmail.com', 'Quận 1, TP.HCM'),
('KH02-V3', 'CTY CỔ PHẦN ALPHA', '079200000002', '0911000002', 'alpha_cp@gmail.com', 'Quận 3, TP.HCM'),
('KH03-V3', 'Ông Hoàng Văn Chờ', '079200000003', '0911000003', 'cho_duyet@gmail.com', 'Quận 5, TP.HCM'),
('KH04-V3', 'Bà Nguyễn Thị Hạn', '079200000004', '0911000004', 'han_cuoi@gmail.com', 'Quận 10, TP.HCM'),
('KH05-V3', 'CTY TNHH GIA HẠN LẺ', '079200000005', '0911000005', 'ext_le@gmail.com', 'Bình Thạnh, TP.HCM'),
('KH06-V3', 'CTY CỔ PHẦN RÚT LUI', '079200000006', '0911000006', 'rutlui_1phan@gmail.com', 'Gò Vấp, TP.HCM'),
('KH07-V3', 'Ông Trần Hủy Kèo', '079200000007', '0911000007', 'huy_keo@gmail.com', 'Tân Bình, TP.HCM'),
('KH08-V3', 'CTY TNHH WATERFALL', '079200000008', '0911000008', 'waterfall_no@gmail.com', 'Phú Nhuận, TP.HCM'),
('KH09-V3', 'Ông Lý Hóa Đơn Lỗi', '079200000009', '0911000009', 'void_bill@gmail.com', 'Quận 2, TP.HCM'),
('KH10-V3', 'Bà Lê Vãng Lai', '079200000010', '0911000010', 'vanglai@gmail.com', 'Quận 9, TP.HCM');

-- Cấp account cho 3 khách hàng (MK: 123456)
INSERT IGNORE INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id) VALUES 
('ACC-KH01-V3', 'KH01-V3', 'kh01_happy', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH02-V3', 'KH02-V3', 'kh02_alpha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH08-V3', 'KH08-V3', 'kh08_water', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4);

-- =========================================================================
-- 4. CẤU HÌNH HỆ THỐNG
-- =========================================================================
INSERT IGNORE INTO CAU_HINH_HE_THONG (key_name, key_value, description) VALUES 
('LOCK_PHONG_PHUT', '10', 'Thời gian đóng băng khóa phòng (Phút)'),
('THUE_TOI_THIEU', '6', 'Số tháng thuê tối thiểu'),
('VAT_PERCENT', '10', 'Phần trăm thuế VAT theo quy định');

-- =========================================================================
-- 5. KỊCH BẢN HỢP ĐỒNG (HOP_DONG, CHI_TIET_HOP_DONG, TIEN_COC)
-- =========================================================================

-- Kịch bản 1 (Happy Case): 2 Hợp đồng hiệu lực
-- HD001: KH01 thuê P101, P102 (Từ 01/01/2026 - 31/12/2026).
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD001-V3', 'KH01-V3', 'NV-QLN-V3', '2025-12-25', '2026-01-01', '2026-12-31', '2026-12-31', 100000000, 1),
('HD002-V3', 'KH02-V3', 'NV-QLN-V3', '2026-01-15', '2026-02-01', '2027-02-01', '2027-02-01', 50000000, 1);

INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-001-V3', 'HD001-V3', 'P101-V3', 25000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-002-V3', 'HD001-V3', 'P102-V3', 60000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-003-V3', 'HD002-V3', 'R201-V3', 160000000, '2026-02-01', '2027-02-01', 'DangThue');

INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-001-V3', 'HD001-V3', 100000000, '2025-12-25', 'ChuyenKhoan', 'NV-QLN-V3', 1),
('COC-002-V3', 'HD002-V3', 50000000, '2026-01-15', 'TienMat', 'NV-QLN-V3', 1);


-- Kịch bản 2 (Cho Duyệt): Chưa ký, cờ 3
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD003-V3', 'KH03-V3', 'NV-QLN-V3', '2026-04-10', '2026-05-01', '2027-05-01', '2027-05-01', 20000000, 3);
-- Chưa chèn CTHD vì chưa duyệt xong


-- Kịch bản 3 (Sắp hết hạn): Tháng 05/2026 hết hạn
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD004-V3', 'KH04-V3', 'NV-QLN-V3', '2025-05-01', '2025-05-05', '2026-05-05', '2026-05-05', 40000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-004-V3', 'HD004-V3', 'R202-V3', 120000000, '2025-05-05', '2026-05-05', 'DangThue');


-- Kịch bản 4 (Gia hạn lẻ): Hợp đồng HD005 có P104 (Hết hạn 06/2026), P105 (Gia hạn thêm 3 tháng tới 09/2026)
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD005-V3', 'KH05-V3', 'NV-QLN-V3', '2025-06-01', '2025-06-01', '2026-09-01', '2026-06-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-005-V3', 'HD005-V3', 'P104-V3', 24000000, '2025-06-01', '2026-06-01', 'DangThue'),
('CTHD-006-V3', 'HD005-V3', 'P105-V3', 35000000, '2025-06-01', '2026-09-01', 'DangThue');

INSERT IGNORE INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) VALUES 
('GH-001-V3', 'HD005-V3', '2026-04-05', 3, '2026-09-01', 'Khách muốn gia hạn riêng P105 thêm 3 tháng');
INSERT IGNORE INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES 
('CTGH-001-V3', 'GH-001-V3', 'P105-V3', 35000000);


-- Kịch bản 5 (Kết thúc lẻ): Hợp đồng HD006 thuê P106, P107, P108. P106 đã kết thúc sớm.
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD006-V3', 'KH06-V3', 'NV-QLN-V3', '2025-08-01', '2025-08-01', '2026-08-01', '2026-08-01', 80000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-007-V3', 'HD006-V3', 'P106-V3', 18000000, '2025-08-01', '2026-04-15', 'DaKetThuc'),
('CTHD-008-V3', 'HD006-V3', 'P107-V3', 18000000, '2025-08-01', '2026-08-01', 'DangThue'),
('CTHD-009-V3', 'HD006-V3', 'P108-V3', 18000000, '2025-08-01', '2026-08-01', 'DangThue');


-- Kịch bản 6 (Đã Hủy): Hợp đồng HD007 bị hủy giữa chừng
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES 
('HD007-V3', 'KH07-V3', 'NV-QLN-V3', '2025-10-01', '2025-10-01', '2026-10-01', '2026-10-01', 30000000, 2, '2026-03-01', 'Công ty phá sản, thanh lý sớm');
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-010-V3', 'HD007-V3', 'P109-V3', 36000000, '2025-10-01', '2026-03-01', 'DaKetThuc');
INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-007-V3', 'HD007-V3', 30000000, '2025-10-01', 'TienMat', 'NV-QLN-V3', 4); -- 4: ChoXuLy


-- =========================================================================
-- 6. THANH TOÁN (Waterfall & Void Kịch Bản)
-- =========================================================================

-- Sinh Hóa Đơn đàng hoàng cho HD001, Tháng 03/2026 (Đã thu)
INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD001-V3-0326', 'HD001-V3', 3, 2026, 85000000, 85000000, 0, 'DaThu', '03/2026', 'NV-KT1-V3', '2026-03-01 08:00:00');
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-001-V3', '2026-03-05', 85000000, 'ChuyenKhoan', 'NV-KT1-V3', 'Thu tiền HĐ001 tháng 3/2026');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-001-V3', 'BILL-HD001-V3-0326', 85000000);


-- Waterfall (HD008): Tháng 03 nợ 20tr. Tháng 04 thêm 40tr. Nộp PT 50tr -> Waterfall ưu tiên nhét đầy T03 (20tr) và T04 (30tr, còn nợ 10tr).
-- Setup HD008:
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD008-V3', 'KH08-V3', 'NV-QLN-V3', '2026-01-01', '2026-01-01', '2027-01-01', '2027-01-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-011-V3', 'HD008-V3', 'R208-V3', 40000000, '2026-01-01', '2027-01-01', 'DangThue');

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD008-V3-0326', 'HD008-V3', 3, 2026, 40000000, 20000000, 20000000, 'DaThuMotPhan', '03/2026', 'NV-KT1-V3', '2026-03-01 08:00:00'),
('BILL-HD008-V3-0426', 'HD008-V3', 4, 2026, 40000000, 30000000, 10000000, 'DaThuMotPhan', '04/2026', 'NV-KT1-V3', '2026-04-01 08:00:00');

-- 1 Phiếu thu gánh cả 2 Invoice:
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-002-V3', '2026-04-10', 50000000, 'ChuyenKhoan', 'NV-KT2-V3', 'Waterfall Đóng bù T03 và trả trước T04');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-002-V3', 'BILL-HD008-V3-0326', 20000000),
('PT-002-V3', 'BILL-HD008-V3-0426', 30000000);


-- Void (HD009): Bị sai thông tin Kế toán
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD009-V3', 'KH09-V3', 'NV-QLN-V3', '2026-02-01', '2026-02-01', '2027-02-01', '2027-02-01', 10000000, 1);

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD009-V3-VOID', 'HD009-V3', 3, 2026, 10000000, 0, 10000000, 'Void', '03/2026', 'NV-KT1-V3', '2026-03-01 08:00:00');

INSERT IGNORE INTO HOA_DON_VOID (soPhieu, lyDoVoid, maNV_Void, ngayVoid) VALUES 
('BILL-HD009-V3-VOID', 'Trùng lắp hóa đơn, gõ sai hệ số điện nước', 'NV-KT2-V3', '2026-03-02 09:00:00');


-- =========================================================================
-- 7. CHỈ SỐ ĐIỆN NƯỚC (Tháng 03, 04 / 2026)
-- =========================================================================
INSERT IGNORE INTO CHI_SO_DIEN_NUOC (maChiSo, maPhong, thangGhi, namGhi, chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi, donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc) VALUES 
('CS-P101-V3-0326', 'P101-V3', 3, 2026, 100, 250, 10, 20, 3500, 15000, 525000, 150000),
('CS-P101-V3-0426', 'P101-V3', 4, 2026, 250, 400, 20, 32, 3500, 18000, 525000, 216000),
('CS-P102-V3-0326', 'P102-V3', 3, 2026, 500, 800, 50, 85, 3500, 15000, 1050000, 525000),
('CS-P104-V3-0326', 'P104-V3', 3, 2026, 120, 200, 15, 20, 3500, 15000, 280000, 75000),
('CS-R201-V3-0326', 'R201-V3', 3, 2026, 1000, 1300, 40, 60, 3500, 15000, 1050000, 300000);


-- =========================================================================
-- 8. YÊU CẦU THUÊ, BẢO TRÌ & TRANH CHẤP
-- =========================================================================
INSERT IGNORE INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email, ngayYeuCau, trangThai) VALUES 
('YCT-001-V3', 'P103-V3', 'Nguyễn Thị A', '0988000001', 'nga@gmail.com', '2026-04-15 10:00:00', 0),
('YCT-002-V3', 'R209-V3', 'Phạm B', '0988000002', 'phamb@gmail.com', '2026-04-18 14:30:00', 1);

INSERT IGNORE INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT, created_at) VALUES 
('M-P101-V3-01', 'P101-V3', 'Cháy bóng đèn sảnh', 'KH01-V3', 1, 1, '2026-04-10 09:00:00'),
('M-R201-V3-02', 'R201-V3', 'Máy lạnh chảy nước', 'KH02-V3', 0, 3, '2026-04-19 16:00:00');

INSERT IGNORE INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai, ngayTao) VALUES 
('TC-HD008-V3', 'BILL-HD008-V3-0426', 'Khách báo chuyển khoản rồi nhưng hệ thống vẫn báo nợ', 1, '2026-04-12 08:30:00');


-- =========================================================================
-- 9. LOGS (AUDIT VÀ LOGIN)
-- =========================================================================
INSERT IGNORE INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, ipAddress, thoiGian) VALUES 
('NV-ADMIN', 'VOIDING_INVOICE', 'HOA_DON_VOID', 'BILL-HD009-V3-VOID', 'Xóa nhầm bill hóa đơn 3/2026', '127.0.0.1', '2026-03-02 09:05:00'),
('NV-QLN-V3', 'KÝ_HỢP_ĐỒNG', 'HOP_DONG', 'HD008-V3', 'Ký hợp đồng Waterfall', '192.168.1.100', '2026-01-01 08:00:00');

INSERT IGNORE INTO LOGIN_ATTEMPT (username, ip_address, attempt_time, status) VALUES 
('admin', '127.0.0.1', '2026-04-20 08:00:00', 1),
('hacker', '192.168.1.99', '2026-04-20 14:00:00', 0),
('ketoan1', '192.168.1.5', '2026-04-20 08:30:00', 1);

-- HOÀN THIỆN KỊCH BẢN SEED DATA.


-- ================================ GOLDEN SEED VERSION 4 ================================
-- =========================================================================
-- GOLDEN SEED DATA SCRIPT (THEO MỐC THÁNG 04/2026)
-- HỖ TRỢ TEST 8 KỊCH BẢN VẬN HÀNH BUILDING MANAGEMENT SYSTEM
-- =========================================================================

-- Tắt kiểm tra khóa ngoại tạm thời để Reset (nếu cần) nhưng các lệnh INSERT dưới đây đã được xếp ĐÚNG THỨ TỰ.
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu cũ (Tùy chọn, uncomment nếu muốn clean db trước khi chèn)
-- TRUNCATE TABLE PHIEU_THU_CHI_TIET;
-- TRUNCATE TABLE PHIEU_THU;
-- TRUNCATE TABLE HOA_DON_VOID;
-- TRUNCATE TABLE HOA_DON;
-- TRUNCATE TABLE TIEN_COC;
-- ... vv

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. CƠ SỞ VẬT CHẤT (CAO ỐC, TẦNG, PHÒNG)
-- =========================================================================
INSERT IGNORE INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES 
('BLSK-V4', 'THE SAPPHIRE', '123 Cách Mạng Tháng 8, Quận 10', 10),
('GRLN-V4', 'Green Land Building', '456 Võ Văn Kiệt, Quận 1', 15);

INSERT IGNORE INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES 
('BLSK-T1-V4', 'BLSK-V4', 'Tầng 1 (Bán lẻ)', 1.50),
('BLSK-T2-V4', 'BLSK-V4', 'Tầng 2 (Văn phòng)', 1.20),
('BLSK-T3-V4', 'BLSK-V4', 'Tầng 3 (Văn phòng VIP)', 1.50),
('GRLN-T1-V4', 'GRLN-V4', 'Tầng 1 (Sảnh Thương mại)', 2.00),
('GRLN-T2-V4', 'GRLN-V4', 'Tầng 2 (Coworking)', 1.00);

-- Sinh 20 Phòng đa dạng (Trong, DangThue, DangSuaChua)
-- Giá thuê = donGiaM2 * dienTich * heSoGia (Giả định)
INSERT IGNORE INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai) VALUES 
('P101-V4', 'BLSK-T1-V4', 'Cửa hàng tiện lợi 1', 'ThuongMai', 50.00, 10, 500000, 25000000, 2), -- Đang thuê (Happy Case 1)
('P102-V4', 'BLSK-T1-V4', 'Cửa hàng mặt tiền', 'ThuongMai', 100.00, 20, 600000, 60000000, 2), -- Đang thuê (Happy Case 1)
('P103-V4', 'BLSK-T2-V4', 'Văn phòng 1', 'VanPhong', 80.00, 15, 300000, 24000000, 1), -- Trước đó chờ duyệt, chuyển thành Trống/Lock (TC2)
('P104-V4', 'BLSK-T2-V4', 'Văn phòng 2', 'VanPhong', 80.00, 15, 300000, 24000000, 2), -- Đang thuê (Gia hạn lẻ)
('P105-V4', 'BLSK-T2-V4', 'Văn phòng 3 VIP', 'VanPhong', 100.00, 20, 350000, 35000000, 2), -- Đang thuê (Gia hạn lẻ)
('P106-V4', 'BLSK-T3-V4', 'Văn phòng 4', 'VanPhong', 60.00, 10, 300000, 18000000, 1), -- Đã trả phòng về Trống (Kết thúc lẻ)
('P107-V4', 'BLSK-T3-V4', 'Văn phòng 5', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P108-V4', 'BLSK-T3-V4', 'Văn phòng 6', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P109-V4', 'BLSK-T3-V4', 'Văn phòng 7', 'VanPhong', 120.00, 25, 300000, 36000000, 1), -- Hợp đồng đã Hủy, trả về Trống
('P110-V4', 'BLSK-T3-V4', 'Văn phòng 8', 'VanPhong', 80.00, 15, 300000, 24000000, 3), -- Đang sửa chữa
('R201-V4', 'GRLN-T1-V4', 'Mặt Bằng 1', 'ThuongMai', 200.00, 40, 800000, 160000000, 2), -- Đang thuê (Happy Case 2)
('R202-V4', 'GRLN-T1-V4', 'Mặt Bằng 2', 'ThuongMai', 150.00, 30, 800000, 120000000, 2), -- Đang thuê (Sắp hết hạn)
('R203-V4', 'GRLN-T2-V4', 'Coworking A', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R204-V4', 'GRLN-T2-V4', 'Coworking B', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R205-V4', 'GRLN-T2-V4', 'Coworking C', 'DichVu', 50.00, 15, 400000, 20000000, 3), -- Đang sửa chữa
('R206-V4', 'GRLN-T2-V4', 'Coworking D', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R207-V4', 'GRLN-T2-V4', 'Coworking E', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R208-V4', 'GRLN-T2-V4', 'Coworking F', 'DichVu', 100.00, 30, 400000, 40000000, 2), -- Gắn cho Waterfall HD008
('R209-V4', 'GRLN-T2-V4', 'Coworking G', 'DichVu', 100.00, 30, 400000, 40000000, 1),
('R210-V4', 'GRLN-T2-V4', 'Coworking VIP', 'DichVu', 150.00, 45, 500000, 75000000, 1);

-- =========================================================================
-- 2. NHÂN SỰ & QUẢN LÝ (ROLE & TÀI KHOẢN) - MK: '123456'
-- =========================================================================
-- Chú ý: hash '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a' là 123456
INSERT IGNORE INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau) VALUES 
('NV-ADMIN2-V4', 'Trần Văn Admin', 'Giám Đốc HT', '0900000001', 'admin_sys@bluesky.com', 'admin', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 1, 0),
('NV-QLN-V4', 'Lê Thị Quản Lý', 'Quản Lý Nhà', '0900000002', 'qln@bluesky.com', 'quanlynha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 2, 0),
('NV-KT1-V4', 'Phạm Kế Toán', 'Kế Toán Viên', '0900000003', 'kt1@bluesky.com', 'ketoan1', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0),
('NV-KT2-V4', 'Vũ Trưởng Toán', 'Kế Toán Trưởng', '0900000004', 'kt2@bluesky.com', 'ketoan2', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0);

-- =========================================================================
-- 3. KHÁCH HÀNG & TÀI KHOẢN KHÁCH (KHACH_HANG_ACCOUNT)
-- =========================================================================
INSERT IGNORE INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) VALUES 
('KH01-V4', 'CTY TNHH HAPPY C', '079200000001', '0911000001', 'happy_c@gmail.com', 'Quận 1, TP.HCM'),
('KH02-V4', 'CTY CỔ PHẦN ALPHA', '079200000002', '0911000002', 'alpha_cp@gmail.com', 'Quận 3, TP.HCM'),
('KH03-V4', 'Ông Hoàng Văn Chờ', '079200000003', '0911000003', 'cho_duyet@gmail.com', 'Quận 5, TP.HCM'),
('KH04-V4', 'Bà Nguyễn Thị Hạn', '079200000004', '0911000004', 'han_cuoi@gmail.com', 'Quận 10, TP.HCM'),
('KH05-V4', 'CTY TNHH GIA HẠN LẺ', '079200000005', '0911000005', 'ext_le@gmail.com', 'Bình Thạnh, TP.HCM'),
('KH06-V4', 'CTY CỔ PHẦN RÚT LUI', '079200000006', '0911000006', 'rutlui_1phan@gmail.com', 'Gò Vấp, TP.HCM'),
('KH07-V4', 'Ông Trần Hủy Kèo', '079200000007', '0911000007', 'huy_keo@gmail.com', 'Tân Bình, TP.HCM'),
('KH08-V4', 'CTY TNHH WATERFALL', '079200000008', '0911000008', 'waterfall_no@gmail.com', 'Phú Nhuận, TP.HCM'),
('KH09-V4', 'Ông Lý Hóa Đơn Lỗi', '079200000009', '0911000009', 'void_bill@gmail.com', 'Quận 2, TP.HCM'),
('KH10-V4', 'Bà Lê Vãng Lai', '079200000010', '0911000010', 'vanglai@gmail.com', 'Quận 9, TP.HCM');

-- Cấp account cho 3 khách hàng (MK: 123456)
INSERT IGNORE INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id) VALUES 
('ACC-KH01-V4', 'KH01-V4', 'kh01_happy', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH02-V4', 'KH02-V4', 'kh02_alpha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH08-V4', 'KH08-V4', 'kh08_water', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4);

-- =========================================================================
-- 4. CẤU HÌNH HỆ THỐNG
-- =========================================================================
INSERT IGNORE INTO CAU_HINH_HE_THONG (key_name, key_value, description) VALUES 
('LOCK_PHONG_PHUT', '10', 'Thời gian đóng băng khóa phòng (Phút)'),
('THUE_TOI_THIEU', '6', 'Số tháng thuê tối thiểu'),
('VAT_PERCENT', '10', 'Phần trăm thuế VAT theo quy định');

-- =========================================================================
-- 5. KỊCH BẢN HỢP ĐỒNG (HOP_DONG, CHI_TIET_HOP_DONG, TIEN_COC)
-- =========================================================================

-- Kịch bản 1 (Happy Case): 2 Hợp đồng hiệu lực
-- HD001: KH01 thuê P101, P102 (Từ 01/01/2026 - 31/12/2026).
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD001-V4', 'KH01-V4', 'NV-QLN-V4', '2025-12-25', '2026-01-01', '2026-12-31', '2026-12-31', 100000000, 1),
('HD002-V4', 'KH02-V4', 'NV-QLN-V4', '2026-01-15', '2026-02-01', '2027-02-01', '2027-02-01', 50000000, 1);

INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-001-V4', 'HD001-V4', 'P101-V4', 25000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-002-V4', 'HD001-V4', 'P102-V4', 60000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-003-V4', 'HD002-V4', 'R201-V4', 160000000, '2026-02-01', '2027-02-01', 'DangThue');

INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-001-V4', 'HD001-V4', 100000000, '2025-12-25', 'ChuyenKhoan', 'NV-QLN-V4', 1),
('COC-002-V4', 'HD002-V4', 50000000, '2026-01-15', 'TienMat', 'NV-QLN-V4', 1);


-- Kịch bản 2 (Cho Duyệt): Chưa ký, cờ 3
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD003-V4', 'KH03-V4', 'NV-QLN-V4', '2026-04-10', '2026-05-01', '2027-05-01', '2027-05-01', 20000000, 3);
-- Chưa chèn CTHD vì chưa duyệt xong


-- Kịch bản 3 (Sắp hết hạn): Tháng 05/2026 hết hạn
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD004-V4', 'KH04-V4', 'NV-QLN-V4', '2025-05-01', '2025-05-05', '2026-05-05', '2026-05-05', 40000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-004-V4', 'HD004-V4', 'R202-V4', 120000000, '2025-05-05', '2026-05-05', 'DangThue');


-- Kịch bản 4 (Gia hạn lẻ): Hợp đồng HD005 có P104 (Hết hạn 06/2026), P105 (Gia hạn thêm 3 tháng tới 09/2026)
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD005-V4', 'KH05-V4', 'NV-QLN-V4', '2025-06-01', '2025-06-01', '2026-09-01', '2026-06-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-005-V4', 'HD005-V4', 'P104-V4', 24000000, '2025-06-01', '2026-06-01', 'DangThue'),
('CTHD-006-V4', 'HD005-V4', 'P105-V4', 35000000, '2025-06-01', '2026-09-01', 'DangThue');

INSERT IGNORE INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) VALUES 
('GH-001-V4', 'HD005-V4', '2026-04-05', 3, '2026-09-01', 'Khách muốn gia hạn riêng P105 thêm 3 tháng');
INSERT IGNORE INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES 
('CTGH-001-V4', 'GH-001-V4', 'P105-V4', 35000000);


-- Kịch bản 5 (Kết thúc lẻ): Hợp đồng HD006 thuê P106, P107, P108. P106 đã kết thúc sớm.
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD006-V4', 'KH06-V4', 'NV-QLN-V4', '2025-08-01', '2025-08-01', '2026-08-01', '2026-08-01', 80000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-007-V4', 'HD006-V4', 'P106-V4', 18000000, '2025-08-01', '2026-04-15', 'DaKetThuc'),
('CTHD-008-V4', 'HD006-V4', 'P107-V4', 18000000, '2025-08-01', '2026-08-01', 'DangThue'),
('CTHD-009-V4', 'HD006-V4', 'P108-V4', 18000000, '2025-08-01', '2026-08-01', 'DangThue');


-- Kịch bản 6 (Đã Hủy): Hợp đồng HD007 bị hủy giữa chừng
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES 
('HD007-V4', 'KH07-V4', 'NV-QLN-V4', '2025-10-01', '2025-10-01', '2026-10-01', '2026-10-01', 30000000, 2, '2026-03-01', 'Công ty phá sản, thanh lý sớm');
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-010-V4', 'HD007-V4', 'P109-V4', 36000000, '2025-10-01', '2026-03-01', 'DaKetThuc');
INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-007-V4', 'HD007-V4', 30000000, '2025-10-01', 'TienMat', 'NV-QLN-V4', 4); -- 4: ChoXuLy


-- =========================================================================
-- 6. THANH TOÁN (Waterfall & Void Kịch Bản)
-- =========================================================================

-- Sinh Hóa Đơn đàng hoàng cho HD001, Tháng 03/2026 (Đã thu)
INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD001-V4-0326', 'HD001-V4', 3, 2026, 85000000, 85000000, 0, 'DaThu', '03/2026', 'NV-KT1-V4', '2026-03-01 08:00:00');
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-001-V4', '2026-03-05', 85000000, 'ChuyenKhoan', 'NV-KT1-V4', 'Thu tiền HĐ001 tháng 3/2026');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-001-V4', 'BILL-HD001-V4-0326', 85000000);


-- Waterfall (HD008): Tháng 03 nợ 20tr. Tháng 04 thêm 40tr. Nộp PT 50tr -> Waterfall ưu tiên nhét đầy T03 (20tr) và T04 (30tr, còn nợ 10tr).
-- Setup HD008:
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD008-V4', 'KH08-V4', 'NV-QLN-V4', '2026-01-01', '2026-01-01', '2027-01-01', '2027-01-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-011-V4', 'HD008-V4', 'R208-V4', 40000000, '2026-01-01', '2027-01-01', 'DangThue');

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD008-V4-0326', 'HD008-V4', 3, 2026, 40000000, 20000000, 20000000, 'DaThuMotPhan', '03/2026', 'NV-KT1-V4', '2026-03-01 08:00:00'),
('BILL-HD008-V4-0426', 'HD008-V4', 4, 2026, 40000000, 30000000, 10000000, 'DaThuMotPhan', '04/2026', 'NV-KT1-V4', '2026-04-01 08:00:00');

-- 1 Phiếu thu gánh cả 2 Invoice:
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-002-V4', '2026-04-10', 50000000, 'ChuyenKhoan', 'NV-KT2-V4', 'Waterfall Đóng bù T03 và trả trước T04');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-002-V4', 'BILL-HD008-V4-0326', 20000000),
('PT-002-V4', 'BILL-HD008-V4-0426', 30000000);


-- Void (HD009): Bị sai thông tin Kế toán
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD009-V4', 'KH09-V4', 'NV-QLN-V4', '2026-02-01', '2026-02-01', '2027-02-01', '2027-02-01', 10000000, 1);

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD009-V4-VOID', 'HD009-V4', 3, 2026, 10000000, 0, 10000000, 'Void', '03/2026', 'NV-KT1-V4', '2026-03-01 08:00:00');

INSERT IGNORE INTO HOA_DON_VOID (soPhieu, lyDoVoid, maNV_Void, ngayVoid) VALUES 
('BILL-HD009-V4-VOID', 'Trùng lắp hóa đơn, gõ sai hệ số điện nước', 'NV-KT2-V4', '2026-03-02 09:00:00');


-- =========================================================================
-- 7. CHỈ SỐ ĐIỆN NƯỚC (Tháng 03, 04 / 2026)
-- =========================================================================
INSERT IGNORE INTO CHI_SO_DIEN_NUOC (maChiSo, maPhong, thangGhi, namGhi, chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi, donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc) VALUES 
('CS-P101-V4-0326', 'P101-V4', 3, 2026, 100, 250, 10, 20, 3500, 15000, 525000, 150000),
('CS-P101-V4-0426', 'P101-V4', 4, 2026, 250, 400, 20, 32, 3500, 18000, 525000, 216000),
('CS-P102-V4-0326', 'P102-V4', 3, 2026, 500, 800, 50, 85, 3500, 15000, 1050000, 525000),
('CS-P104-V4-0326', 'P104-V4', 3, 2026, 120, 200, 15, 20, 3500, 15000, 280000, 75000),
('CS-R201-V4-0326', 'R201-V4', 3, 2026, 1000, 1300, 40, 60, 3500, 15000, 1050000, 300000);


-- =========================================================================
-- 8. YÊU CẦU THUÊ, BẢO TRÌ & TRANH CHẤP
-- =========================================================================
INSERT IGNORE INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email, ngayYeuCau, trangThai) VALUES 
('YCT-001-V4', 'P103-V4', 'Nguyễn Thị A', '0988000001', 'nga@gmail.com', '2026-04-15 10:00:00', 0),
('YCT-002-V4', 'R209-V4', 'Phạm B', '0988000002', 'phamb@gmail.com', '2026-04-18 14:30:00', 1);

INSERT IGNORE INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT, created_at) VALUES 
('M-P101-V4-01', 'P101-V4', 'Cháy bóng đèn sảnh', 'KH01-V4', 1, 1, '2026-04-10 09:00:00'),
('M-R201-V4-02', 'R201-V4', 'Máy lạnh chảy nước', 'KH02-V4', 0, 3, '2026-04-19 16:00:00');

INSERT IGNORE INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai, ngayTao) VALUES 
('TC-HD008-V4', 'BILL-HD008-V4-0426', 'Khách báo chuyển khoản rồi nhưng hệ thống vẫn báo nợ', 1, '2026-04-12 08:30:00');


-- =========================================================================
-- 9. LOGS (AUDIT VÀ LOGIN)
-- =========================================================================
INSERT IGNORE INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, ipAddress, thoiGian) VALUES 
('NV-ADMIN', 'VOIDING_INVOICE', 'HOA_DON_VOID', 'BILL-HD009-V4-VOID', 'Xóa nhầm bill hóa đơn 3/2026', '127.0.0.1', '2026-03-02 09:05:00'),
('NV-QLN-V4', 'KÝ_HỢP_ĐỒNG', 'HOP_DONG', 'HD008-V4', 'Ký hợp đồng Waterfall', '192.168.1.100', '2026-01-01 08:00:00');

INSERT IGNORE INTO LOGIN_ATTEMPT (username, ip_address, attempt_time, status) VALUES 
('admin', '127.0.0.1', '2026-04-20 08:00:00', 1),
('hacker', '192.168.1.99', '2026-04-20 14:00:00', 0),
('ketoan1', '192.168.1.5', '2026-04-20 08:30:00', 1);

-- HOÀN THIỆN KỊCH BẢN SEED DATA.


-- ================================ GOLDEN SEED VERSION 5 ================================
-- =========================================================================
-- GOLDEN SEED DATA SCRIPT (THEO MỐC THÁNG 04/2026)
-- HỖ TRỢ TEST 8 KỊCH BẢN VẬN HÀNH BUILDING MANAGEMENT SYSTEM
-- =========================================================================

-- Tắt kiểm tra khóa ngoại tạm thời để Reset (nếu cần) nhưng các lệnh INSERT dưới đây đã được xếp ĐÚNG THỨ TỰ.
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu cũ (Tùy chọn, uncomment nếu muốn clean db trước khi chèn)
-- TRUNCATE TABLE PHIEU_THU_CHI_TIET;
-- TRUNCATE TABLE PHIEU_THU;
-- TRUNCATE TABLE HOA_DON_VOID;
-- TRUNCATE TABLE HOA_DON;
-- TRUNCATE TABLE TIEN_COC;
-- ... vv

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. CƠ SỞ VẬT CHẤT (CAO ỐC, TẦNG, PHÒNG)
-- =========================================================================
INSERT IGNORE INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES 
('BLSK-V5', 'THE SAPPHIRE', '123 Cách Mạng Tháng 8, Quận 10', 10),
('GRLN-V5', 'Green Land Building', '456 Võ Văn Kiệt, Quận 1', 15);

INSERT IGNORE INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES 
('BLSK-T1-V5', 'BLSK-V5', 'Tầng 1 (Bán lẻ)', 1.50),
('BLSK-T2-V5', 'BLSK-V5', 'Tầng 2 (Văn phòng)', 1.20),
('BLSK-T3-V5', 'BLSK-V5', 'Tầng 3 (Văn phòng VIP)', 1.50),
('GRLN-T1-V5', 'GRLN-V5', 'Tầng 1 (Sảnh Thương mại)', 2.00),
('GRLN-T2-V5', 'GRLN-V5', 'Tầng 2 (Coworking)', 1.00);

-- Sinh 20 Phòng đa dạng (Trong, DangThue, DangSuaChua)
-- Giá thuê = donGiaM2 * dienTich * heSoGia (Giả định)
INSERT IGNORE INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai) VALUES 
('P101-V5', 'BLSK-T1-V5', 'Cửa hàng tiện lợi 1', 'ThuongMai', 50.00, 10, 500000, 25000000, 2), -- Đang thuê (Happy Case 1)
('P102-V5', 'BLSK-T1-V5', 'Cửa hàng mặt tiền', 'ThuongMai', 100.00, 20, 600000, 60000000, 2), -- Đang thuê (Happy Case 1)
('P103-V5', 'BLSK-T2-V5', 'Văn phòng 1', 'VanPhong', 80.00, 15, 300000, 24000000, 1), -- Trước đó chờ duyệt, chuyển thành Trống/Lock (TC2)
('P104-V5', 'BLSK-T2-V5', 'Văn phòng 2', 'VanPhong', 80.00, 15, 300000, 24000000, 2), -- Đang thuê (Gia hạn lẻ)
('P105-V5', 'BLSK-T2-V5', 'Văn phòng 3 VIP', 'VanPhong', 100.00, 20, 350000, 35000000, 2), -- Đang thuê (Gia hạn lẻ)
('P106-V5', 'BLSK-T3-V5', 'Văn phòng 4', 'VanPhong', 60.00, 10, 300000, 18000000, 1), -- Đã trả phòng về Trống (Kết thúc lẻ)
('P107-V5', 'BLSK-T3-V5', 'Văn phòng 5', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P108-V5', 'BLSK-T3-V5', 'Văn phòng 6', 'VanPhong', 60.00, 10, 300000, 18000000, 2), -- Đang thuê (Kết thúc lẻ)
('P109-V5', 'BLSK-T3-V5', 'Văn phòng 7', 'VanPhong', 120.00, 25, 300000, 36000000, 1), -- Hợp đồng đã Hủy, trả về Trống
('P110-V5', 'BLSK-T3-V5', 'Văn phòng 8', 'VanPhong', 80.00, 15, 300000, 24000000, 3), -- Đang sửa chữa
('R201-V5', 'GRLN-T1-V5', 'Mặt Bằng 1', 'ThuongMai', 200.00, 40, 800000, 160000000, 2), -- Đang thuê (Happy Case 2)
('R202-V5', 'GRLN-T1-V5', 'Mặt Bằng 2', 'ThuongMai', 150.00, 30, 800000, 120000000, 2), -- Đang thuê (Sắp hết hạn)
('R203-V5', 'GRLN-T2-V5', 'Coworking A', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R204-V5', 'GRLN-T2-V5', 'Coworking B', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R205-V5', 'GRLN-T2-V5', 'Coworking C', 'DichVu', 50.00, 15, 400000, 20000000, 3), -- Đang sửa chữa
('R206-V5', 'GRLN-T2-V5', 'Coworking D', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R207-V5', 'GRLN-T2-V5', 'Coworking E', 'DichVu', 50.00, 15, 400000, 20000000, 1),
('R208-V5', 'GRLN-T2-V5', 'Coworking F', 'DichVu', 100.00, 30, 400000, 40000000, 2), -- Gắn cho Waterfall HD008
('R209-V5', 'GRLN-T2-V5', 'Coworking G', 'DichVu', 100.00, 30, 400000, 40000000, 1),
('R210-V5', 'GRLN-T2-V5', 'Coworking VIP', 'DichVu', 150.00, 45, 500000, 75000000, 1);

-- =========================================================================
-- 2. NHÂN SỰ & QUẢN LÝ (ROLE & TÀI KHOẢN) - MK: '123456'
-- =========================================================================
-- Chú ý: hash '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a' là 123456
INSERT IGNORE INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau) VALUES 
('NV-ADMIN2-V5', 'Trần Văn Admin', 'Giám Đốc HT', '0900000001', 'admin_sys@bluesky.com', 'admin', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 1, 0),
('NV-QLN-V5', 'Lê Thị Quản Lý', 'Quản Lý Nhà', '0900000002', 'qln@bluesky.com', 'quanlynha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 2, 0),
('NV-KT1-V5', 'Phạm Kế Toán', 'Kế Toán Viên', '0900000003', 'kt1@bluesky.com', 'ketoan1', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0),
('NV-KT2-V5', 'Vũ Trưởng Toán', 'Kế Toán Trưởng', '0900000004', 'kt2@bluesky.com', 'ketoan2', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 3, 0);

-- =========================================================================
-- 3. KHÁCH HÀNG & TÀI KHOẢN KHÁCH (KHACH_HANG_ACCOUNT)
-- =========================================================================
INSERT IGNORE INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) VALUES 
('KH01-V5', 'CTY TNHH HAPPY C', '079200000001', '0911000001', 'happy_c@gmail.com', 'Quận 1, TP.HCM'),
('KH02-V5', 'CTY CỔ PHẦN ALPHA', '079200000002', '0911000002', 'alpha_cp@gmail.com', 'Quận 3, TP.HCM'),
('KH03-V5', 'Ông Hoàng Văn Chờ', '079200000003', '0911000003', 'cho_duyet@gmail.com', 'Quận 5, TP.HCM'),
('KH04-V5', 'Bà Nguyễn Thị Hạn', '079200000004', '0911000004', 'han_cuoi@gmail.com', 'Quận 10, TP.HCM'),
('KH05-V5', 'CTY TNHH GIA HẠN LẺ', '079200000005', '0911000005', 'ext_le@gmail.com', 'Bình Thạnh, TP.HCM'),
('KH06-V5', 'CTY CỔ PHẦN RÚT LUI', '079200000006', '0911000006', 'rutlui_1phan@gmail.com', 'Gò Vấp, TP.HCM'),
('KH07-V5', 'Ông Trần Hủy Kèo', '079200000007', '0911000007', 'huy_keo@gmail.com', 'Tân Bình, TP.HCM'),
('KH08-V5', 'CTY TNHH WATERFALL', '079200000008', '0911000008', 'waterfall_no@gmail.com', 'Phú Nhuận, TP.HCM'),
('KH09-V5', 'Ông Lý Hóa Đơn Lỗi', '079200000009', '0911000009', 'void_bill@gmail.com', 'Quận 2, TP.HCM'),
('KH10-V5', 'Bà Lê Vãng Lai', '079200000010', '0911000010', 'vanglai@gmail.com', 'Quận 9, TP.HCM');

-- Cấp account cho 3 khách hàng (MK: 123456)
INSERT IGNORE INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id) VALUES 
('ACC-KH01-V5', 'KH01-V5', 'kh01_happy', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH02-V5', 'KH02-V5', 'kh02_alpha', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4),
('ACC-KH08-V5', 'KH08-V5', 'kh08_water', '$2y$10$e.wOhN.D9PzVvofL3g8m.ul1j9wR7hJdF/uI8P0M/1l/7r9aX1J6a', 4);

-- =========================================================================
-- 4. CẤU HÌNH HỆ THỐNG
-- =========================================================================
INSERT IGNORE INTO CAU_HINH_HE_THONG (key_name, key_value, description) VALUES 
('LOCK_PHONG_PHUT', '10', 'Thời gian đóng băng khóa phòng (Phút)'),
('THUE_TOI_THIEU', '6', 'Số tháng thuê tối thiểu'),
('VAT_PERCENT', '10', 'Phần trăm thuế VAT theo quy định');

-- =========================================================================
-- 5. KỊCH BẢN HỢP ĐỒNG (HOP_DONG, CHI_TIET_HOP_DONG, TIEN_COC)
-- =========================================================================

-- Kịch bản 1 (Happy Case): 2 Hợp đồng hiệu lực
-- HD001: KH01 thuê P101, P102 (Từ 01/01/2026 - 31/12/2026).
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD001-V5', 'KH01-V5', 'NV-QLN-V5', '2025-12-25', '2026-01-01', '2026-12-31', '2026-12-31', 100000000, 1),
('HD002-V5', 'KH02-V5', 'NV-QLN-V5', '2026-01-15', '2026-02-01', '2027-02-01', '2027-02-01', 50000000, 1);

INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-001-V5', 'HD001-V5', 'P101-V5', 25000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-002-V5', 'HD001-V5', 'P102-V5', 60000000, '2026-01-01', '2026-12-31', 'DangThue'),
('CTHD-003-V5', 'HD002-V5', 'R201-V5', 160000000, '2026-02-01', '2027-02-01', 'DangThue');

INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-001-V5', 'HD001-V5', 100000000, '2025-12-25', 'ChuyenKhoan', 'NV-QLN-V5', 1),
('COC-002-V5', 'HD002-V5', 50000000, '2026-01-15', 'TienMat', 'NV-QLN-V5', 1);


-- Kịch bản 2 (Cho Duyệt): Chưa ký, cờ 3
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD003-V5', 'KH03-V5', 'NV-QLN-V5', '2026-04-10', '2026-05-01', '2027-05-01', '2027-05-01', 20000000, 3);
-- Chưa chèn CTHD vì chưa duyệt xong


-- Kịch bản 3 (Sắp hết hạn): Tháng 05/2026 hết hạn
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD004-V5', 'KH04-V5', 'NV-QLN-V5', '2025-05-01', '2025-05-05', '2026-05-05', '2026-05-05', 40000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-004-V5', 'HD004-V5', 'R202-V5', 120000000, '2025-05-05', '2026-05-05', 'DangThue');


-- Kịch bản 4 (Gia hạn lẻ): Hợp đồng HD005 có P104 (Hết hạn 06/2026), P105 (Gia hạn thêm 3 tháng tới 09/2026)
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD005-V5', 'KH05-V5', 'NV-QLN-V5', '2025-06-01', '2025-06-01', '2026-09-01', '2026-06-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-005-V5', 'HD005-V5', 'P104-V5', 24000000, '2025-06-01', '2026-06-01', 'DangThue'),
('CTHD-006-V5', 'HD005-V5', 'P105-V5', 35000000, '2025-06-01', '2026-09-01', 'DangThue');

INSERT IGNORE INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) VALUES 
('GH-001-V5', 'HD005-V5', '2026-04-05', 3, '2026-09-01', 'Khách muốn gia hạn riêng P105 thêm 3 tháng');
INSERT IGNORE INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES 
('CTGH-001-V5', 'GH-001-V5', 'P105-V5', 35000000);


-- Kịch bản 5 (Kết thúc lẻ): Hợp đồng HD006 thuê P106, P107, P108. P106 đã kết thúc sớm.
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD006-V5', 'KH06-V5', 'NV-QLN-V5', '2025-08-01', '2025-08-01', '2026-08-01', '2026-08-01', 80000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-007-V5', 'HD006-V5', 'P106-V5', 18000000, '2025-08-01', '2026-04-15', 'DaKetThuc'),
('CTHD-008-V5', 'HD006-V5', 'P107-V5', 18000000, '2025-08-01', '2026-08-01', 'DangThue'),
('CTHD-009-V5', 'HD006-V5', 'P108-V5', 18000000, '2025-08-01', '2026-08-01', 'DangThue');


-- Kịch bản 6 (Đã Hủy): Hợp đồng HD007 bị hủy giữa chừng
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai, ngayHuy, lyDoHuy) VALUES 
('HD007-V5', 'KH07-V5', 'NV-QLN-V5', '2025-10-01', '2025-10-01', '2026-10-01', '2026-10-01', 30000000, 2, '2026-03-01', 'Công ty phá sản, thanh lý sớm');
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-010-V5', 'HD007-V5', 'P109-V5', 36000000, '2025-10-01', '2026-03-01', 'DaKetThuc');
INSERT IGNORE INTO TIEN_COC (maTienCoc, soHopDong, soTien, ngayNop, phuongThuc, nguoiThu, trangThai) VALUES 
('COC-007-V5', 'HD007-V5', 30000000, '2025-10-01', 'TienMat', 'NV-QLN-V5', 4); -- 4: ChoXuLy


-- =========================================================================
-- 6. THANH TOÁN (Waterfall & Void Kịch Bản)
-- =========================================================================

-- Sinh Hóa Đơn đàng hoàng cho HD001, Tháng 03/2026 (Đã thu)
INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD001-V5-0326', 'HD001-V5', 3, 2026, 85000000, 85000000, 0, 'DaThu', '03/2026', 'NV-KT1-V5', '2026-03-01 08:00:00');
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-001-V5', '2026-03-05', 85000000, 'ChuyenKhoan', 'NV-KT1-V5', 'Thu tiền HĐ001 tháng 3/2026');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-001-V5', 'BILL-HD001-V5-0326', 85000000);


-- Waterfall (HD008): Tháng 03 nợ 20tr. Tháng 04 thêm 40tr. Nộp PT 50tr -> Waterfall ưu tiên nhét đầy T03 (20tr) và T04 (30tr, còn nợ 10tr).
-- Setup HD008:
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD008-V5', 'KH08-V5', 'NV-QLN-V5', '2026-01-01', '2026-01-01', '2027-01-01', '2027-01-01', 50000000, 1);
INSERT IGNORE INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, ngayBatDau, ngayHetHan, trangThai) VALUES 
('CTHD-011-V5', 'HD008-V5', 'R208-V5', 40000000, '2026-01-01', '2027-01-01', 'DangThue');

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD008-V5-0326', 'HD008-V5', 3, 2026, 40000000, 20000000, 20000000, 'DaThuMotPhan', '03/2026', 'NV-KT1-V5', '2026-03-01 08:00:00'),
('BILL-HD008-V5-0426', 'HD008-V5', 4, 2026, 40000000, 30000000, 10000000, 'DaThuMotPhan', '04/2026', 'NV-KT1-V5', '2026-04-01 08:00:00');

-- 1 Phiếu thu gánh cả 2 Invoice:
INSERT IGNORE INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maNV, ghiChu) VALUES 
('PT-002-V5', '2026-04-10', 50000000, 'ChuyenKhoan', 'NV-KT2-V5', 'Waterfall Đóng bù T03 và trả trước T04');
INSERT IGNORE INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo) VALUES 
('PT-002-V5', 'BILL-HD008-V5-0326', 20000000),
('PT-002-V5', 'BILL-HD008-V5-0426', 30000000);


-- Void (HD009): Bị sai thông tin Kế toán
INSERT IGNORE INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayHetHanCuoiCung, ngayKetThuc, tienTienCoc, trangThai) VALUES 
('HD009-V5', 'KH09-V5', 'NV-QLN-V5', '2026-02-01', '2026-02-01', '2027-02-01', '2027-02-01', 10000000, 1);

INSERT IGNORE INTO HOA_DON (soHoaDon, soHopDong, thang, nam, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV, ngayLap) VALUES 
('BILL-HD009-V5-VOID', 'HD009-V5', 3, 2026, 10000000, 0, 10000000, 'Void', '03/2026', 'NV-KT1-V5', '2026-03-01 08:00:00');

INSERT IGNORE INTO HOA_DON_VOID (soPhieu, lyDoVoid, maNV_Void, ngayVoid) VALUES 
('BILL-HD009-V5-VOID', 'Trùng lắp hóa đơn, gõ sai hệ số điện nước', 'NV-KT2-V5', '2026-03-02 09:00:00');


-- =========================================================================
-- 7. CHỈ SỐ ĐIỆN NƯỚC (Tháng 03, 04 / 2026)
-- =========================================================================
INSERT IGNORE INTO CHI_SO_DIEN_NUOC (maChiSo, maPhong, thangGhi, namGhi, chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi, donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc) VALUES 
('CS-P101-V5-0326', 'P101-V5', 3, 2026, 100, 250, 10, 20, 3500, 15000, 525000, 150000),
('CS-P101-V5-0426', 'P101-V5', 4, 2026, 250, 400, 20, 32, 3500, 18000, 525000, 216000),
('CS-P102-V5-0326', 'P102-V5', 3, 2026, 500, 800, 50, 85, 3500, 15000, 1050000, 525000),
('CS-P104-V5-0326', 'P104-V5', 3, 2026, 120, 200, 15, 20, 3500, 15000, 280000, 75000),
('CS-R201-V5-0326', 'R201-V5', 3, 2026, 1000, 1300, 40, 60, 3500, 15000, 1050000, 300000);


-- =========================================================================
-- 8. YÊU CẦU THUÊ, BẢO TRÌ & TRANH CHẤP
-- =========================================================================
INSERT IGNORE INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email, ngayYeuCau, trangThai) VALUES 
('YCT-001-V5', 'P103-V5', 'Nguyễn Thị A', '0988000001', 'nga@gmail.com', '2026-04-15 10:00:00', 0),
('YCT-002-V5', 'R209-V5', 'Phạm B', '0988000002', 'phamb@gmail.com', '2026-04-18 14:30:00', 1);

INSERT IGNORE INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT, created_at) VALUES 
('M-P101-V5-01', 'P101-V5', 'Cháy bóng đèn sảnh', 'KH01-V5', 1, 1, '2026-04-10 09:00:00'),
('M-R201-V5-02', 'R201-V5', 'Máy lạnh chảy nước', 'KH02-V5', 0, 3, '2026-04-19 16:00:00');

INSERT IGNORE INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai, ngayTao) VALUES 
('TC-HD008-V5', 'BILL-HD008-V5-0426', 'Khách báo chuyển khoản rồi nhưng hệ thống vẫn báo nợ', 1, '2026-04-12 08:30:00');


-- =========================================================================
-- 9. LOGS (AUDIT VÀ LOGIN)
-- =========================================================================
INSERT IGNORE INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, ipAddress, thoiGian) VALUES 
('NV-ADMIN', 'VOIDING_INVOICE', 'HOA_DON_VOID', 'BILL-HD009-V5-VOID', 'Xóa nhầm bill hóa đơn 3/2026', '127.0.0.1', '2026-03-02 09:05:00'),
('NV-QLN-V5', 'KÝ_HỢP_ĐỒNG', 'HOP_DONG', 'HD008-V5', 'Ký hợp đồng Waterfall', '192.168.1.100', '2026-01-01 08:00:00');

INSERT IGNORE INTO LOGIN_ATTEMPT (username, ip_address, attempt_time, status) VALUES 
('admin', '127.0.0.1', '2026-04-20 08:00:00', 1),
('hacker', '192.168.1.99', '2026-04-20 14:00:00', 0),
('ketoan1', '192.168.1.5', '2026-04-20 08:30:00', 1);

-- HOÀN THIỆN KỊCH BẢN SEED DATA.
