<?php
session_start();
session_destroy();
header("Location: /model-shop/index.php?success=Đăng xuất thành công!");
exit();
?>