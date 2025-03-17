<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Admin') {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';

// Thống kê doanh thu
$sql = "SELECT SUM(Total) as TotalRevenue 
        FROM Orders 
        WHERE Status = 'Hoàn thành'";
$total_revenue = $pdo->query($sql)->fetchColumn();

// Thống kê số đơn hàng
$sql = "SELECT COUNT(*) as TotalOrders 
        FROM Orders";
$total_orders = $pdo->query($sql)->fetchColumn();

// Sản phẩm bán chạy nhất
$sql = "SELECT p.ProductName, SUM(od.Quantity) as TotalSold 
        FROM OrderDetails od 
        JOIN Products p ON od.ProductID = p.ProductID 
        JOIN Orders o ON od.OrderID = o.OrderID 
        WHERE o.Status = 'Hoàn thành' 
        GROUP BY p.ProductID 
        ORDER BY TotalSold DESC 
        LIMIT 5";
$top_products = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo & Thống kê - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4"><i class="fas fa-chart-line me-2"></i>Báo cáo & Thống kê</h2>

        <!-- Thống kê tổng quan -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title">Tổng doanh thu (Đã hoàn thành)</h5>
                        <h3 class="text-success"><?php echo number_format($total_revenue, 0, ',', '.'); ?> VNĐ</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title">Tổng số đơn hàng</h5>
                        <h3><?php echo $total_orders; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title">Số sản phẩm bán chạy</h5>
                        <h3><?php echo count($top_products); ?>/5</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sản phẩm bán chạy -->
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-trophy me-2"></i>Sản phẩm bán chạy nhất</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th>Số lượng bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($top_products) > 0): ?>
                            <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                                    <td><?php echo $product['TotalSold']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center">Chưa có sản phẩm nào được bán.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>