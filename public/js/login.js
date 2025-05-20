document.addEventListener('DOMContentLoaded', () => {
    console.log('login.js loaded - Version: ' + new Date().getTime());

    // Thêm sự kiện cho các mục menu
    function setupMenuEvents() {
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault(); // Ngăn hành vi mặc định của thẻ <a>
                const filter = link.getAttribute('data-filter');
                
                // Xử lý các mục menu có thuộc tính data-filter
                if (filter) {
                    switch(filter) {
                        case 'trending':
                            window.location.href = 'movies.html?filter=trending';
                            break;
                        case 'thuyet-minh':
                            window.location.href = 'movies.html?filter=thuyet-minh';
                            break;
                        case 'vietsub':
                            window.location.href = 'movies.html?filter=vietsub';
                            break;
                        case 'theater':
                            window.location.href = 'movies.html?filter=theater';
                            break;
                        default:
                            console.log('Filter không được hỗ trợ:', filter);
                    }
                } else {
                    // Xử lý các mục không có data-filter (như TRANG CHỦ, Đăng nhập, Đăng ký)
                    const href = link.getAttribute('href');
                    if (href && href !== '#') {
                        window.location.href = href;
                    }
                }
            });
        });
    }

    function loadGenresAndCountries() {
        fetch('http://localhost/WebPhim/api/genre.php?action=get_all')
            .then(response => response.json())
            .then(genres => {
                const genreMenu = document.getElementById('genreMenu');
                if (genreMenu) {
                    genreMenu.innerHTML = '';
                    genres.forEach(genre => {
                        const li = document.createElement('li');
                        const link = document.createElement('a');
                        link.className = 'dropdown-item';
                        link.href = '#';
                        link.setAttribute('data-genre', genre.name);
                        link.textContent = genre.name;
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            window.location.href = `movies.html?filter=genre&value=${encodeURIComponent(genre.name)}`;
                        });
                        li.appendChild(link);
                        genreMenu.appendChild(li);
                    });
                }
            })
            .catch(error => console.error('Lỗi tải thể loại:', error));

        fetch('http://localhost/WebPhim/api/country.php?action=get_all')
            .then(response => response.json())
            .then(countries => {
                const countryMenu = document.getElementById('countryMenu');
                if (countryMenu) {
                    countryMenu.innerHTML = '';
                    countries.forEach(country => {
                        const li = document.createElement('li');
                        const link = document.createElement('a');
                        link.className = 'dropdown-item';
                        link.href = '#';
                        link.setAttribute('data-country', country.name);
                        link.textContent = country.name;
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            window.location.href = `movies.html?filter=country&value=${encodeURIComponent(country.name)}`;
                        });
                        li.appendChild(link);
                        countryMenu.appendChild(li);
                    });
                }
            })
            .catch(error => console.error('Lỗi tải quốc gia:', error));
    }

    loadGenresAndCountries();
    setupMenuEvents(); // Gọi hàm để thiết lập sự kiện menu

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);

            fetch('http://localhost/WebPhim/api/auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include' // Đảm bảo gửi cookie/session nếu cần
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Phản hồi từ server không thành công: ' + response.status);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Phản hồi không phải JSON: ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                alert(data.message);
                if (data.success) {
                    // Lưu thông tin người dùng vào localStorage
                    localStorage.setItem('isLoggedIn', 'true');
                    localStorage.setItem('username', data.username);
                    localStorage.setItem('role', data.role);
                    localStorage.setItem('userId', data.user_id);

                    // Chuyển hướng dựa trên role
                    if (data.role.toLowerCase() === 'admin') {
                        window.location.href = 'admin.html';
                    } else {
                        window.location.href = 'index.html';
                    }
                }
            })
            .catch(error => {
                console.error('Lỗi đăng nhập:', error);
                alert('Có lỗi xảy ra khi đăng nhập. Vui lòng thử lại! Chi tiết lỗi: ' + error.message);
            });
        });
    }
});