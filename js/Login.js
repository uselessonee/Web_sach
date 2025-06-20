
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.querySelector('.user-dropdown');
    const userIconLink = document.querySelector('.user-icon-link');

    userIconLink.addEventListener('click', function(event) {
        event.preventDefault(); // Ngăn chặn hành vi mặc định của thẻ 'a'
        userDropdown.classList.toggle('active');
    });

            // Đóng dropdown khi click ra ngoài
    document.addEventListener('click', function(event) {
    if (!userDropdown.contains(event.target)) {
    userDropdown.classList.remove('active');
    }
});
});