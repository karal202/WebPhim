document.addEventListener('DOMContentLoaded', () => {
    const moviesPerPage = 18;
    let allMovies = [];
    let currentPage = 1;

    // Hàm hiển thị phim với phân trang
    function displayMovies() {
        const movieGrid = document.getElementById('movies-grid');
        const paginationContainer = document.getElementById('movies-pagination');

        // Tính toán phân trang
        const totalMovies = allMovies.length;
        const totalPages = Math.ceil(totalMovies / moviesPerPage);
        const startIndex = (currentPage - 1) * moviesPerPage;
        const endIndex = startIndex + moviesPerPage;
        const paginatedMovies = allMovies.slice(startIndex, endIndex);

        // Hiển thị phim
        movieGrid.innerHTML = '';
        if (totalMovies === 0) {
            movieGrid.innerHTML = '<p>Không có phim nào để hiển thị.</p>';
            paginationContainer.innerHTML = '';
            return;
        }

        paginatedMovies.forEach(movie => {
            const movieItem = document.createElement('div');
            movieItem.className = 'movie-item';
            movieItem.innerHTML = `
                <div class="movie-content">
                    <img src="${movie.thumbnail || 'https://via.placeholder.com/300x450'}" alt="${movie.title}">
                    <div class="movie-overlay">
                        <p><strong>Thể loại:</strong> ${movie.genres || 'N/A'}</p>
                        <p><strong>Xuất xứ:</strong> ${movie.countries || 'N/A'}</p>
                        <p><strong>Diễn viên:</strong> ${movie.actors || 'N/A'}</p>
                    </div>
                    <span class="badge">${filterType === 'scheduled' ? 'Có lịch chiếu' : 'Nổi bật'}</span>
                </div>
                <h5>${movie.title}</h5>
            `;
            movieItem.addEventListener('click', () => {
                window.location.href = `movie-detail.html?id=${movie.id}`;
            });
            movieGrid.appendChild(movieItem);
        });

        // Hiển thị phân trang
        paginationContainer.innerHTML = '';
        if (totalPages > 1) {
            // Nút Previous
            const prevItem = document.createElement('li');
            prevItem.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevItem.innerHTML = `<a class="page-link" href="#">Previous</a>`;
            prevItem.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    displayMovies();
                }
            });
            paginationContainer.appendChild(prevItem);

            // Các trang
            for (let i = 1; i <= totalPages; i++) {
                const pageItem = document.createElement('li');
                pageItem.className = `page-item ${currentPage === i ? 'active' : ''}`;
                pageItem.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                pageItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    displayMovies();
                });
                paginationContainer.appendChild(pageItem);
            }

            // Nút Next
            const nextItem = document.createElement('li');
            nextItem.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextItem.innerHTML = `<a class="page-link" href="#">Next</a>`;
            nextItem.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    displayMovies();
                }
            });
            paginationContainer.appendChild(nextItem);
        }
    }

    // Kiểm tra query string để tải phim
    const urlParams = new URLSearchParams(window.location.search);
    const filterType = urlParams.get('filter');
    const filterValue = urlParams.get('value');
    const searchQuery = urlParams.get('search');

    let url = '';
    let title = '';

    if (filterType && filterValue) {
        if (filterType === 'genre') {
            url = `http://localhost/WebPhim/api/movie.php?action=get_by_genre&genre=${encodeURIComponent(filterValue)}`;
            title = `Phim theo thể loại: ${filterValue}`;
        } else if (filterType === 'country') {
            url = `http://localhost/WebPhim/api/movie.php?action=get_by_country&country=${encodeURIComponent(filterValue)}`;
            title = `Phim theo quốc gia: ${filterValue}`;
        }
    } else if (filterType) {
        if (filterType === 'scheduled') {
            url = `http://localhost/WebPhim/api/schedule.php?action=get_movies_with_schedules`;
            title = 'Phim Có Lịch Chiếu';
        } else if (filterType === 'recommended') {
            url = `http://localhost/WebPhim/api/movie.php?action=get_all`;
            title = 'Phim Đề Cử';
        } else if (filterType === 'trending') {
            url = `http://localhost/WebPhim/api/movie.php?action=get_trending`;
            title = 'Phim Nổi Bật';
        } else if (filterType === 'thuyet-minh') {
            url = `http://localhost/WebPhim/api/movie.php?action=get_thuyet_minh`;
            title = 'Phim Thuyết Minh';
        } else if (filterType === 'vietsub') {
            url = `http://localhost/WebPhim/api/movie.php?action=get_vietsub`;
            title = 'Phim Vietsub';
        } else if (filterType === 'theater') {
            url = `http://localhost/WebPhim/api/movie.php?action=get_theater_movies`;
            title = 'Phim Đã Chiếu Rạp';
        }
    } else if (searchQuery) {
        url = `http://localhost/WebPhim/api/movie.php?action=search&query=${encodeURIComponent(searchQuery)}`;
        title = `Kết quả tìm kiếm: ${searchQuery}`;
    } else {
        url = `http://localhost/WebPhim/api/movie.php?action=get_all`;
        title = 'Tất Cả Phim';
    }

    if (url) {
        document.getElementById('movies-title').textContent = title;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                allMovies = data.success ? data.data : data; // Xử lý định dạng trả về từ API
                displayMovies();
            })
            .catch(error => {
                console.error('Lỗi tải phim:', error);
                document.getElementById('movies-title').textContent = 'Lỗi Tải Phim';
                document.getElementById('movies-grid').innerHTML = '<p>Đã xảy ra lỗi khi tải phim.</p>';
            });
    }

    // Thêm sự kiện cho thanh tìm kiếm
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `movies.html?search=${encodeURIComponent(query)}`;
            }
        });
    }

    // Tải danh sách thể loại và quốc gia
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

    // Xử lý các mục lọc trong menu
    const filterLinks = document.querySelectorAll('.nav-link[data-filter]');
    filterLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const filter = link.getAttribute('data-filter');
            window.location.href = `movies.html?filter=${filter}`;
        });
    });

    // Xử lý đăng nhập/đăng xuất
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const username = localStorage.getItem('username');
    const role = localStorage.getItem('role');

    if (isLoggedIn === 'true') {
        const userNav = document.getElementById('userNav');
        if (userNav) {
            userNav.innerHTML = `
                <li class="nav-item">
                    <a class="nav-link" href="profile.html"><i class="fas fa-user"></i> Xin chào, ${username} (${role})</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </li>
            `;
            document.getElementById('logoutLink').addEventListener('click', () => {
                localStorage.removeItem('isLoggedIn');
                localStorage.removeItem('username');
                localStorage.removeItem('role');
                localStorage.removeItem('email');
                window.location.href = 'login.html';
            });
        }
    }
});