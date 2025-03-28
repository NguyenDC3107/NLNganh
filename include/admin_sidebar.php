<div class="sidebar">
    <h2>Quản trị</h2>
    <ul>
        <li><a href="/NLNganh/view/admin/admin_dashboard.php">Bảng điều khiển</a></li>
        <li><a href="/NLNganh/view/admin/user_manage/manage_users.php">Quản lý người dùng</a></li>
        <li><a href="/NLNganh/view/admin/manage_projects/manage_projects.php">Quản lý đề tài</a></li>
        <li><a href="/NLNganh/view/admin/manage_departments/manage_departments.php">Quản lý các khoa</a></li>
        <li><a href="/NLNganh/view/admin/reports.php">Báo cáo</a></li>
        <li><a href="/NLNganh/view/admin/settings.php">Cài đặt</a></li>
        <li><a href="/NLNganh/logout.php">Đăng xuất</a></li>
    </ul>
</div>

<style>
    body,
    html {
        overflow-x: hidden;
        /* Ngăn chặn thanh cuộn ngang */
    }

    .sidebar {
        width: 220px;
        height: 100vh;
        background-color: #f8f9fa;
        padding: 15px;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        border-right: 2px solid #ddd;
    }

    .sidebar h2 {
        text-align: center;
        font-size: 18px;
        margin-bottom: 15px;
    }

    .sidebar ul {
        list-style-type: none;
        padding: 0;
    }

    .sidebar ul li {
        margin: 10px 0;
    }

    .sidebar ul li a {
        text-decoration: none;
        color: #333;
        display: block;
        padding: 10px;
        border-radius: 5px;
    }

    .sidebar ul li a:hover {
        background-color: #007bff;
        color: #fff;
    }

    .container-fluid {
        margin-left: 230px;
        /* Tạo khoảng cách với sidebar */
        padding: 20px;
        max-width: calc(100vw - 230px);
        /* Đảm bảo không tràn ngang */
    }

    .card-deck .card {
        min-width: 250px;
    }
</style>