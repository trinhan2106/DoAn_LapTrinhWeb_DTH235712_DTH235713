<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php'; // Để dùng sinhMaNgauNhien
kiemTraSession();

if ((int)$_SESSION['user_role'] !== 4) {
    header("Location: " . BASE_URL . "dangnhap.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();
$maKH = $_SESSION['user_id'];

// Xử lý POST lưu yêu cầu gia hạn
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soHopDong = $_POST['soHopDong'] ?? '';
    $soThangDeXuat = (int)($_POST['soThangDeXuat'] ?? 0);
    $lyDo = trim($_POST['lyDo'] ?? '');

    // Chống IDOR: Xác thực hợp đồng này có đúng là của khách hàng đang login không
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM HOP_DONG WHERE soHopDong = ? AND maKH = ? AND trangThai = 1");
    $stmtCheck->execute([$soHopDong, $maKH]);
    if ($stmtCheck->fetchColumn() == 0) {
        $_SESSION['error_msg'] = "Hợp đồng không hợp lệ hoặc đã hết hạn.";
        header("Location: yeu_cau_giahan.php");
        exit();
    }

    try {
        $maYC = sinhMaNgauNhien('YCGH-' . date('Ym') . '-', 6);
        $stmtInsert = $pdo->prepare("INSERT INTO YEU_CAU_GIA_HAN (maYeuCauGH, soHopDong, soThangDeXuat, lyDo, trangThai) VALUES (?, ?, ?, ?, 0)");
        $stmtInsert->execute([$maYC, $soHopDong, $soThangDeXuat, htmlspecialchars($lyDo)]);
        
        $_SESSION['success_msg'] = "Đã gửi yêu cầu gia hạn thành công! Vui lòng chờ phản hồi.";
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        error_log("Lỗi tạo YCGH: " . $e->getMessage());
        $_SESSION['error_msg'] = "Có lỗi xảy ra, vui lòng thử lại sau.";
    }
}

// Lấy danh sách hợp đồng đang hiệu lực của khách hàng để hiển thị vào Select
$stmtHD = $pdo->prepare("SELECT soHopDong, ngayBatDau, ngayKetThuc, ngayHetHanCuoiCung FROM HOP_DONG WHERE maKH = ? AND trangThai = 1");
$stmtHD->execute([$maKH]);
$dsHopDong = $stmtHD->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách phòng tương ứng với các hợp đồng này để dùng cho JS render
$stmtPhong = $pdo->prepare("
    SELECT c.soHopDong, c.maPhong, p.tenPhong, c.ngayHetHan 
    FROM CHI_TIET_HOP_DONG c 
    JOIN PHONG p ON c.maPhong = p.maPhong 
    JOIN HOP_DONG h ON c.soHopDong = h.soHopDong
    WHERE h.maKH = ? AND h.trangThai = 1
");
$stmtPhong->execute([$maKH]);
$dsPhong = $stmtPhong->fetchAll(PDO::FETCH_ASSOC);

// Chuyển danh sách phòng sang JSON để JS sử dụng
$roomsJson = json_encode($dsPhong);

// Include Header
require_once __DIR__ . '/../../includes/tenant/header.php';
?>

<div class="container py-5" style="max-width: 900px;">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-navy">Trang chủ</a></li>
                <li class="breadcrumb-item active">Yêu cầu gia hạn</li>
            </ol>
        </nav>
        <h2 class="fw-bold text-navy"><i class="fa-solid fa-clock-rotate-left me-2"></i>Gia Hạn Hợp Đồng</h2>
        <p class="text-muted">Gửi yêu cầu gia hạn thời gian thuê trực tuyến một cách nhanh chóng.</p>
    </div>

    <!-- Thông báo lỗi/thành công -->
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-navy py-3 px-4">
            <h5 class="mb-0 text-white fw-bold">Tạo Yêu Cầu Gia Hạn</h5>
        </div>
        <div class="card-body p-4">
            <form id="formGiaHan" action="yeu_cau_giahan.php" method="POST">
                <!-- Chọn Hợp Đồng -->
                <div class="mb-4">
                    <label for="soHopDong" class="form-label fw-bold text-navy">Hợp đồng muốn gia hạn <span class="text-danger">*</span></label>
                    <select class="form-select form-select-lg rounded-3 border-2" id="soHopDong" name="soHopDong" required>
                        <option value="" selected disabled>-- Chọn hợp đồng --</option>
                        <?php foreach($dsHopDong as $hd): ?>
                            <option value="<?php echo e($hd['soHopDong']); ?>">
                                <?php echo e($hd['soHopDong']); ?> (Hết hạn: <?php echo date('d/m/Y', strtotime($hd['ngayHetHanCuoiCung'] ?? $hd['ngayKetThuc'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Danh sách phòng thuê (Sẽ render bằng JS) -->
                <div id="roomSection" class="mb-4 d-none">
                    <label class="form-label fw-bold text-navy mb-3"><i class="fa-solid fa-building-user me-2"></i>Chọn số tháng gia hạn cho từng mặt bằng:</label>
                    <div id="roomList" class="bg-light p-3 rounded-4 border">
                        <!-- Rooms will be inserted here -->
                    </div>
                    <div class="form-text mt-2"><i class="fa-solid fa-circle-info me-1"></i> Nhập số tháng (ví dụ: 6, 12, 24) cho từng phòng bạn muốn gia hạn.</div>
                </div>

                <!-- Ghi chú thêm -->
                <div class="mb-4">
                    <label for="ghiChuThem" class="form-label fw-bold text-navy">Ghi chú / Đề xuất thêm</label>
                    <textarea class="form-control rounded-3 border-2" id="ghiChuThem" rows="3" placeholder="Nhập thêm lý do hoặc yêu cầu đặc biệt nếu có..."></textarea>
                </div>

                <!-- Hidden Inputs for Post Data -->
                <input type="hidden" id="lyDo" name="lyDo">
                <input type="hidden" id="soThangDeXuat" name="soThangDeXuat" value="0">

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="dashboard.php" class="btn btn-light rounded-pill px-4 me-md-2">Hủy bỏ</a>
                    <button type="submit" class="btn btn-navy rounded-pill px-5 py-2 fw-bold" style="background-color: #1e3a5f; color: #fff;">
                        <i class="fa-solid fa-paper-plane me-2"></i>Gửi Yêu Cầu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript Logic -->
<script>
    const roomsData = <?php echo $roomsJson; ?>;
    const soHopDongSelect = document.getElementById('soHopDong');
    const roomSection = document.getElementById('roomSection');
    const roomListContainer = document.getElementById('roomList');
    const formGiaHan = document.getElementById('formGiaHan');

    // Sự kiện khi thay đổi Hợp đồng
    soHopDongSelect.addEventListener('change', function() {
        const selectedHD = this.value;
        const filteredRooms = roomsData.filter(r => r.soHopDong === selectedHD);
        
        // Clear previous rooms
        roomListContainer.innerHTML = '';
        
        if (filteredRooms.length > 0) {
            roomSection.classList.remove('d-none');
            filteredRooms.forEach(room => {
                const row = document.createElement('div');
                row.className = 'row align-items-center mb-3 bg-white p-3 rounded-3 shadow-xs mx-0 border';
                row.innerHTML = `
                    <div class="col-md-7">
                        <div class="fw-bold text-navy">${room.tenPhong}</div>
                        <small class="text-muted">Mã: ${room.maPhong} | Hết hạn cũ: ${formatDate(room.ngayHetHan)}</small>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="number" class="form-control border-2 room-months" 
                                   data-room-name="${room.tenPhong}" 
                                   data-room-id="${room.maPhong}" 
                                   min="1" placeholder="Số tháng...">
                            <span class="input-group-text">tháng</span>
                        </div>
                    </div>
                `;
                roomListContainer.appendChild(row);
            });
        } else {
            roomSection.classList.add('d-none');
        }
    });

    // Xử lý trước khi Submit
    formGiaHan.addEventListener('submit', function(e) {
        const roomInputs = document.querySelectorAll('.room-months');
        let concatLyDo = "";
        let maxMonths = 0;
        let hasVal = false;

        roomInputs.forEach(input => {
            const months = parseInt(input.value);
            if (months > 0) {
                hasVal = true;
                concatLyDo += `Phòng ${input.dataset.roomName} (${input.dataset.roomId}) gia hạn ${months} tháng; `;
                if (months > maxMonths) maxMonths = months;
            }
        });

        if (!hasVal) {
            e.preventDefault();
            alert("Vui lòng nhập ít nhất một phòng có số tháng gia hạn > 0.");
            return;
        }

        // Thêm ghi chú của khách hàng
        const ghiChuClient = document.getElementById('ghiChuThem').value.trim();
        if (ghiChuClient) {
            concatLyDo += "\n[Ghi chú thêm]: " + ghiChuClient;
        }

        // Gán vào hidden fields
        document.getElementById('lyDo').value = concatLyDo;
        document.getElementById('soThangDeXuat').value = maxMonths;
    });

    // Helper format date
    function formatDate(dateStr) {
        if (!dateStr) return "N/A";
        const d = new Date(dateStr);
        return d.toLocaleDateString('vi-VN');
    }
</script>

<style>
    .shadow-xs { box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .btn-navy:hover { background-color: #162a45 !important; }
</style>

<?php
require_once __DIR__ . '/../../includes/tenant/footer.php';
?>
