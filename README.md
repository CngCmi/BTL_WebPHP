# Model Shop (PHP Version)

Dự án web bán mô hình sử dụng PHP thuần, HTML, CSS, và Bootstrap.

## Yêu cầu
- XAMPP (Apache và MySQL)

## Cài đặt
1. Sao chép thư mục `model-shop` vào `D:\vscode\xampp\htdocs\`.
2. Tạo cơ sở dữ liệu `model_shop` trong phpMyAdmin và import các bảng từ SQL.
3. Khởi động XAMPP (Apache và MySQL).
4. Truy cập `http://localhost/model-shop`.

## Cấu trúc
- `config/db.php`: Kết nối cơ sở dữ liệu.
- `pages/`: Các trang chính (đăng nhập, đăng ký, sản phẩm, v.v.).
- `includes/`: Header, footer, navbar chung.

## Cấu trúc file
model-shop/
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── uploads/
│
├── config/
│   └── db.php
│
├── includes/
│   ├── footer.php
│   ├── header.php
│   └── navbar.php
│
├── pages/
│   ├── account.php
│   ├── admin.php
│   ├── cart.php
│   ├── checkout.php
│   ├── customer_management.php
│   ├── employee.php
│   ├── generate_invoice.php
│   ├── login.php
│   ├── logout.php
│   ├── my_orders.php
│   ├── order_management.php
│   ├── products.php
│   ├── register.php
│   ├── reports.php
│   └── user_management.php
│
├── index.php
└── README.md
