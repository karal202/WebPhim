document.addEventListener('DOMContentLoaded', () => {
    // Kiểm tra trạng thái đăng nhập từ server
    checkSession();

    // Thêm sự kiện cho các mục menu
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
            body: formData,
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Phản hồi từ server không thành công: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('username', data.username);
                localStorage.setItem('userId', data.user_id);
                localStorage.setItem('role', data.role);
                localStorage.setItem('email', data.email || 'N/A');
                localStorage.setItem('created_at', data.created_at || 'N/A');
                localStorage.setItem('avatar', data.avatar || 'https://via.placeholder.com/150');
                updateNav(true, data.username, data.role);
            } else {
                localStorage.removeItem('isLoggedIn');
                localStorage.removeItem('username');
                localStorage.removeItem('userId');
                localStorage.removeItem('role');
                localStorage.removeItem('email');
                localStorage.removeItem('created_at');
                localStorage.removeItem('avatar');
                updateNav(false);
            }
            loadMovieDetails();
            setupMenuEvents();
        })
        .catch(error => {
            console.error('Lỗi kiểm tra phiên:', error);
            updateNav(false);
            loadMovieDetails();
            setupMenuEvents();
        });
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
                    body: formData,
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        localStorage.removeItem('isLoggedIn');
                        localStorage.removeItem('username');
                        localStorage.removeItem('userId');
                        localStorage.removeItem('role');
                        localStorage.removeItem('email');
                        localStorage.removeItem('created_at');
                        localStorage.removeItem('avatar');
                        alert(data.message);
                        window.location.href = 'login.html';
                    }
                })
                .catch(error => console.error('Lỗi đăng xuất:', error));
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

    function loadMovieDetails() {
        const urlParams = new URLSearchParams(window.location.search);
        const movieId = urlParams.get('id');
        const movieTitleElement = document.getElementById('movie-title');
        const videoPlayer = document.getElementById('video-player');
    
        if (!movieTitleElement) {
            console.error('Không tìm thấy phần tử movie-title trong HTML.');
            return;
        }
    
        if (!videoPlayer) {
            console.error('Không tìm thấy phần tử video-player trong HTML.');
            return;
        }
    
        // Validate movieId
        if (!movieId || isNaN(movieId) || parseInt(movieId) <= 0) {
            movieTitleElement.textContent = 'ID phim không hợp lệ';
            videoPlayer.innerHTML = '<p>Không có video để hiển thị.</p>';
            return;
        }
    
        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        const userId = localStorage.getItem('userId');
    
        // Nhúng video nếu người dùng đã đăng nhập
        if (isLoggedIn) {
            fetch(`http://localhost/WebPhim/api/movie.php?action=get&id=${movieId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.video_path) {
                        const videoUrl = `http://localhost/WebPhim/${data.video_path}`;
                        videoPlayer.innerHTML = `
                            <video controls>
                                <source src="${videoUrl}" type="video/mp4">
                                Trình duyệt của bạn không hỗ trợ thẻ video.
                            </video>
                        `;
                        // Ghi lịch sử xem phim
                        const formData = new FormData();
                        formData.append('action', 'add');
                        formData.append('user_id', userId);
                        formData.append('movie_id', movieId);
    
                        fetch('http://localhost/WebPhim/api/history.php', {
                            method: 'POST',
                            body: formData,
                            credentials: 'include'
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                console.log('Đã ghi lịch sử xem phim:', result.message);
                            } else {
                                console.error('Lỗi ghi lịch sử xem phim:', result.message);
                            }
                        })
                        .catch(error => console.error('Lỗi khi ghi lịch sử xem phim:', error));
                    } else {
                        videoPlayer.innerHTML = '<p>Không có video để hiển thị.</p>';
                    }
                })
                .catch(error => {
                    console.error('Lỗi tải video:', error);
                    videoPlayer.innerHTML = '<p>Có lỗi xảy ra khi tải video!</p>';
                });
        } else {
            videoPlayer.innerHTML = '<p>Vui lòng đăng nhập để xem phim!</p>';
        }
    
        // Gán link cho nút "Đặt Lịch Xem Phim"
        const scheduleButton = document.getElementById('schedule-button');
        if (scheduleButton) {
            scheduleButton.href = `schedule.html?movieId=${movieId}`;
            scheduleButton.addEventListener('click', (e) => {
                if (!isLoggedIn) {
                    e.preventDefault();
                    alert('Vui lòng đăng nhập để đặt lịch xem phim!');
                    window.location.href = 'login.html';
                }
            });
        }
    
        // Xử lý nút "Lưu phim"
        const bookmarkButton = document.getElementById('bookmark-button');
        if (bookmarkButton) {
            if (isLoggedIn) {
                const formData = new FormData();
                formData.append('action', 'get');
    
                fetch('http://localhost/WebPhim/api/bookmark.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const isBookmarked = data.data.some(movie => movie.id == movieId);
                        if (isBookmarked) {
                            bookmarkButton.classList.add('saved');
                            bookmarkButton.innerHTML = '<i class="fas fa-bookmark"></i> Đã Lưu';
                        }
                    }
                })
                .catch(error => console.error('Lỗi kiểm tra trạng thái lưu phim:', error));
            }
    
            bookmarkButton.addEventListener('click', () => {
                if (!isLoggedIn) {
                    alert('Vui lòng đăng nhập để lưu phim!');
                    window.location.href = 'login.html';
                    return;
                }
    
                const isSaved = bookmarkButton.classList.contains('saved');
                const formData = new FormData();
                formData.append('action', isSaved ? 'remove' : 'add');
                formData.append('movie_id', movieId);
    
                fetch('http://localhost/WebPhim/api/bookmark.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (isSaved) {
                            bookmarkButton.classList.remove('saved');
                            bookmarkButton.innerHTML = '<i class="fas fa-bookmark"></i> Lưu phim';
                        } else {
                            bookmarkButton.classList.add('saved');
                            bookmarkButton.innerHTML = '<i class="fas fa-bookmark"></i> Đã Lưu';
                        }
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                    alert('Đã có lỗi xảy ra!');
                });
            });
        }
    
        // Xử lý nút "Chia sẻ"
        const shareButton = document.getElementById('share-button');
        if (shareButton) {
            shareButton.addEventListener('click', () => {
                const url = window.location.href;
                navigator.clipboard.writeText(url).then(() => {
                    alert('Đã sao chép liên kết phim để chia sẻ!');
                }).catch(err => {
                    console.error('Lỗi sao chép liên kết:', err);
                    alert('Không thể sao chép liên kết. Vui lòng thử lại!');
                });
            });
        }
    
        // Tải chi tiết phim
        fetch(`http://localhost/WebPhim/api/movie.php?action=get&id=${movieId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(movie => {
            if (movie.error) {
                movieTitleElement.textContent = 'Không tìm thấy phim';
                return;
            }
    
            const movieThumbnail = document.getElementById('movieThumbnail');
            if (movieThumbnail) {
                movieThumbnail.src = movie.thumbnail || 'https://via.placeholder.com/300x450';
                movieThumbnail.alt = movie.title || 'Không có tiêu đề';
            }
    
            movieTitleElement.textContent = movie.title || 'Không có tiêu đề';
    
            const releaseYear = document.getElementById('movie-release-year');
            if (releaseYear) releaseYear.textContent = movie.release_year || 'N/A';
    
            const status = document.getElementById('movie-status');
            if (status) status.textContent = movie.status || 'Không rõ trạng thái';
    
            const actors = document.getElementById('movie-actors');
            if (actors) actors.textContent = movie.actors || 'Không có thông tin diễn viên';
    
            const duration = document.getElementById('movie-duration');
            if (duration) duration.textContent = movie.duration || 'Không có thông tin thời lượng';
    
            const language = document.getElementById('movie-language');
            if (language) language.textContent = movie.language || 'Không có thông tin ngôn ngữ';
    
            const country = document.getElementById('movie-country');
            if (country) country.textContent = movie.countries || 'Không có thông tin quốc gia';
    
            const genre = document.getElementById('movie-genre');
            if (genre) genre.textContent = movie.genres || 'Không có thông tin thể loại';
    
            const description = document.getElementById('movie-description');
            if (description) description.textContent = movie.description || 'Không có mô tả';
    
            const views = document.getElementById('movie-views');
            if (views) views.textContent = movie.views || 0;
    
            const rating = movie.ratings || 0;
            const votes = movie.votes || 0;
    
            const ratings = document.getElementById('movie-ratings');
            if (ratings) ratings.textContent = rating;
    
            const ratingsDisplay = document.getElementById('movie-ratings-display');
            if (ratingsDisplay) ratingsDisplay.textContent = rating;
    
            const votesElement = document.getElementById('movie-votes');
            if (votesElement) votesElement.textContent = votes;
    
            const ratingStars = document.getElementById('rating-stars');
            if (ratingStars) {
                ratingStars.innerHTML = '';
                const fullStars = Math.floor(rating / 2);
                const halfStar = rating % 2 >= 1 ? 1 : 0;
                const emptyStars = 5 - fullStars - halfStar;
    
                for (let i = 0; i < fullStars; i++) {
                    ratingStars.innerHTML += '<i class="fas fa-star"></i>';
                }
                if (halfStar) {
                    ratingStars.innerHTML += '<i class="fas fa-star-half-alt"></i>';
                }
                for (let i = 0; i < emptyStars; i++) {
                    ratingStars.innerHTML += '<i class="far fa-star"></i>';
                }
            }
        })
        .catch(error => {
            console.error('Lỗi tải chi tiết phim:', error);
            movieTitleElement.textContent = 'Lỗi khi tải chi tiết phim: ' + error.message;
        });
        
        // Hàm tải danh sách phim gợi ý ngẫu nhiên
        function loadSuggestedMovies() {
            fetch('http://localhost/WebPhim/api/movie.php?action=get_all')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(movies => {
                    const suggestedCarousel = document.getElementById('suggested-carousel');
                    if (!suggestedCarousel) {
                        console.error('Không tìm thấy phần tử suggested-carousel trong HTML.');
                        return;
                    }

                    // Xáo trộn danh sách phim để chọn ngẫu nhiên
                    const shuffledMovies = movies.sort(() => 0.5 - Math.random());
                    // Lấy tối đa 10 phim
                    const suggestedMovies = shuffledMovies.slice(0, 10);

                    suggestedCarousel.innerHTML = ''; // Xóa nội dung cũ

                    suggestedMovies.forEach(movie => {
                        const movieItem = document.createElement('div');
                        movieItem.className = 'movie-item';
                        if (movie.is_trending) {
                            movieItem.classList.add('trending');
                        }

                        movieItem.innerHTML = `
                            <div class="movie-content">
                                <img src="${movie.thumbnail || 'https://via.placeholder.com/180x240'}" alt="${movie.title || 'Không có tiêu đề'}">
                                <div class="movie-overlay">
                                    <p><strong>Thể loại:</strong> ${movie.genres || 'N/A'}</p>
                                    <p><strong>Quốc gia:</strong> ${movie.countries || 'N/A'}</p>
                                    <p><strong>Thời lượng:</strong> ${movie.duration || 'N/A'}</p>
                                </div>
                                ${movie.is_trending ? '<span class="badge">Nổi bật</span>' : ''}
                            </div>
                            <h5>${movie.title || 'Không có tiêu đề'}</h5>
                        `;

                        // Thêm sự kiện click để chuyển hướng đến trang chi tiết phim
                        movieItem.addEventListener('click', () => {
                            window.location.href = `movie.html?id=${movie.id}`;
                        });

                        suggestedCarousel.appendChild(movieItem);
                    });

                    // Xử lý nút điều hướng cho carousel
                    const prevButton = document.querySelector('.carousel-arrow.prev[data-carousel="suggested-carousel"]');
                    const nextButton = document.querySelector('.carousel-arrow.next[data-carousel="suggested-carousel"]');

                    if (prevButton && nextButton) {
                        prevButton.addEventListener('click', () => {
                            suggestedCarousel.scrollBy({
                                left: -360,
                                behavior: 'smooth'
                            });
                        });

                        nextButton.addEventListener('click', () => {
                            suggestedCarousel.scrollBy({
                                left: 360,
                                behavior: 'smooth'
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Lỗi tải danh sách phim gợi ý:', error);
                    const suggestedCarousel = document.getElementById('suggested-carousel');
                    if (suggestedCarousel) {
                        suggestedCarousel.innerHTML = '<p>Lỗi khi tải danh sách phim gợi ý.</p>';
                    }
                });
        }

        // Gọi hàm tải danh sách phim gợi ý
        loadSuggestedMovies();

        // Xử lý kéo thả cho carousel
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

        // Hàm tải danh sách thể loại và quốc gia
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

        // Thêm sự kiện cho thanh tìm kiếm
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        if (searchForm && searchInput) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = `index.html?search=${encodeURIComponent(query)}`;
                }
            });
        }
    }
});