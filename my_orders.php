<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Customer') {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';
$user_id = $_SESSION['user']['UserID'];

// Lấy danh sách đơn hàng của khách hàng
$sql = "SELECT * FROM Orders WHERE UserID = :user_id ORDER BY OrderDate DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4"><i class="fas fa-list-alt me-2"></i>Đơn hàng của tôi</h2>

        <!-- Bảng danh sách đơn hàng -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền (VNĐ)</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['OrderID']; ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($order['OrderDate'])); ?></td>
                                <td><?php echo number_format($order['Total'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($order['Status']); ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#orderDetailModal_<?php echo $order['OrderID']; ?>"><i class="fas fa-eye me-1"></i>Xem chi tiết</button>
                                    <a href="/model-shop/pages/generate_invoice.php?orderID=<?php echo $order['OrderID']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i>In hóa đơn</a>
                                </td>
                            </tr>

                            <!-- Modal xem chi tiết đơn hàng -->
                            <div class="modal fade" id="orderDetailModal_<?php echo $order['OrderID']; ?>" tabindex="-1" aria-labelledby="orderDetailModalLabel_<?php echo $order['OrderID']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="orderDetailModalLabel_<?php echo $order['OrderID']; ?>">Chi tiết đơn hàng #<?php echo $order['OrderID']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php
                                            $sql = "SELECT od.*, p.ProductName 
                                                    FROM OrderDetails od 
                                                    JOIN Products p ON od.ProductID = p.ProductID 
                                                    WHERE od.OrderID = :order_id";
                                            $stmt = $pdo->prepare($sql);
                                            $stmt->execute(['order_id' => $order['OrderID']]);
                                            $order_details = $stmt->fetchAll();
                                            ?>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Tên sản phẩm</th>
                                                        <th>Giá</th>
                                                        <th>Số lượng</th>
                                                        <th>Tổng</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($order_details as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                                                            <td><?php echo number_format($item['Price'], 0, ',', '.'); ?> VNĐ</td>
                                                            <td><?php echo $item['Quantity']; ?></td>
                                                            <td><?php echo number_format($item['Price'] * $item['Quantity'], 0, ',', '.'); ?> VNĐ</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Bạn chưa có đơn hàng nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>