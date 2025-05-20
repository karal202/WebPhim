document.addEventListener('DOMContentLoaded', () => {
    function setupMenuEvents() {
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
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
    // Hàm tải danh sách thể loại và quốc gia
    function loadGenresAndCountries() {
        // Tải thể loại
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
                            window.location.href = `index.html?filter=genre&value=${encodeURIComponent(genre.name)}`;
                        });
                        li.appendChild(link);
                        genreMenu.appendChild(li);
                    });
                }
            })
            .catch(error => console.error('Lỗi tải thể loại:', error));

        // Tải quốc gia
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
                            window.location.href = `index.html?filter=country&value=${encodeURIComponent(country.name)}`;
                        });
                        li.appendChild(link);
                        countryMenu.appendChild(li);
                    });
                }
            })
            .catch(error => console.error('Lỗi tải quốc gia:', error));
    }
    loadGenresAndCountries();

    // Xử lý form đăng ký
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            if (password !== confirmPassword) {
                alert('Mật khẩu không khớp!');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);

            fetch('http://localhost/WebPhim/api/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    localStorage.setItem('isLoggedIn', 'true');
                    localStorage.setItem('username', data.username);
                    localStorage.setItem('role', data.role);
                    window.location.href = 'index.html';
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert('Có lỗi xảy ra khi đăng ký. Vui lòng thử lại!');
            });
        });
    }
});