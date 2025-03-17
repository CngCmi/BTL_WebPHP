<?php
session_start();
require_once '../config/db.php';

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = $search ? "WHERE p.ProductName LIKE :search" : "";
$search_param = $search ? "%$search%" : "";

// Xử lý phân trang
$limit = 9; // Số sản phẩm mỗi trang
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

// Xử lý thêm vào giỏ hàng
if (isset($_GET['add_to_cart'])) {
    $product_id = (int)$_GET['add_to_cart'];
    $quantity = 1;

    if (!isset($_SESSION['user'])) {
        header("Location: login.php?error=" . urlencode("Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!"));
        exit();
    }

    try {
        $sql = "SELECT * FROM Cart WHERE UserID = :user_id AND ProductID = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['user']['UserID'], 'product_id' => $product_id]);
        $cart_item = $stmt->fetch();

        if ($cart_item) {
            $sql = "UPDATE Cart SET Quantity = Quantity + :quantity WHERE CartID = :cart_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['quantity' => $quantity, 'cart_id' => $cart_item['CartID']]);
        } else {
            $sql = "INSERT INTO Cart (UserID, ProductID, Quantity) VALUES (:user_id, :product_id, :quantity)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $_SESSION['user']['UserID'], 'product_id' => $product_id, 'quantity' => $quantity]);
        }
        header("Location: products.php?success=" . urlencode("Thêm vào giỏ hàng thành công!"));
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý mua ngay
if (isset($_GET['buy_now'])) {
    $product_id = (int)$_GET['buy_now'];
    if (!isset($_SESSION['user'])) {
        header("Location: login.php?error=" . urlencode("Vui lòng đăng nhập để mua sản phẩm!"));
        exit();
    }
    header("Location: checkout.php?product_id=" . $product_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm - Cửa hàng mô hình</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-th-large me-2"></i>Danh sách sản phẩm</h2>
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
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Danh sách sản phẩm -->
        <div class="row">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card product-card">
                            <?php if ($product['Image']): ?>
                                <img src="../<?php echo htmlspecialchars($product['Image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                            <?php else: ?>
                                <img src="../assets/images/placeholder.jpg" class="card-img-top" alt="No image">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['ProductName']); ?></h5>
                                <p class="card-text">Giá: <?php echo number_format($product['Price'], 0, ',', '.'); ?> VNĐ</p>
                                <p class="card-text">Danh mục: <?php echo htmlspecialchars($product['CategoryName']); ?></p>
                                <p class="card-text">Trạng thái: <?php echo htmlspecialchars($product['Status']); ?></p>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#productDetailModal_<?php echo $product['ProductID']; ?>"><i class="fas fa-eye me-1"></i>Xem chi tiết</button>
                                    <?php if ($product['Status'] == 'Còn hàng'): ?>
                                        <a href="products.php?add_to_cart=<?php echo $product['ProductID']; ?>" class="btn btn-success btn-sm"><i class="fas fa-cart-plus me-1"></i>Thêm vào giỏ</a>
                                        <a href="products.php?buy_now=<?php echo $product['ProductID']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-shopping-bag me-1"></i>Mua ngay</a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>Hết hàng</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal xem chi tiết sản phẩm -->
                    <div class="modal fade" id="productDetailModal_<?php echo $product['ProductID']; ?>" tabindex="-1" aria-labelledby="productDetailModalLabel_<?php echo $product['ProductID']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="productDetailModalLabel_<?php echo $product['ProductID']; ?>">Chi tiết sản phẩm</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if ($product['Image']): ?>
                                        <img src="../<?php echo htmlspecialchars($product['Image']); ?>" class="img-fluid mb-3" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                                    <?php endif; ?>
                                    <p><strong>Tên sản phẩm:</strong> <?php echo htmlspecialchars($product['ProductName']); ?></p>
                                    <p><strong>Giá:</strong> <?php echo number_format($product['Price'], 0, ',', '.'); ?> VNĐ</p>
                                    <p><strong>Số lượng:</strong> <?php echo $product['Quantity']; ?></p>
                                    <p><strong>Danh mục:</strong> <?php echo htmlspecialchars($product['CategoryName']); ?></p>
                                    <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($product['Status']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">Không tìm thấy sản phẩm nào!</div>
                </div>
            <?php endif; ?>
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