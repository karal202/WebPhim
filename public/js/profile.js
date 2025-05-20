document.addEventListener('DOMContentLoaded', () => {
    console.log('profile.js loaded - Version: ' + new Date().getTime());

    const moviesPerPage = 10;
    let bookmarkedMovies = [];
    let watchHistory = [];
    let filteredMovies = []; // Thêm biến mới để lưu danh sách phim lọc
    let currentPageBookmarks = 1;
    let currentPageHistory = 1;
    let currentPageFiltered = 1; // Thêm biến phân trang cho danh sách phim lọc

    checkSession();

    function setupMenuEvents() {
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const filter = link.getAttribute('data-filter');
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
                    const href = link.getAttribute('href');
                    if (href && href !== '#') {
                        window.location.href = href;
                    }
                }
            });
        });
    }

    function checkSession() {
        const formData = new FormData();
        formData.append('action', 'check_session');

        fetch('http://localhost/WebPhim/api/auth.php', {
            method: 'POST',
            body: formData
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
            if (data.success) {
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('username', data.username);
                localStorage.setItem('userId', data.user_id);
                localStorage.setItem('role', data.role);
                updateNav(true, data.username, data.role);
                loadUserProfile(data);
                setupMenuEvents();
                loadFilteredMovies(); // Gọi hàm mới để tải phim lọc
            } else {
                localStorage.removeItem('isLoggedIn');
                localStorage.removeItem('username');
                localStorage.removeItem('userId');
                localStorage.removeItem('role');
                updateNav(false);
                alert('Vui lòng đăng nhập để xem thông tin cá nhân!');
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error('Lỗi kiểm tra phiên:', error);
            updateNav(false);
            alert('Có lỗi xảy ra khi kiểm tra phiên. Vui lòng đăng nhập lại! Chi tiết lỗi: ' + error.message);
            window.location.href = 'login.html';
        });
    }

    // Hàm mới để tải phim lọc dựa trên URL params
    function loadFilteredMovies() {
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
            if (filterType === 'recommended') {
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
        }

        if (url) {
            const moviesTitle = document.getElementById('movies-title');
            if (moviesTitle) moviesTitle.textContent = title;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Phản hồi từ server không thành công: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    filteredMovies = data || [];
                    displayMovies('filtered-movies', filteredMovies, currentPageFiltered, 'filtered-movies-pagination');
                })
                .catch(error => {
                    console.error('Lỗi tải phim:', error);
                    const filteredMoviesContainer = document.getElementById('filtered-movies');
                    if (filteredMoviesContainer) {
                        filteredMoviesContainer.innerHTML = `<p>Có lỗi xảy ra khi tải phim: ${error.message}</p>`;
                    }
                });
        }
    }
    function updateNav(isLoggedIn, username, role) {
        const userNav = document.getElementById('userNav');
        if (!userNav) return;

        if (isLoggedIn) {
            userNav.innerHTML = `
                <li class="nav-item">
                    <a class="nav-link" href="profile.html"><i class="fas fa-user"></i> Xin chào, ${username} (${role})</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </li>
            `;
            document.getElementById('logoutLink').addEventListener('click', () => {
                const formData = new FormData();
                formData.append('action', 'logout');

                fetch('http://localhost/WebPhim/api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Phản hồi từ server không thành công: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        localStorage.removeItem('isLoggedIn');
                        localStorage.removeItem('username');
                        localStorage.removeItem('userId');
                        localStorage.removeItem('role');
                        alert(data.message);
                        window.location.href = 'login.html';
                    }
                })
                .catch(error => {
                    console.error('Lỗi đăng xuất:', error);
                    alert('Có lỗi xảy ra khi đăng xuất! Chi tiết lỗi: ' + error.message);
                });
            });
        } else {
            userNav.innerHTML = `
                <li class="nav-item">
                    <a class="nav-link" href="login.html"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="register.html"><i class="fas fa-user-plus"></i> Đăng ký</a>
                </li>
            `;
        }
        setupMenuEvents(); 
    }

    function loadUserProfile(userData) {
        document.getElementById('user-name').textContent = userData.username || 'Người dùng';
        document.getElementById('user-role').innerHTML = `Vai trò: <span>${userData.role || 'Người dùng'}</span>`;
        document.getElementById('user-username').textContent = userData.username || 'N/A';
        document.getElementById('user-email').textContent = userData.email || 'N/A';
        document.getElementById('user-joined').textContent = userData.created_at || 'N/A';
        document.getElementById('user-role-info').textContent = userData.role || 'Người dùng';

        const avatar = userData.avatar || 'https://via.placeholder.com/150';
        document.getElementById('user-avatar').src = avatar + '?t=' + new Date().getTime();

        // Xử lý tải ảnh từ máy
        const avatarInput = document.getElementById('avatar-input');
        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('action', 'update_avatar');
                formData.append('avatar', file);

                fetch('http://localhost/WebPhim/api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Phản hồi từ server không thành công: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('user-avatar').src = data.avatar + '?t=' + new Date().getTime();
                        alert(data.message);
                        const modal = bootstrap.Modal.getInstance(document.getElementById('avatarModal'));
                        modal.hide();
                        checkSession();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Lỗi cập nhật avatar:', error);
                    alert('Có lỗi xảy ra khi cập nhật avatar: ' + error.message);
                });
            }
        });

        loadBookmarkedMovies();
        loadWatchHistory();
    }

    function displayMovies(containerId, movies, currentPage, paginationId) {
        const movieContainer = document.getElementById(containerId);
        const paginationContainer = document.getElementById(paginationId);

        const totalMovies = movies.length;
        const totalPages = Math.ceil(totalMovies / moviesPerPage);
        const startIndex = (currentPage - 1) * moviesPerPage;
        const endIndex = startIndex + moviesPerPage;
        const paginatedMovies = movies.slice(startIndex, endIndex);

        movieContainer.innerHTML = '';
        if (totalMovies === 0) {
            movieContainer.innerHTML = `<p>Chưa có ${containerId === 'bookmarked-movies' ? 'phim nào được lưu' : 'lịch sử xem phim'}.</p>`;
            paginationContainer.innerHTML = '';
            return;
        }

        paginatedMovies.forEach(movie => {
            const genresDisplay = Array.isArray(movie.genres) ? movie.genres.join(', ') : (movie.genres || 'Không có');
            const countriesDisplay = Array.isArray(movie.countries) ? movie.countries.join(', ') : (movie.countries || 'Không có');
            const actorsDisplay = Array.isArray(movie.actors) ? movie.actors.join(', ') : (movie.actors || 'Không có');

            const movieItem = document.createElement('div');
            movieItem.className = 'movie-item';
            movieItem.innerHTML = `
                <div class="movie-content">
                    <img src="${movie.thumbnail || 'https://via.placeholder.com/300x450'}" alt="${movie.title}">
                    <div class="movie-overlay">
                        <p><strong>Thể loại:</strong> ${genresDisplay}</p>
                        <p><strong>Xuất xứ:</strong> ${countriesDisplay}</p>
                        <p><strong>Diễn viên:</strong> ${actorsDisplay}</p>
                    </div>
                    ${containerId === 'watch-history' ? '<button class="delete-btn">Xóa</button>' : ''}
                </div>
                <h5>${movie.title}</h5>
            `;
            movieItem.addEventListener('click', (e) => {
                if (!e.target.classList.contains('delete-btn')) {
                    window.location.href = `movie-detail.html?id=${movie.id}`;
                }


            });

            if (containerId === 'watch-history') {
                const deleteBtn = movieItem.querySelector('.delete-btn');
                deleteBtn.addEventListener('click', () => {
                    const userId = localStorage.getItem('userId');
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('user_id', userId);
                    formData.append('movie_id', movie.id);

                    fetch('http://localhost/WebPhim/api/history.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Phản hồi từ server không thành công: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            watchHistory = watchHistory.filter(m => m.id !== movie.id);
                            displayMovies('watch-history', watchHistory, currentPageHistory, 'watch-history-pagination');
                            alert(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi xóa phim khỏi lịch sử xem:', error);
                        alert('Có lỗi xảy ra khi xóa phim khỏi lịch sử xem: ' + error.message);
                    });
                });
            }

            movieContainer.appendChild(movieItem);
        });

        paginationContainer.innerHTML = '';
        if (totalPages > 1) {
            const prevItem = document.createElement('li');
            prevItem.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevItem.innerHTML = `<a class="page-link" href="#">Previous</a>`;
            prevItem.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    if (containerId === 'bookmarked-movies') {
                        currentPageBookmarks = currentPage;
                        displayMovies('bookmarked-movies', bookmarkedMovies, currentPageBookmarks, 'bookmarked-movies-pagination');
                    } else {
                        currentPageHistory = currentPage;
                        displayMovies('watch-history', watchHistory, currentPageHistory, 'watch-history-pagination');
                    }
                }
            });
            paginationContainer.appendChild(prevItem);

            for (let i = 1; i <= totalPages; i++) {
                const pageItem = document.createElement('li');
                pageItem.className = `page-item ${currentPage === i ? 'active' : ''}`;
                pageItem.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                pageItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    if (containerId === 'bookmarked-movies') {
                        currentPageBookmarks = currentPage;
                        displayMovies('bookmarked-movies', bookmarkedMovies, currentPageBookmarks, 'bookmarked-movies-pagination');
                    } else {
                        currentPageHistory = currentPage;
                        displayMovies('watch-history', watchHistory, currentPageHistory, 'watch-history-pagination');
                    }
                });
                paginationContainer.appendChild(pageItem);
            }

            const nextItem = document.createElement('li');
            nextItem.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextItem.innerHTML = `<a class="page-link" href="#">Next</a>`;
            nextItem.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    if (containerId === 'bookmarked-movies') {
                        currentPageBookmarks = currentPage;
                        displayMovies('bookmarked-movies', bookmarkedMovies, currentPageBookmarks, 'bookmarked-movies-pagination');
                    } else {
                        currentPageHistory = currentPage;
                        displayMovies('watch-history', watchHistory, currentPageHistory, 'watch-history-pagination');
                    }
                }
            });
            paginationContainer.appendChild(nextItem);
        }
    }

    function loadBookmarkedMovies() {
        const userId = localStorage.getItem('userId');
        if (!userId) {
            document.getElementById('bookmarked-movies').innerHTML = '<p>Vui lòng đăng nhập để xem danh sách phim đã lưu.</p>';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'get');

        fetch('http://localhost/WebPhim/api/bookmark.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Phản hồi từ server không thành công: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data.length > 0) {
                bookmarkedMovies = data.data;
            } else {
                bookmarkedMovies = [];
            }
            displayMovies('bookmarked-movies', bookmarkedMovies, currentPageBookmarks, 'bookmarked-movies-pagination');
        })
        .catch(error => {
            console.error('Lỗi tải phim đã lưu:', error);
            document.getElementById('bookmarked-movies').innerHTML = '<p>Có lỗi xảy ra khi tải phim đã lưu: ' + error.message + '</p>';
        });
    }

    function loadWatchHistory() {
        const userId = localStorage.getItem('userId');
        if (!userId) {
            document.getElementById('watch-history').innerHTML = '<p>Vui lòng đăng nhập để xem lịch sử xem phim.</p>';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'get');
        formData.append('user_id', userId);

        fetch('http://localhost/WebPhim/api/history.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Phản hồi từ server không thành công: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data.length > 0) {
                watchHistory = data.data;
            } else {
                watchHistory = [];
            }
            displayMovies('watch-history', watchHistory, currentPageHistory, 'watch-history-pagination');
        })
        .catch(error => {
            console.error('Lỗi tải lịch sử xem phim:', error);
            document.getElementById('watch-history').innerHTML = '<p>Có lỗi xảy ra khi tải lịch sử xem phim: ' + error.message + '</p>';
        });
    }

    const deleteAllBtn = document.getElementById('delete-all-history');
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', () => {
            if (confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử xem?')) {
                const userId = localStorage.getItem('userId');
                const formData = new FormData();
                formData.append('action', 'delete_all');
                formData.append('user_id', userId);

                fetch('http://localhost/WebPhim/api/history.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Phản hồi từ server không thành công: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        watchHistory = [];
                        displayMovies('watch-history', watchHistory, currentPageHistory, 'watch-history-pagination');
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Lỗi xóa toàn bộ lịch sử xem:', error);
                    alert('Có lỗi xảy ra khi xóa toàn bộ lịch sử xem: ' + error.message);
                });
            }
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
});