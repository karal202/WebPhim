document.addEventListener("DOMContentLoaded", function () {
    // Kiểm tra trạng thái đăng nhập và vai trò admin
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const role = localStorage.getItem('role');
    const username = localStorage.getItem('username');

    // Kiểm tra nếu không đăng nhập hoặc không phải admin
    if (!isLoggedIn || role !== 'admin') {
        alert("Bạn không có quyền truy cập trang này!");
        window.location.href = "login.html";
        return;
    }

    // Hiển thị thông tin admin
    const userNav = document.getElementById('userNav');
    if (userNav) {
        userNav.innerHTML = `
            <li class="nav-item">
                <a class="nav-link" href="profile.html"><i class="fas fa-user"></i> Xin chào, ${username} (${role})</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </li>
        `;
    }

    console.log("Chào mừng admin:", username);
});

// Xử lý đăng xuất
document.getElementById("logoutBtn").addEventListener("click", function (e) {
    e.preventDefault();

    // Gửi yêu cầu đăng xuất đến server
    const formData = new FormData();
    formData.append('action', 'logout');

    fetch('http://localhost/WebPhim/api/auth.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Xóa thông tin người dùng trong localStorage
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('username');
            localStorage.removeItem('userId');
            localStorage.removeItem('role');
            alert(data.message);
            window.location.href = "login.html";
        } else {
            alert(data.message || 'Đăng xuất thất bại!');
        }
    })
    .catch(error => {
        console.error('Lỗi đăng xuất:', error);
        alert('Có lỗi xảy ra khi đăng xuất!');
    });
});