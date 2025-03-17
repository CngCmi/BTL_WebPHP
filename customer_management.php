<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Employee') {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = $search ? "WHERE FullName LIKE :search OR Phone LIKE :search" : "";
$search_param = $search ? "%$search%" : "";

// Xử lý phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số khách hàng
$sql_count = "SELECT COUNT(*) FROM Users WHERE Role = 'Customer' $where";
$stmt_count = $pdo->prepare($sql_count);
if ($search) {
    $stmt_count->bindParam(':search', $search_param, PDO::PARAM_STR);
}
$stmt_count->execute();
$total_customers = $stmt_count->fetchColumn();
$total_pages = ceil($total_customers / $limit);

// Xử lý cập nhật thông tin khách hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);

    $errors = [];
    if (empty($full_name)) $errors[] = "Họ và tên không được để trống.";
    if (empty($phone)) $errors[] = "Số điện thoại không được để trống.";

    if (empty($errors)) {
        try {
            $sql = "UPDATE Users SET FullName = :full_name, Phone = :phone WHERE UserID = :user_id AND Role = 'Customer'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'full_name' => $full_name,
                'phone' => $phone,
                'user_id' => $user_id
            ]);
            $success = "Cập nhật thông tin khách hàng thành công!";
            header("Location: customer_management.php?success=" . urlencode($success));
            exit();
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// Lấy danh sách khách hàng
$sql = "SELECT * FROM Users WHERE Role = 'Customer' $where LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
if ($search) {
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-user-friends me-2"></i>Quản lý khách hàng</h2>
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
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Bảng danh sách khách hàng -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ và tên</th>
                        <th>Số điện thoại</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customers) > 0): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['UserID']; ?></td>
                                <td><?php echo htmlspecialchars($customer['FullName']); ?></td>
                                <td><?php echo htmlspecialchars($customer['Phone']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#customerModal_<?php echo $customer['UserID']; ?>"><i class="fas fa-edit me-1"></i>Sửa</button>
                                </td>
                            </tr>

                            <!-- Modal chỉnh sửa khách hàng -->
                            <div class="modal fade" id="customerModal_<?php echo $customer['UserID']; ?>" tabindex="-1" aria-labelledby="customerModalLabel_<?php echo $customer['UserID']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="customerModalLabel_<?php echo $customer['UserID']; ?>">Sửa thông tin khách hàng</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="update_customer">
                                                <input type="hidden" name="user_id" value="<?php echo $customer['UserID']; ?>">
                                                <div class="mb-3">
                                                    <label for="full_name_<?php echo $customer['UserID']; ?>" class="form-label">Họ và tên</label>
                                                    <input type="text" class="form-control" id="full_name_<?php echo $customer['UserID']; ?>" name="full_name" value="<?php echo htmlspecialchars($customer['FullName']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="phone_<?php echo $customer['UserID']; ?>" class="form-label">Số điện thoại</label>
                                                    <input type="text" class="form-control" id="phone_<?php echo $customer['UserID']; ?>" name="phone" value="<?php echo htmlspecialchars($customer['Phone']); ?>" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Lưu</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Không tìm thấy khách hàng nào!</td>
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