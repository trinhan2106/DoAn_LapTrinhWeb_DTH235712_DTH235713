/**
 * PROJECT: Quản lý Cao ốc
 * MODULE: DataTables Global Initialization & Vietnamese Localization
 */

$(document).ready(function() {
    // Khởi tạo DataTables cho tất cả các bảng có class 'table-datatable'
    $('.table-datatable').DataTable({
        "language": {
            "sProcessing":   "Đang xử lý...",
            "sLengthMenu":   "Hiển thị _MENU_ dòng",
            "sZeroRecords":  "Không tìm thấy dòng nào phù hợp",
            "sInfo":         "Đang xem _START_ đến _END_ trong tổng số _TOTAL_ mục",
            "sInfoEmpty":    "Đang xem 0 đến 0 trong tổng số 0 mục",
            "sInfoFiltered": "(được lọc từ _MAX_ mục)",
            "sInfoPostFix":  "",
            "sSearch":       "Tìm kiếm nhanh:",
            "sUrl":          "",
            "oPaginate": {
                "sFirst":    "Đầu",
                "sPrevious": "Trước",
                "sNext":     "Tiếp",
                "sLast":     "Cuối"
            },
            "oAria": {
                "sSortAscending":  ": Sắp xếp cột theo thứ tự tăng dần",
                "sSortDescending": ": Sắp xếp cột theo thứ tự giảm dần"
            }
        },
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
        "ordering": true,
        "stateSave": true, // Ghi nhớ trạng thái bộ lọc khi F5 trang
        "responsive": true,
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    });
});
