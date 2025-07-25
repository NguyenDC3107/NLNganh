<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\research_dashboard.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/database.php';

// Lấy thông tin quản lý nghiên cứu
$manager_id = $_SESSION['user_id'];

// Đếm tổng số đề tài
$stmt = $conn->prepare("SELECT COUNT(*) as total_projects FROM de_tai_nghien_cuu");
$total_projects = 0;
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $total_projects = $result->fetch_assoc()['total_projects'];
    $stmt->close();
}

// Đếm số đề tài đang tiến hành  
$stmt = $conn->prepare("SELECT COUNT(*) as in_progress FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đang thực hiện'");
$in_progress = 0;
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $in_progress = $result->fetch_assoc()['in_progress'];
    $stmt->close();
}

// Đếm số đề tài đã hoàn thành
$stmt = $conn->prepare("SELECT COUNT(*) as completed FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hoàn thành'");
$completed = 0;
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $completed = $result->fetch_assoc()['completed'];
    $stmt->close();
}

// Đếm số đề tài đang chờ phê duyệt
$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Chờ duyệt'");
$pending = 0;
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $pending = $result->fetch_assoc()['pending'];
    $stmt->close();
}

// Lấy số sinh viên tham gia (kiểm tra bảng có tồn tại không)
$student_count = 0;
$check_table = $conn->query("SHOW TABLES LIKE 'chi_tiet_tham_gia'");
if ($check_table && $check_table->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT SV_MASV) as student_count FROM chi_tiet_tham_gia");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $student_count = $result->fetch_assoc()['student_count'];
        $stmt->close();
    }
} else {
    // Fallback: đếm số sinh viên từ bảng sinh_vien
    $check_sinh_vien = $conn->query("SHOW TABLES LIKE 'sinh_vien'");
    if ($check_sinh_vien && $check_sinh_vien->num_rows > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as student_count FROM sinh_vien");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $student_count = $result->fetch_assoc()['student_count'];
            $stmt->close();
        }
    }
}

// Lấy số giảng viên tham gia
$stmt = $conn->prepare("SELECT COUNT(DISTINCT GV_MAGV) as teacher_count FROM de_tai_nghien_cuu");
$teacher_count = 0;
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher_count = $result->fetch_assoc()['teacher_count'];
    $stmt->close();
}

// Lấy các đề tài gần đây
$recent_projects = null;
$stmt = $conn->prepare("SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, dt.DT_NGAYTAO, 
                               CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN
                       FROM de_tai_nghien_cuu dt 
                       LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV 
                       ORDER BY dt.DT_NGAYTAO DESC LIMIT 5");
if ($stmt) {
    $stmt->execute();
    $recent_projects = $stmt->get_result();
    $stmt->close();
}

// Đếm số đề tài theo khoa
$faculty_projects = [];
$stmt = $conn->prepare("SELECT k.DV_TENDV, COUNT(dt.DT_MADT) as project_count 
                       FROM de_tai_nghien_cuu dt 
                       JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV 
                       JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
                       GROUP BY k.DV_MADV, k.DV_TENDV 
                       ORDER BY project_count DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $faculty_projects[] = $row;
    }
    $stmt->close();
}

// Lấy thống kê theo khoa
$faculty_stats = [];
$stmt = $conn->prepare("SELECT k.DV_TENDV, COUNT(dt.DT_MADT) as project_count 
                       FROM de_tai_nghien_cuu dt 
                       JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV 
                       JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
                       GROUP BY k.DV_MADV, k.DV_TENDV 
                       ORDER BY project_count DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $faculty_stats[] = $row;
    }
    $stmt->close();
}

// Lấy thống kê theo loại đề tài
$project_type_stats = [];
$stmt = $conn->prepare("SELECT ldt.LDT_TENLOAI, COUNT(dt.DT_MADT) as project_count 
                       FROM de_tai_nghien_cuu dt 
                       JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA 
                       GROUP BY dt.LDT_MA, ldt.LDT_TENLOAI 
                       ORDER BY project_count DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $project_type_stats[] = $row;
    }
    $stmt->close();
}

// Lấy thống kê theo trạng thái
$status_stats = [];
$statuses = ['Chờ duyệt', 'Đang thực hiện', 'Đã hoàn thành', 'Tạm dừng'];
foreach ($statuses as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = ?");
    if ($stmt) {
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
            $status_stats[] = ['status' => $status, 'count' => $count];
        }
        $stmt->close();
    }
}
?>

<?php
// Set page title for the header
$page_title = "Bảng điều khiển | Quản lý nghiên cứu";

// Define any additional CSS specific to this page
$additional_css = '<link href="/NLNganh/assets/css/research/dashboard-sidebar-fix.css" rel="stylesheet">
<link href="/NLNganh/assets/css/research/dashboard-enhanced.css" rel="stylesheet">
<style>
    .card-counter {
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        padding: 25px 15px;
        background-color: #fff;
        height: 110px;
        border-radius: 8px;
        transition: all 0.3s ease-in-out;
        overflow: hidden;
        position: relative;
    }
    
    .card-counter:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }
    
    .card-counter i {
        font-size: 5em;
        opacity: 0.15;
        position: absolute;
        right: 10px;
        bottom: -10px;
    }
    
    .card-counter .count-numbers {
        position: absolute;
        right: 35px;
        top: 20px;
        font-size: 32px;
        font-weight: 700;
        display: block;
    }
    
    .card-counter .count-name {
        position: absolute;
        right: 35px;
        top: 65px;
        text-transform: uppercase;
        opacity: 0.9;
        display: block;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    
    .card-counter.primary {
        background-color: #4e73df;
        color: #FFF;
    }
    
    .card-counter.danger {
        background-color: #e74a3b;
        color: #FFF;
    }
    
    .card-counter.success {
        background-color: #1cc88a;
        color: #FFF;
    }
    
    .card-counter.info {
        background-color: #36b9cc;
        color: #FFF;
    }
    
    .card-counter.warning {
        background-color: #f6c23e;
        color: #FFF;
    }
    
    .quick-access-card {
        transition: all 0.3s;
        cursor: pointer;
        border: none;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    .quick-access-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.12);
    }
    
    .quick-access-card .card-body {
        padding: 1.5rem;
    }
    
    .quick-access-card .text-xs {
        letter-spacing: 0.5px;
    }
    
    .project-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        display: inline-block;
    }
    
    .status-pending {
        background-color: #f6c23e;
        color: white;
    }
    
    .status-progress {
        background-color: #4e73df;
        color: white;
    }
    
    .status-completed {
        background-color: #1cc88a;
        color: white;
    }
    
    .status-rejected {
        background-color: #e74a3b;
        color: white;
    }
    
    .activity-timeline {
        position: relative;
        padding-left: 30px;
        margin-top: 10px;
    }
    
    .activity-timeline:before {
        content: "";
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e8eaf6;
    }
    
    .activity-item {
        position: relative;
        margin-bottom: 25px;
        padding-bottom: 5px;
    }
    
    .activity-item:before {
        content: "";
        position: absolute;
        left: -22px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #4e73df;
        box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2);
        z-index: 1;
    }
    
    .activity-item:hover:before {
        background: #3a5cbe;
        box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.3);
    }
    
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease-out, transform 0.5s ease-out;
    }
    
    .animate-on-scroll.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    .fadeInUp {
        animation-name: fadeInUp;
        animation-duration: 0.5s;
        animation-fill-mode: both;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #4e73df !important;
        border-color: #4e73df !important;
        color: white !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #3a5cbe !important;
        border-color: #3a5cbe !important;
        color: white !important;
    }

    /* Fix layout positioning - giảm khoảng cách với sidebar */
    #content-wrapper {
        margin-left: 260px !important;
        width: calc(100% - 260px) !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    .container-fluid {
        padding-left: 15px !important;
        padding-right: 15px !important;
        max-width: none !important;
    }
    
    /* Đảm bảo body layout đúng */
    body {
        margin-left: 0 !important;
    }
</style>';

// Include the research header
include '../../include/research_header.php';
?>

<!-- Sidebar đã được include trong header -->

<!-- Begin Page Content -->
<div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Bảng điều khiển</h1>
                        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Tạo báo cáo
                        </a>
                    </div>

                    <!-- Content Row - Statistics Cards -->
                    <div class="row">
                        <!-- Tổng số đề tài -->
                        <div class="col-md-3 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="100">
                            <div class="card card-counter primary">
                                <i class="fa fa-folder-open"></i>
                                <span class="count-numbers"><?= $total_projects ?></span>
                                <span class="count-name">Tổng số đề tài</span>
                            </div>
                        </div>

                        <!-- Đề tài đang tiến hành -->
                        <div class="col-md-3 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="200">
                            <div class="card card-counter info">
                                <i class="fa fa-spinner"></i>
                                <span class="count-numbers"><?= $in_progress ?></span>
                                <span class="count-name">Đang tiến hành</span>
                            </div>
                        </div>

                        <!-- Đề tài hoàn thành -->
                        <div class="col-md-3 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="300">
                            <div class="card card-counter success">
                                <i class="fa fa-check-circle"></i>
                                <span class="count-numbers"><?= $completed ?></span>
                                <span class="count-name">Đã hoàn thành</span>
                            </div>
                        </div>

                        <!-- Đề tài chờ phê duyệt -->
                        <div class="col-md-3 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="400">
                            <div class="card card-counter warning">
                                <i class="fa fa-clock"></i>
                                <span class="count-numbers"><?= $pending ?></span>
                                <span class="count-name">Chờ phê duyệt</span>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row - Statistics Tables -->
                    <div class="row">
                        <!-- Thống kê theo khoa -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <!-- Card Header -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Thống kê đề tài theo khoa/đơn vị</h6>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Khoa/Đơn vị</th>
                                                    <th>Số đề tài</th>
                                                    <th>Tỷ lệ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($faculty_stats)): ?>
                                                    <?php foreach ($faculty_stats as $faculty): ?>
                                                        <?php $percentage = $total_projects > 0 ? round(($faculty['project_count'] / $total_projects) * 100, 1) : 0; ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($faculty['DV_TENDV']) ?></td>
                                                            <td>
                                                                <span class="badge badge-primary"><?= $faculty['project_count'] ?></span>
                                                            </td>
                                                            <td>
                                                                <div class="progress" style="height: 20px;">
                                                                    <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                                        <?= $percentage ?>%
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Không có dữ liệu</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thống kê theo loại đề tài và trạng thái -->
                        <div class="col-xl-4 col-lg-5">
                            <!-- Loại đề tài -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Phân loại đề tài</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($project_type_stats)): ?>
                                        <?php foreach ($project_type_stats as $type): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small"><?= htmlspecialchars($type['LDT_TENLOAI']) ?></span>
                                                <span class="badge badge-info"><?= $type['project_count'] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-center text-muted">Không có dữ liệu</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Trạng thái đề tài -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Trạng thái đề tài</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($status_stats)): ?>
                                        <?php foreach ($status_stats as $status): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small">
                                                    <?php
                                                    $status_class = '';
                                                    switch($status['status']) {
                                                        case 'Chờ duyệt':
                                                            $status_class = 'warning';
                                                            break;
                                                        case 'Đang thực hiện':
                                                            $status_class = 'primary';
                                                            break;
                                                        case 'Đã hoàn thành':
                                                            $status_class = 'success';
                                                            break;
                                                        case 'Tạm dừng':
                                                            $status_class = 'danger';
                                                            break;
                                                        default:
                                                            $status_class = 'secondary';
                                                    }
                                                    ?>
                                                    <i class="fas fa-circle text-<?= $status_class ?> mr-2"></i>
                                                    <?= htmlspecialchars($status['status']) ?>
                                                </span>
                                                <span class="badge badge-<?= $status_class ?>"><?= $status['count'] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-center text-muted">Không có dữ liệu</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row - Recent Projects and Quick Access -->
                    <div class="row">
                        <!-- Recent Projects -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Đề tài gần đây</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered datatable" id="recentProjectsTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Mã đề tài</th>
                                                    <th>Tên đề tài</th>
                                                    <th>Giảng viên</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày tạo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($recent_projects && $recent_projects->num_rows > 0): ?>
                                                    <?php while ($project = $recent_projects->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($project['DT_MADT']) ?></td>
                                                            <td><?= htmlspecialchars($project['DT_TENDT']) ?></td>
                                                            <td><?= htmlspecialchars($project['GV_HOTEN'] ?? 'Chưa phân công') ?></td>
                                                            <td>
                                                                <?php 
                                                                $status_class = '';
                                                                switch($project['DT_TRANGTHAI']) {
                                                                    case 'Chờ duyệt':
                                                                        $status_class = 'status-pending';
                                                                        break;
                                                                    case 'Đang thực hiện':
                                                                        $status_class = 'status-progress';
                                                                        break;
                                                                    case 'Đã hoàn thành':
                                                                        $status_class = 'status-completed';
                                                                        break;
                                                                    case 'Tạm dừng':
                                                                        $status_class = 'status-rejected';
                                                                        break;
                                                                    default:
                                                                        $status_class = 'status-pending';
                                                                }
                                                                ?>
                                                                <span class="project-status <?= $status_class ?>"><?= htmlspecialchars($project['DT_TRANGTHAI']) ?></span>
                                                            </td>
                                                            <td><?= isset($project['DT_NGAYTAO']) ? date('d/m/Y', strtotime($project['DT_NGAYTAO'])) : 'N/A' ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Không có đề tài nào</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="/NLNganh/view/research/manage_projects.php" class="btn btn-sm btn-primary">Xem tất cả đề tài</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access and Timeline -->
                        <div class="col-lg-6 mb-4">
                            <!-- Quick Access -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Phê duyệt đề tài</div>
                                                    <div class="small text-gray-600">Duyệt đề tài mới đang chờ</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/research/review_projects.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Quản lý hồ sơ</div>
                                                    <div class="small text-gray-600">Cập nhật thông tin cá nhân</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-user-edit fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/research/manage_profile.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Báo cáo</div>
                                                    <div class="small text-gray-600">Xem báo cáo thống kê</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/research/research_reports.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Nhà nghiên cứu</div>
                                                    <div class="small text-gray-600">Quản lý: <?= $teacher_count ?> GV, <?= $student_count ?> SV</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/research/manage_researchers.php" class="stretched-link"></a>
                                    </div>
                                </div>
                            </div>

                            <!-- Activity Timeline -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Hoạt động gần đây</h6>
                                </div>
                                <div class="card-body">
                                    <div class="activity-timeline">
                                        <?php
                                        // Mảng hoạt động demo - trong thực tế nên lấy từ cơ sở dữ liệu
                                        $activities = [
                                            ['time' => '2 giờ trước', 'text' => 'Đề tài "Nghiên cứu ứng dụng AI" được cập nhật trạng thái.'],
                                            ['time' => '1 ngày trước', 'text' => 'Đã phê duyệt đề tài "Phát triển ứng dụng web".'],
                                            ['time' => '3 ngày trước', 'text' => 'Tạo báo cáo thống kê nghiên cứu Quý 2.'],
                                            ['time' => '1 tuần trước', 'text' => 'Thêm 3 nhà nghiên cứu mới vào hệ thống.']
                                        ];
                                        
                                        foreach ($activities as $activity) {
                                            echo '<div class="activity-item">';
                                            echo '<p class="mb-1 small text-gray-600">' . $activity['time'] . '</p>';
                                            echo '<p class="mb-0">' . $activity['text'] . '</p>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->

    <!-- Page level custom scripts -->
    <script>
        // Ensure all scripts wait for both jQuery and DOM to be ready
        $(document).ready(function() {
            // Check if jQuery is loaded
            if (typeof $ === 'undefined') {
                console.error('jQuery is not loaded!');
                return;
            }
            
            // Khởi tạo DataTables cho bảng đề tài gần đây
            if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#recentProjectsTable')) {
                $('#recentProjectsTable').DataTable({
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json",
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiển thị _MENU_ mục",
                        info: "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                        infoEmpty: "Hiển thị 0 đến 0 của 0 mục",
                        infoFiltered: "(lọc từ _MAX_ mục)",
                        paginate: {
                            first: "Đầu tiên",
                            last: "Cuối cùng",
                            next: "Tiếp",
                            previous: "Trước"
                        }
                    },
                    responsive: true,
                    pageLength: 5,
                    dom: 'Bfrtip',
                    ordering: true,
                    searching: true,
                    paging: true,
                    info: true,
                    autoWidth: false
                });
            }
            
            // Animation on scroll
            function animateOnScroll() {
                $('.animate-on-scroll').each(function() {
                    const elementTop = $(this).offset().top;
                    const elementHeight = $(this).outerHeight();
                    const windowHeight = $(window).height();
                    const scrollY = window.scrollY;
                    
                    const delay = parseInt($(this).data('delay')) || 0;
                    
                    if (elementTop < (scrollY + windowHeight - elementHeight / 2)) {
                        setTimeout(() => {
                            $(this).addClass('visible');
                        }, delay);
                    }
                });
            }
            
            // Execute animation on initial load
            setTimeout(function() {
                animateOnScroll();
            }, 100);
            
            // Execute animation on scroll
            $(window).on('scroll', function() {
                animateOnScroll();
            });
            
            // Counter animation
            $('.count-numbers').each(function () {
                const $this = $(this);
                const countTo = parseInt($this.text());
                
                if (countTo > 0) {
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 1000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum));
                        },
                        complete: function() {
                            $this.text(this.countNum);
                        }
                    });
                }
            });
            
            // Hover effects for statistics tables
            $('.table tbody tr').hover(
                function() {
                    $(this).addClass('table-active');
                },
                function() {
                    $(this).removeClass('table-active');
                }
            );
            
            // Progress bar animation
            $('.progress-bar').each(function() {
                const $this = $(this);
                const width = $this.attr('aria-valuenow');
                if (width && width > 0) {
                    $this.css('width', '0%');
                    
                    setTimeout(function() {
                        $this.animate({
                            width: width + '%'
                        }, 1000);
                    }, 500);
                }
            });
            
            console.log('Dashboard scripts initialized successfully');
        });
    </script>

    <!-- Dashboard enhancement scripts -->
    <script>
        // Load dashboard enhancement scripts after jQuery is ready
        $(document).ready(function() {
            // Dashboard specific functionality
            console.log("Dashboard loaded successfully");
            
            // Đảm bảo sidebar luôn cố định
            if (typeof $ !== "undefined") {
                // Xóa mọi class có thể gây thu gọn sidebar
                $("body").removeClass("sidebar-toggled");
                
                // Đảm bảo sidebar có class cố định
                $(".modern-sidebar").addClass("fixed-sidebar");
                
                // Ẩn hoàn toàn các nút toggle
                $(".sidebar-collapse-toggle").hide();
                $(".toggle-sidebar-btn").hide();
                
                // Đảm bảo layout đúng
                $("#content-wrapper").css({
                    "margin-left": "260px",
                    "width": "calc(100% - 260px)",
                    "padding-left": "15px",
                    "padding-right": "15px"
                });
                
                // Đảm bảo container-fluid có padding phù hợp
                $(".container-fluid").css({
                    "padding-left": "15px",
                    "padding-right": "15px",
                    "max-width": "none"
                });
                
                // Enhanced counter animations
                setTimeout(function() {
                    $(".count-numbers").each(function() {
                        const $this = $(this);
                        const countTo = parseInt($this.text());
                        
                        if (countTo > 0) {
                            $this.addClass("counting");
                            $({ countNum: 0 }).animate({
                                countNum: countTo
                            }, {
                                duration: 1500,
                                easing: "swing",
                                step: function() {
                                    $this.text(Math.floor(this.countNum));
                                },
                                complete: function() {
                                    $this.text(this.countNum);
                                    $this.removeClass("counting");
                                }
                            });
                        }
                    });
                }, 500);
                
                // Đảm bảo sidebar không thể bị thay đổi
                setInterval(function() {
                    $("body").removeClass("sidebar-toggled");
                    $(".modern-sidebar").css({
                        "width": "260px",
                        "transform": "none",
                        "display": "block"
                    });
                }, 1000);
            }
        });
    </script>

<?php 
// Include footer
include '../../include/research_footer.php';
?>
