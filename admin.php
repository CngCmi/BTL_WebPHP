<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['Role'], ['Admin', 'Employee'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php';

// Tạo thư mục uploads nếu chưa tồn tại
$upload_dir = '../assets/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Hàm xử lý upload hình ảnh
function uploadImage($file) {
    global $upload_dir;
    $target_file = $upload_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($imageFileType, $allowed_types)) {
        return false;
    }
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return 'assets/uploads/' . basename($file["name"]);
    }
    return false;
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = $search ? "WHERE p.ProductName LIKE :search" : "";
$search_param = $search ? "%$search%" : "";

// Xử lý phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số sản phẩm
$sql_count = "SELECT COUNT(*) FROM Products p JOIN Categories c ON p.CategoryID = c.CategoryID $where";
$stmt_count = $pdo->prepare($sql_count);
if ($search) {
    $stmt_count->bindParam(':search', $search_param, PDO::PARAM_STR);
}
$stmt_count->execute();
$total_products = $stmt_count->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    try {
        $sql = "DELETE FROM Products WHERE ProductID = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        header("Location: admin.php?success=Xóa sản phẩm thành công!");
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi khi xóa sản phẩm: " . $e->getMessage();
    }
}

// Xử lý thêm/sửa sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_product') {
    $product_name = trim($_POST['product_name']);
    $price = trim($_POST['price']);
    $quantity = trim($_POST['quantity']);
    $category_id = trim($_POST['category_id']);
    $status = trim($_POST['status']);
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['name']) {
        $image = uploadImage($_FILES['image']);
        if (!$image) {
            $errors[] = "Lỗi khi upload hình ảnh. Chỉ hỗ trợ định dạng jpg, jpeg, png, gif.";
        }
    } elseif ($product_id) {
        $sql = "SELECT Image FROM Products WHERE ProductID = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        $image = $stmt->fetchColumn();
    }

    $errors = [];
    if (empty($product_name)) $errors[] = "Tên sản phẩm không được để trống.";
    if (!is_numeric($price) || $price <= 0) $errors[] = "Giá phải là số dương.";
    if (!is_numeric($quantity) || $quantity < 0) $errors[] = "Số lượng phải là số không âm.";
    if (empty($category_id)) $errors[] = "Vui lòng chọn danh mục.";

    if (empty($errors)) {
        try {
            if ($product_id) {
                $sql = "UPDATE Products SET ProductName = :product_name, Price = :price, Quantity = :quantity, Image = :image, CategoryID = :category_id, Status = :status WHERE ProductID = :product_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'product_name' => $product_name,
                    'price' => $price,
                    'quantity' => $quantity,
                    'image' => $image,
                    'category_id' => $category_id,
                    'status' => $status,
                    'product_id' => $product_id
                ]);
                $success = "Cập nhật sản phẩm thành công!";
            } else {
                $sql = "INSERT INTO Products (ProductName, Price, Quantity, Image, CategoryID, Status) VALUES (:product_name, :price, :quantity, :image, :category_id, :status)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'product_name' => $product_name,
                    'price' => $price,
                    'quantity' => $quantity,
                    'image' => $image,
                    'category_id' => $category_id,
                    'status' => $status
                ]);
                $success = "Thêm sản phẩm thành công!";
            }
            header("Location: admin.php?success=" . urlencode($success));
            exit();
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// Lấy danh sách sản phẩm
$sql = "SELECT p.*, c.CategoryName FROM Products p JOIN Categories c ON p.CategoryID = c.CategoryID $where LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
if ($search) {
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Lấy danh sách danh mục
$categories = $pdo->query("SELECT * FROM Categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-boxes me-2"></i>Quản lý sản phẩm</h2>
            </div>
            <div class="col-md-6">
                <form method="GET" action="" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
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

        <!-- Nút thêm sản phẩm -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()"><i class="fas fa-plus me-1"></i>Thêm sản phẩm</button>

        <!-- Bảng danh sách sản phẩm -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá (VNĐ)</th>
                        <th>Số lượng</th>
                        <th>Hình ảnh</th>
                        <th>Danh mục</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['ProductID']; ?></td>
                                <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                                <td><?php echo number_format($product['Price'], 0, ',', '.'); ?></td>
                                <td><?php echo $product['Quantity']; ?></td>
                                <td>
                                    <?php if ($product['Image']): ?>
                                        <img src="../<?php echo htmlspecialchars($product['Image']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>" style="max-width: 50px;">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['CategoryName']); ?></td>
                                <td><?php echo htmlspecialchars($product['Status']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#productModal" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)"><i class="fas fa-edit me-1"></i>Sửa</button>
                                    <a href="admin.php?delete=<?php echo $product['ProductID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')"><i class="fas fa-trash-alt me-1"></i>Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Không tìm thấy sản phẩm nào!</td>
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

    <!-- Modal thêm/sửa sản phẩm -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Thêm sản phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="product_id" id="product_id">
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Tên sản phẩm</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Giá (VNĐ)</label>
                            <input type="number" class="form-control" id="price" name="price" step="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Số lượng</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" id="image" name="image">
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Danh mục</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['CategoryID']; ?>"><?php echo htmlspecialchars($category['CategoryName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Trạng thái</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="Còn hàng">Còn hàng</option>
                                <option value="Hết hàng">Hết hàng</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Lưu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.querySelector('#productModalLabel').innerText = 'Thêm sản phẩm';
            document.querySelector('form').reset();
            document.querySelector('#product_id').value = '';
        }

        function editProduct(product) {
            document.querySelector('#productModalLabel').innerText = 'Sửa sản phẩm';
            document.querySelector('#product_id').value = product.ProductID;
            document.querySelector('#product_name').value = product.ProductName;
            document.querySelector('#price').value = product.Price;
            document.querySelector('#quantity').value = product.Quantity;
            document.querySelector('#category_id').value = product.CategoryID;
            document.querySelector('#status').value = product.Status;
        }
    </script>
</body>
</html>