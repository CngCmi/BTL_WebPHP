<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Customer') {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';
$user_id = $_SESSION['user']['UserID'];

// Lấy danh sách sản phẩm trong giỏ hàng
$sql = "SELECT c.*, p.ProductName, p.Price, p.Image 
        FROM Cart c 
        JOIN Products p ON c.ProductID = p.ProductID 
        WHERE c.UserID = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$cart_items = $stmt->fetchAll();

// Xử lý cập nhật số lượng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        try {
            $sql = "UPDATE Cart SET Quantity = :quantity WHERE CartID = :cart_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['quantity' => $quantity, 'cart_id' => $cart_id]);
            header("Location: cart.php?success=Cập nhật giỏ hàng thành công!");
            exit();
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// Xử lý xóa sản phẩm
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    try {
        $sql = "DELETE FROM Cart WHERE CartID = :cart_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['cart_id' => $cart_id]);
        header("Location: cart.php?success=Xóa sản phẩm thành công!");
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
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
    <title>Giỏ hàng - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Giỏ hàng</h2>

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
                        <th>Hành động</th>
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
                                <td>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['CartID']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['Quantity']; ?>" min="1" class="form-control d-inline w-50" required>
                                        <button type="submit" name="update_quantity" class="btn btn-success btn-sm ms-2"><i class="fas fa-sync me-1"></i>Cập nhật</button>
                                    </form>
                                </td>
                                <td><?php echo number_format($item['Price'] * $item['Quantity'], 0, ',', '.'); ?></td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['CartID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')"><i class="fas fa-trash-alt me-1"></i>Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Giỏ hàng của bạn trống.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Tổng cộng:</strong></td>
                        <td><strong><?php echo number_format($total, 0, ',', '.'); ?> VNĐ</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (count($cart_items) > 0): ?>
            <div class="text-end mt-3">
                <a href="/model-shop/pages/checkout.php" class="btn btn-primary btn-lg"><i class="fas fa-shopping-bag me-2"></i>Thanh toán</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>