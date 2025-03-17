<?php
session_start();

// Kiểm tra nếu đã đăng nhập thì chuyển hướng
if (isset($_SESSION['user'])) {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    $errors = [];
    if (empty($full_name)) $errors[] = "Họ và tên không được để trống.";
    if (empty($phone)) $errors[] = "Số điện thoại không được để trống.";
    if (empty($password)) $errors[] = "Mật khẩu không được để trống.";
    if ($password !== $confirm_password) $errors[] = "Mật khẩu xác nhận không khớp.";

    if (empty($errors)) {
        try {
            // Kiểm tra số điện thoại đã tồn tại
            $sql = "SELECT COUNT(*) FROM Users WHERE Phone = :phone";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['phone' => $phone]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Số điện thoại đã được sử dụng.";
            } else {
                // Thêm người dùng mới với vai trò mặc định là Customer
                $sql = "INSERT INTO Users (FullName, Phone, Password, Role) VALUES (:full_name, :phone, :password, 'Customer')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'password' => $password // Lưu ý: Mật khẩu không mã hóa theo yêu cầu
                ]);
                header("Location: login.php?success=Đăng ký thành công! Vui lòng đăng nhập.");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center">
                        <h3><i class="fas fa-user-plus me-2"></i>Đăng ký</h3>
                    </div>
                    <div class="card-body">
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

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-user-plus me-2"></i>Đăng ký</button>
                        </form>
                        <p class="mt-3 text-center">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>