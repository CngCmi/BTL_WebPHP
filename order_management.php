<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['Role'], ['Admin', 'Employee'])) {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = $search ? "WHERE u.FullName LIKE :search OR u.Phone LIKE :search" : "";
$search_param = $search ? "%$search%" : "";

// Xử lý phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số đơn hàng
$sql_count = "SELECT COUNT(*) FROM Orders o JOIN Users u ON o.UserID = u.UserID $where";
$stmt_count = $pdo->prepare($sql_count);
if ($search) {
    $stmt_count->bindParam(':search', $search_param, PDO::PARAM_STR);
}
$stmt_count->execute();
$total_orders = $stmt_count->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = trim($_POST['status']);

    try {
        $sql = "UPDATE Orders SET Status = :status WHERE OrderID = :order_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['status' => $status, 'order_id' => $order_id]);
        header("Location: order_management.php?success=Cập nhật trạng thái đơn hàng thành công!");
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách đơn hàng
$sql = "SELECT o.*, u.FullName, u.Phone 
        FROM Orders o 
        JOIN Users u ON o.UserID = u.UserID $where 
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
if ($search) {
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-shopping-cart me-2"></i>Quản lý đơn hàng</h2>
            </div>
            <div class="col-md-6">
                <form method="GET" action="" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Tìm kiếm khách hàng..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
                </form>
            </div>
        </div>

        <!-- Thông báo -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Bảng danh sách đơn hàng -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
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
                                <td><?php echo htmlspecialchars($order['FullName']); ?></td>
                                <td><?php echo htmlspecialchars($order['Phone']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($order['OrderDate'])); ?></td>
                                <td><?php echo number_format($order['Total'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($order['Status']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#orderModal_<?php echo $order['OrderID']; ?>"><i class="fas fa-edit me-1"></i>Cập nhật trạng thái</button>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#orderDetailModal_<?php echo $order['OrderID']; ?>"><i class="fas fa-eye me-1"></i>Xem chi tiết</button>
                                </td>
                            </tr>

                            <!-- Modal cập nhật trạng thái -->
                            <div class="modal fade" id="orderModal_<?php echo $order['OrderID']; ?>" tabindex="-1" aria-labelledby="orderModalLabel_<?php echo $order['OrderID']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="orderModalLabel_<?php echo $order['OrderID']; ?>">Cập nhật trạng thái đơn hàng #<?php echo $order['OrderID']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">Trạng thái</label>
                                                    <select class="form-control" name="status" required>
                                                        <option value="Chờ xác nhận" <?php echo $order['Status'] == 'Chờ xác nhận' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                                        <option value="Đang xử lý" <?php echo $order['Status'] == 'Đang xử lý' ? 'selected' : ''; ?>>Đang xử lý</option>
                                                        <option value="Đang giao hàng" <?php echo $order['Status'] == 'Đang giao hàng' ? 'selected' : ''; ?>>Đang giao hàng</option>
                                                        <option value="Hoàn thành" <?php echo $order['Status'] == 'Hoàn thành' ? 'selected' : ''; ?>>Hoàn thành</option>
                                                        <option value="Đã hủy" <?php echo $order['Status'] == 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                                                    </select>
                                                </div>
                                                <button type="submit" name="update_status" class="btn btn-primary"><i class="fas fa-save me-1"></i>Lưu</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                            <td colspan="7" class="text-center">Không tìm thấy đơn hàng nào!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>