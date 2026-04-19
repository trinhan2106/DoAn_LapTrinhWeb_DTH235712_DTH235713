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


-- 20. Bảng TRANH_CHAP_HOA_DON (Giải quyết khiếu nại của khách)
CREATE TABLE TRANH_CHAP_HOA_DON (
    id VARCHAR(50) PRIMARY KEY,
    maHoaDon VARCHAR(50) NOT NULL,
    noiDung TEXT NOT NULL,
    trangThai TINYINT DEFAULT 0 COMMENT '0: Moi tao, 1: Dang xu ly, 2: Da giai quyet',
    ngayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tranhchap_hd FOREIGN KEY (maHoaDon) REFERENCES HOA_DON(soHoaDon)
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
-- ==========================================================
-- TẠO DỮ LIỆU CƠ SỞ VẬT CHẤT (TÒA NHÀ & TẦNG MỒI)
INSERT INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) 
VALUES ('CO-01', 'Blue Sky Tower Khối A', '123 CMT8, Q1', 10);

INSERT INTO TANG (maTang, maCaoOc, tenTang, heSoGia) 
VALUES ('T-01', 'CO-01', 'Tầng Trệt Kinh Doanh (Khối A)', 1.25);

INSERT INTO TANG (maTang, maCaoOc, tenTang, heSoGia) 
VALUES ('T-02', 'CO-01', 'Tầng Văn Phòng VIP (Khối A)', 1.00);

-- Tạo tài khoản Admin mặc định để test hệ thống
-- Lưu ý: password_hash bên dưới là kết quả sau khi Bcrypt (Cost 10) chữ "password"
INSERT INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau) 
VALUES ('NV-ADMIN', 'Quản trị viên', 'Admin Hệ Thống', '0901234567', 'admin@example.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);
