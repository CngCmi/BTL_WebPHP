<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Customer') {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';

if (isset($_GET['orderID'])) {
    $order_id = (int)$_GET['orderID'];

    // Lấy thông tin đơn hàng
    $sql = "SELECT o.*, u.FullName, u.Phone 
            FROM Orders o 
            JOIN Users u ON o.UserID = u.UserID 
            WHERE o.OrderID = :order_id AND o.UserID = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['order_id' => $order_id, 'user_id' => $_SESSION['user']['UserID']]);
    $order = $stmt->fetch();

    // Lấy chi tiết đơn hàng
    $sql = "SELECT od.*, p.ProductName 
            FROM OrderDetails od 
            JOIN Products p ON od.ProductID = p.ProductID 
            WHERE od.OrderID = :order_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['order_id' => $order_id]);
    $order_details = $stmt->fetchAll();
} else {
    header("Location: /model-shop/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .invoice {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: white;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .invoice-footer {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <?php if ($order): ?>
            <div class="invoice shadow-sm">
                <div class="invoice-header">
                    <h2><i class="fas fa-receipt me-2"></i>Hóa đơn</h2>
                    <p>Ngày: <?php echo date('d/m/Y H:i:s', strtotime($order['OrderDate'])); ?></p>
                </div>
                <div class="card-body">
                    <h4>Thông tin khách hàng</h4>
                    <p><strong>Tên:</strong> <?php echo htmlspecialchars($order['FullName']); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['Phone']); ?></p>
                    <h4>Chi tiết đơn hàng</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Tên sản phẩm</th>
                                <th>Giá (VNĐ)</th>
                                <th>Số lượng</th>
                                <th>Tổng (VNĐ)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_details as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                                    <td><?php echo number_format($item['Price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['Quantity']; ?></td>
                                    <td><?php echo number_format($item['Price'] * $item['Quantity'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                <td><strong><?php echo number_format($order['Total'], 0, ',', '.'); ?> VNĐ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="invoice-footer">
                    <p>Cảm ơn bạn đã mua sắm tại Cửa hàng mô hình!</p>
                    <button class="btn btn-primary no-print" onclick="window.print()"><i class="fas fa-print me-2"></i>In hóa đơn</button>
                    <a href="/model-shop/index.php" class="btn btn-secondary no-print"><i class="fas fa-arrow-left me-2"></i>Quay lại</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Hóa đơn không tồn tại hoặc không có quyền truy cập!</div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>