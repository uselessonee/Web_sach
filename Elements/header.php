<?php
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa
$username = $_SESSION['username'] ?? 'Khách';
?>

 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<table cellpadding="5">
    <tr>
        <td>
            <img src="../images/logo/book.png" alt="" width="60">
        </td>
        <td>
            <a href="#" class="logo"
                style="font-size: 20px; font-weight: bold; text-decoration: none; color: black;">BOOK
                ONLINE</a>
        </td>
    </tr>
</table>

<input type="checkbox" name="" id="thanhcuon">
<label for="thanhcuon"  class="fa-bars fas"></label>

<div class="menu">
    <a href="../Pages/index.php">Trang Chủ</a>

    <div class="dropdown">
        <a href="#">Thể loại</a>
        <div class="dropdown-content">
            <table>
                <tr>
                    <td><a href="../The_loai/Truyen_ngan/truyenngan.html">Truyện ngắn</a></td>
                    <td><a href="">Ngôn tình</a></td>
                    <td><a href="../The_loai/Tieu_thuyet/tieuthuyet.html">Tiểu thuyết</a></td>
                </tr>
                <tr>
                    <td><a href="">Cổ tích</a></td>
                    <td><a href="">Truyện 3D</a></td>
                    <td><a href="">Kinh dị</a></td>
                </tr>
                <tr>
                    <td><a href="">Hành động & phiêu lưu</a></td>
                    <td><a href="">Lịch sử</a></td>
                    <td><a href="">Tâm lý</a></td>
                </tr>
            </table>
        </div>
    </div>

    <a href="#Sách điện tử">Sách điện tử</a>
    <a href="#Mua sắm">Về chúng tôi</a>
</div>

<div class="icons">
            <a href="../Pages/login.php" class="user">
                <img src="../images/logo/user.png" alt="user">
            </a>
            <div class="dropdown-content">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="#">Trang cá nhân</a> 
                    <a href="logout.php">Đăng xuất</a>
                <?php else: ?>
                    <a href="index.php">Đăng nhập</a>
                    <a href="register.php">Đăng ký</a>
                <?php endif; ?>
        </div>
    </div>
</div>
<script src="../js/Indexloader.js"></script>