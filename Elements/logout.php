<?php
session_start();
session_unset();    // Hủy tất cả các biến session
session_destroy();  // Hủy phiên làm việc

header('Location: ../Pages/index.php'); // Chuyển hướng về trang đăng nhập
exit();
?>