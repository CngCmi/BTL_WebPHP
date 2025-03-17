<?php
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/model-shop/index.php"><i class="fas fa-store me-2"></i>Cửa hàng mô hình</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($user): ?>
                    <?php if ($user['Role'] === 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/admin.php"><i class="fas fa-boxes me-1"></i> Quản lý sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/user_management.php"><i class="fas fa-users me-1"></i> Quản lý người dùng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/order_management.php"><i class="fas fa-shopping-cart me-1"></i> Quản lý đơn hàng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/reports.php"><i class="fas fa-chart-line me-1"></i> Báo cáo & Thống kê</a>
                        </li>
                    <?php elseif ($user['Role'] === 'Employee'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/admin.php"><i class="fas fa-boxes me-1"></i> Quản lý sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/customer_management.php"><i class="fas fa-user-friends me-1"></i> Quản lý khách hàng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/order_management.php"><i class="fas fa-shopping-cart me-1"></i> Quản lý đơn hàng</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/model-shop/pages/products.php"><i class="fas fa-th-large me-1"></i> Sản phẩm</a>
                    </li>
                    <?php if ($user['Role'] === 'Customer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/cart.php"><i class="fas fa-shopping-cart me-1"></i> Giỏ hàng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/model-shop/pages/my_orders.php"><i class="fas fa-list-alt me-1"></i> Đơn hàng của tôi</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/model-shop/pages/account.php"><i class="fas fa-user me-1"></i> Tài khoản</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-user-circle me-1"></i> <?php echo $user['FullName'] . ' (' . $user['Role'] . ')'; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/model-shop/pages/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Đăng xuất</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/model-shop/pages/login.php"><i class="fas fa-sign-in-alt me-1"></i> Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/model-shop/pages/register.php"><i class="fas fa-user-plus me-1"></i> Đăng ký</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>