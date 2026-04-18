/**
 * assets/js/room-lock.js
 * Nhiệm vụ độc lập: Chốt hạ Khóa phòng (Hold Status) để tránh đụng độ 2 Admin lập hợp đồng cùng lúc cho 1 Căn Hộ
 * Sử dụng thuần Vanilla JS DOM.
 */

// Biến rễ lưu trữ Tọa độ phòng hiện hành đang truy kích
let currentLockedRoom = null;

// Biến Engine đếm nhịp tim (Heartbeat Timer) 
let heartbeatInterval = null;

/**
 * Gửi Fetch API Yêu cầu Khóa phòng vào CSDL Memory Backend
 * @param {string} maPhong 
 */
function lockRoom(maPhong) {
    // User vô tình chọn rỗng (Vd: Option text [--Xin chọn phòng--])
    if (!maPhong || maPhong === '') {
        // Nếu đang lock thằng cũ, phải nhả thằng cũ ra vì giờ select bị reset
        if (currentLockedRoom) {
            unlockRoom(currentLockedRoom);
        }
        return;
    }
    
    // Nếu trước đó anh Cán bộ kinh doanh đang trỏ Lock một phòng khác (VD: P-101), 
    // giờ anh đổi ý chọn P-102. Chú ta bắt buộc phải gỡ P-101 ra trước khi đổi sang 102.
    if (currentLockedRoom && currentLockedRoom !== maPhong) {
        unlockRoom(currentLockedRoom);
    }

    const formData = new FormData();
    formData.append('action', 'lock');
    formData.append('maPhong', maPhong);

    // Endpoint Base URL lấy điểm neo File gọi JS tới
    // Ghi chú đếm path từ UI Form hd_them.php ../../modules/phong/phong_lock.php
    fetch('../../modules/phong/phong_lock.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            currentLockedRoom = maPhong;
            console.log("🔓 PING TRACE: " + data.message);
            // Lock thành công, truyền máu nhịp tim đập đều đặn duy trì phiên 10 phút
            startHeartbeat(maPhong); 
        } else {
            // ĂN ĐẠN RACE CONDITION (Thằng Manager kia đã Lock thành công cách đó 3 giây trước)
            alert("⚠️ CẢNH BÁO BẢO MẬT (RACE CONDITION):\n" + data.message);
            
            // Kích hoạt Reset buộc lùi lại Dropdown để cản không cho user chọn đi qua Form Wizard được
            const dp = document.getElementById('maPhong');
            if(dp) dp.value = ''; 
            
            // Ngắt máy
            currentLockedRoom = null;
            stopHeartbeat();
        }
    })
    .catch(error => {
        console.error("API Lock Room Crash Error Framework:", error);
    });
}


/**
 * Hủy Khóa Phòng Trả Lại Vào System Queue khi User tắt Node Browser hoặc chuyển thẻ phòng
 */
function unlockRoom(maPhong) {
    if (!maPhong) return;

    const formData = new FormData();
    formData.append('action', 'unlock');
    formData.append('maPhong', maPhong);

    // Kỹ thuật Cứng: Sử dụng navigator.sendBeacon 
    // Kỹ thuật này chuyên xử lý các Request đâm nền lúc sự kiện beforeunload xảy ra (Tắt tab, Load tab). 
    // Browser chém bay Request ajax thường khi tắt màn, nhưng Beacon cho phép tẩu tán Data trước khi nhắm mắt!
    if (navigator.sendBeacon) {
        navigator.sendBeacon('../../modules/phong/phong_lock.php', formData);
    } else {
        // Dự phòng cho Browser cổ đại 
        fetch('../../modules/phong/phong_lock.php', {
            method: 'POST',
            body: formData,
            keepalive: true // Cờ đặc biệt ép mạng giữ Node nối 
        }).catch(err => console.log(err));
    }
    
    // Gỡ Memory Local
    if(currentLockedRoom === maPhong) {
        currentLockedRoom = null;
    }
    stopHeartbeat();
}


/**
 * Tính năng Nhịp Đập Heartbeat: 
 * Database Backend setup mỗi record lock chỉ sống 10 phút (Tránh việc Admin cúp điện nghỉ ngang phòng bị khóa treo mãi mãi vô cực).
 * Tuy nhiên, lỡ Admin làm Hợp đồng gõ phím quá 10 phút thì sao? Vẫn phải giữ Lock tiếp! 
 * JS sẽ chích thuốc gia hạn Lock tự động sau mỗi 3 phút (An toàn x3).
 */
function startHeartbeat(maPhong) {
    stopHeartbeat(); // Clear rác timer mồ côi

    // Set 3 phút (3 * 60 * 1000 = 180.000 miliseconds)
    heartbeatInterval = setInterval(() => {
        console.log("Ping Heartbeat Auto Socket: Xin tái kí lệnh Lock Room (" + maPhong + ") thêm 10 phút.");
        
        const fd = new FormData();
        fd.append('action', 'lock');
        fd.append('maPhong', maPhong);
        
        fetch('../../modules/phong/phong_lock.php', { 
            method: 'POST', 
            body: fd 
        }).catch(e => false);

    }, 180000); 
}

// Xả Timer
function stopHeartbeat() {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
}


/**
 * Bẫy Cố Định (EventListener) 
 * Trigger khi người dùng ấn tắt chéo Tab Browser, hoặc F5 refresh load trang.
 * Nhả súng khẩn cấp phòng đã bị Hold ra ngầm.
 */
window.addEventListener('beforeunload', function (e) {
    if (currentLockedRoom) {
        // Tắt tab đi là tao trả phòng cho thằng khác mướn nha
        unlockRoom(currentLockedRoom);
    }
});
