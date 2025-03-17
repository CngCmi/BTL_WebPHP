<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="mb-4"><i class="fas fa-store me-2"></i>Chào mừng đến với Cửa hàng mô hình</h1>
                    <p class="lead">Khám phá các mô hình tuyệt đẹp và mua sắm ngay hôm nay!</p>
                    <a href="/model-shop/pages/products.php" class="btn btn-primary btn-lg"><i class="fas fa-th-large me-2"></i>Xem sản phẩm</a>

                    <?php if (isset($_GET['orderID']) && isset($_SESSION['user']) && $_SESSION['user']['Role'] === 'Customer'): ?>
                        <div class="mt-3">
                            <a href="/model-shop/pages/generate_invoice.php?orderID=<?php echo $_GET['orderID']; ?>" class="btn btn-info"><i class="fas fa-print me-2"></i>In hóa đơn</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>