<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user'])) {
    header("Location: /model-shop/index.php");
    exit();
}

require_once '../config/db.php';
$user_id = $_SESSION['user']['UserID'];

// Xử lý cập nhật tài khoản
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    $errors = [];
    if (empty($full_name)) $errors[] = "Họ và tên không được để trống.";
    if (empty($phone)) $errors[] = "Số điện thoại không được để trống.";

    if (empty($errors)) {
        try {
            if (!empty($password)) {
                $sql = "UPDATE Users SET FullName = :full_name, Phone = :phone, Password = :password WHERE UserID = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'password' => $password,
                    'user_id' => $user_id
                ]);
            } else {
                $sql = "UPDATE Users SET FullName = :full_name, Phone = :phone WHERE UserID = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'user_id' => $user_id
                ]);
            }
            $_SESSION['user']['FullName'] = $full_name;
            $_SESSION['user']['Phone'] = $phone;
            $success = "Cập nhật tài khoản thành công!";
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// Lấy thông tin người dùng
$sql = "SELECT * FROM Users WHERE UserID = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản - Cửa hàng mô hình</title>
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
                        <h3><i class="fas fa-user me-2"></i>Quản lý tài khoản</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
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
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['FullName']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['Phone']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu mới (để trống nếu không thay đổi)</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Vai trò</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['Role']); ?>" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Cập nhật</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>