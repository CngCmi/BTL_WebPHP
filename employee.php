<?php
include '../config/db.php';
include '../includes/header.php';
include '../includes/navbar.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Employee') {
    header('Location: /model-shop/index.php');
    exit;
}
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Trang nhân viên</h2>
    <p>Chào mừng Nhân viên! Bạn có thể quản lý sản phẩm, kho hàng, chăm sóc khách hàng, quản lý đơn hàng, và xem báo cáo tại đây.</p>
</div>

<?php include '../includes/footer.php'; ?>