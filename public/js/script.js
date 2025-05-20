document.addEventListener('DOMContentLoaded', () => {
    // Hàm tải danh sách phim vào carousel
    function loadMovies(carouselId, url, filter) {
        const carousel = document.getElementById(carouselId);
        if (!carousel) {
            console.error(`Không tìm thấy carousel với ID: ${carouselId}`);
            return;
        }


        carousel.innerHTML = '<div class="loading-spinner">Đang tải...</div>';

        console.log(`Fetching movies from: ${url}`); 
        fetch(url)
            .then(response => {
                console.log(`Response status for ${carouselId}: ${response.status}`); 
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`Data for ${carouselId}:`, data); 
                carousel.innerHTML = '';
                const section = carousel.closest('.movie-section');
                // Kiểm tra dữ liệu trả về từ API
                const movies = data.success ? data.data : data;
                if (!movies || movies.length === 0) {
                    section.classList.add('hidden'); // Ẩn section
                    return;
                }

                section.classList.remove('hidden');
                movies.slice(0, 10).forEach(movie => { // Hiển thị tối đa 10 phim trong carousel
                    const isTrending = filter === 'trending';
                    const movieItem = document.createElement('div');
                    movieItem.className = 'movie-item';
                    if (isTrending) {
                        movieItem.classList.add('trending');
                    }
                    movieItem.innerHTML = `
                        <div class="movie-content">
                            <img src="${movie.thumbnail || 'https://via.placeholder.com/300x450'}" alt="${movie.title}">
                            <div class="movie-overlay">
                                <p><strong>Thể loại:</strong> ${movie.genres || 'N/A'}</p>
                                <p><strong>Xuất xứ:</strong> ${movie.countries || 'N/A'}</p>
                                <p><strong>Diễn viên:</strong> ${movie.actors || 'N/A'}</p>
                            </div>
                            <span class="badge">${filter === 'scheduled' ? 'Có lịch chiếu' : 'Nổi bật'}</span>
                        </div>
                        <h5>${movie.title}</h5>
                    `;
                    movieItem.addEventListener('click', (e) => {
                        if (isDragging) { 
                            e.preventDefault();
                            return;
                        }
                        window.location.href = `movie-detail.html?id=${movie.id}`;
                    });
                    carousel.appendChild(movieItem);
                });
            })
            .catch(error => {
                console.error(`Lỗi tải phim cho ${carouselId}:`, error);
                const section = carousel.closest('.movie-section');
                section.classList.add('hidden'); 
            });
    }

    // Hàm ẩn/hiện các section
    function toggleSections(showSections) {
        const sections = {
            'recommended': document.querySelector('#recommended-carousel').closest('.movie-section'),
            'trending': document.querySelector('#trending-carousel').closest('.movie-section'),
            'thuyet-minh': document.querySelector('#thuyet-minh-carousel').closest('.movie-section'),
            'vietsub': document.querySelector('#vietsub-carousel').closest('.movie-section'),
            'theater': document.querySelector('#theater-carousel').closest('.movie-section')
        };

        Object.keys(sections).forEach(section => {
            if (showSections.includes(section)) {
                sections[section].style.display = 'block';
            } else {
                sections[section].style.display = 'none';
            }
        });
    }

    // Kiểm tra query string để lọc phim
    const urlParams = new URLSearchParams(window.location.search);
    const filterType = urlParams.get('filter');
    const filterValue = urlParams.get('value');
    const searchQuery = urlParams.get('search');

    if (filterType && filterValue) {
        if (filterType === 'genre') {
            loadMovies('recommended-carousel', `http://localhost/WebPhim/api/movie.php?action=get_by_genre&genre=${encodeURIComponent(filterValue)}`, 'recommended');
            toggleSections(['recommended']);
        } else if (filterType === 'country') {
            loadMovies('recommended-carousel', `http://localhost/WebPhim/api/movie.php?action=get_by_country&country=${encodeURIComponent(filterValue)}`, 'recommended');
            toggleSections(['recommended']);
        }
    } else if (filterType) {
        if (filterType === 'trending') {
            loadMovies('recommended-carousel', `http://localhost/WebPhim/api/movie.php?action=get_trending`, 'recommended');
            toggleSections(['recommended']);
        } else if (filterType === 'thuyet-minh') {
            loadMovies('recommended-carousel', `http://localhost/WebPhim/api/movie.php?action=get_thuyet_minh`, 'recommended');
            toggleSections(['recommended']);
        } else if (filterType === 'vietsub') {
            loadMovies('recommended-carousel', `http://localhost/WebPhim/api/movie.php?action=get_vietsub`, 'recommended');
            toggleSections(['recommended']);
        } else if (filterType === 'theater') {
            loadMovies('recommended-carousel', `http://localhost/WebPhim/api/movie.php?action=get_theater_movies`, 'recommended');
            toggleSections(['recommended']);
        }
    } else if (searchQuery) {
        loadMovies('recommended-carousel', `http://localhost/WebPhim/api/movie.php?action=search&query=${encodeURIComponent(searchQuery)}`, 'recommended');
        toggleSections(['recommended']);
    } else {
        // Mặc định hiển thị tất cả section
        loadMovies('recommended-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_all&random=true', 'recommended');
        loadMovies('trending-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_trending', 'trending');
        loadMovies('thuyet-minh-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_thuyet_minh', 'thuyetMinh');
        loadMovies('vietsub-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_vietsub', 'vietsub');
        loadMovies('theater-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_theater_movies', 'theater');
        toggleSections(['recommended', 'scheduled', 'trending', 'thuyet-minh', 'vietsub', 'theater']);
    }

    // Thêm sự kiện click vào tiêu đề của section
    document.querySelectorAll('.section-title').forEach(header => {
        header.addEventListener('click', () => {
            const section = header.closest('.movie-section');
            if (section.querySelector('#recommended-carousel')) {
                window.location.href = 'movies.html?filter=recommended';
            } else if (section.querySelector('#trending-carousel')) {
                window.location.href = 'movies.html?filter=trending';
            } else if (section.querySelector('#thuyet-minh-carousel')) {
                window.location.href = 'movies.html?filter=thuyet-minh';
            } else if (section.querySelector('#vietsub-carousel')) {
                window.location.href = 'movies.html?filter=vietsub';
            } else if (section.querySelector('#theater-carousel')) {
                window.location.href = 'movies.html?filter=theater';
            }
        });
    });

    // Thêm tính năng kéo chuột ngang cho carousel
    let isDragging = false;
    let startX;
    let scrollLeft;
    let currentCarousel;

    const carousels = document.querySelectorAll('.movie-carousel');
    carousels.forEach(carousel => {
        carousel.addEventListener('mousedown', (e) => {
            isDragging = true;
            currentCarousel = carousel;
            startX = e.pageX - carousel.offsetLeft;
            scrollLeft = carousel.scrollLeft;
            carousel.style.cursor = 'grabbing';
            e.preventDefault();
        });

        carousel.addEventListener('mousemove', (e) => {
            if (!isDragging || !currentCarousel) return;
            e.preventDefault();
            const x = e.pageX - currentCarousel.offsetLeft;
            const walk = (x - startX) * 1.5;
            currentCarousel.scrollLeft = scrollLeft - walk;
        });

        carousel.addEventListener('mouseup', () => {
            isDragging = false;
            if (currentCarousel) currentCarousel.style.cursor = 'grab';
            currentCarousel = null;
        });

        carousel.addEventListener('mouseleave', () => {
            isDragging = false;
            if (currentCarousel) currentCarousel.style.cursor = 'grab';
            currentCarousel = null;
        });

        carousel.addEventListener('click', (e) => {
            if (isDragging) e.preventDefault();
        });
    });

    // Thêm sự kiện cho các nút mũi tên
    const arrows = document.querySelectorAll('.carousel-arrow');
    arrows.forEach(arrow => {
        arrow.addEventListener('click', () => {
            const carouselId = arrow.getAttribute('data-carousel');
            const carousel = document.getElementById(carouselId);
            if (!carousel) return;

            const scrollAmount = 300;
            if (arrow.classList.contains('prev')) {
                carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            } else if (arrow.classList.contains('next')) {
                carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
        });
    });

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

    // Xử lý nút "TRANG CHỦ"
    const homeLink = document.querySelector('a[href="index.html"]');
    if (homeLink) {
        homeLink.addEventListener('click', (e) => {
            e.preventDefault();
            loadMovies('recommended-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_all&random=true', 'recommended');
            loadMovies('trending-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_trending', 'trending');
            loadMovies('thuyet-minh-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_thuyet_minh', 'thuyetMinh');
            loadMovies('vietsub-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_vietsub', 'vietsub');
            loadMovies('theater-carousel', 'http://localhost/WebPhim/api/movie.php?action=get_theater_movies', 'theater');
            toggleSections(['recommended', 'scheduled', 'trending', 'thuyet-minh', 'vietsub', 'theater']);
            window.history.pushState({}, document.title, 'index.html');
        });
    }

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

            if (role === 'admin') {
                alert('Bạn là admin! Bạn có thể quản lý lịch chiếu phim cũ.');
            } else {
                alert('Bạn là user! Bạn có thể đặt lịch xem phim cũ.');
            }
        }
    }
});