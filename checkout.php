<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Customer') {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';
$user_id = $_SESSION['user']['UserID'];

// Xử lý mua ngay (thêm sản phẩm vào giỏ hàng rồi chuyển đến checkout)
if (isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    $quantity = 1;

    try {
        $sql = "SELECT * FROM Cart WHERE UserID = :user_id AND ProductID = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id, 'product_id' => $product_id]);
        $cart_item = $stmt->fetch();

        if ($cart_item) {
            $sql = "UPDATE Cart SET Quantity = Quantity + :quantity WHERE CartID = :cart_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['quantity' => $quantity, 'cart_id' => $cart_item['CartID']]);
        } else {
            $sql = "INSERT INTO Cart (UserID, ProductID, Quantity) VALUES (:user_id, :product_id, :quantity)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id, 'product_id' => $product_id, 'quantity' => $quantity]);
        }
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách sản phẩm trong giỏ hàng
$sql = "SELECT c.*, p.ProductName, p.Price, p.Image 
        FROM Cart c 
        JOIN Products p ON c.ProductID = p.ProductID 
        WHERE c.UserID = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$cart_items = $stmt->fetchAll();

// Xử lý thanh toán
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['Price'] * $item['Quantity'];
    }

    try {
        $sql = "INSERT INTO Orders (UserID, Total, OrderDate, Status) VALUES (:user_id, :total, NOW(), 'Chờ xác nhận')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id, 'total' => $total]);
        $order_id = $pdo->lastInsertId();

        foreach ($cart_items as $item) {
            $sql = "INSERT INTO OrderDetails (OrderID, ProductID, Price, Quantity) VALUES (:order_id, :product_id, :price, :quantity)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'order_id' => $order_id,
                'product_id' => $item['ProductID'],
                'price' => $item['Price'],
                'quantity' => $item['Quantity']
            ]);

            // Cập nhật số lượng tồn kho
            $sql = "UPDATE Products SET Quantity = Quantity - :quantity WHERE ProductID = :product_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['quantity' => $item['Quantity'], 'product_id' => $item['ProductID']]);
        }

        // Xóa giỏ hàng sau khi đặt hàng
        $sql = "DELETE FROM Cart WHERE UserID = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);

        header("Location: index.php?orderID=" . $order_id . "&success=Đặt hàng thành công!");
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi khi đặt hàng: " . $e->getMessage();
    }
}

// Tính tổng tiền
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['Price'] * $item['Quantity'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4"><i class="fas fa-shopping-bag me-2"></i>Thanh toán</h2>

        <!-- Thông báo -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Danh sách sản phẩm trong giỏ hàng -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá (VNĐ)</th>
                        <th>Số lượng</th>
                        <th>Tổng (VNĐ)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($cart_items) > 0): ?>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['Image']): ?>
                                        <img src="../<?php echo htmlspecialchars($item['Image']); ?>" alt="<?php echo htmlspecialchars($item['ProductName']); ?>" style="max-width: 50px;">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                                <td><?php echo number_format($item['Price'], 0, ',', '.'); ?></td>
                                <td><?php echo $item['Quantity']; ?></td>
                                <td><?php echo number_format($item['Price'] * $item['Quantity'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Giỏ hàng của bạn trống.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Tổng cộng:</strong></td>
                        <td><strong><?php echo number_format($total, 0, ',', '.'); ?> VNĐ</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (count($cart_items) > 0): ?>
            <form method="POST" action="">
                <button type="submit" name="place_order" class="btn btn-primary btn-lg w-100"><i class="fas fa-check me-2"></i>Đặt hàng</button>
            </form>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>