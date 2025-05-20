document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM đã tải xong');

    // Lấy orderId từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('orderId');
    console.log('orderId:', orderId);

    // Validate orderId
    if (!orderId || isNaN(orderId) || orderId <= 0) {
        console.error('Invalid or missing orderId in URL:', orderId);
        window.location.href = 'index.html';
        return;
    }

    // Set orderId in form
    document.getElementById('orderId').value = orderId;

    // Kiểm tra trạng thái đăng nhập
    checkSession();

    function checkSession() {
        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        if (isLoggedIn) {
            updateNav(true, localStorage.getItem('username'), localStorage.getItem('role'));
            loadGenresAndCountries();
            loadOrderDetails();
            setupNavigationPrompt();
            return;
        }

        const formData = new FormData();
        formData.append('action', 'check_session');

        fetch('http://localhost/WebPhim/api/auth.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('username', data.username);
                localStorage.setItem('userId', data.user_id);
                localStorage.setItem('role', data.role);
                updateNav(true, data.username, data.role);
                loadGenresAndCountries();
                loadOrderDetails();
                setupNavigationPrompt();
            } else {
                localStorage.clear();
                window.location.href = `login.html?redirect=${encodeURIComponent(window.location.href)}`;
            }
        })
        .catch(error => {
            console.error('Lỗi kiểm tra phiên:', error);
            window.location.href = `login.html?redirect=${encodeURIComponent(window.location.href)}`;
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
            document.getElementById('logoutLink').addEventListener('click', (e) => {
                e.preventDefault();
                handleNavigation('login.html');
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
                            handleNavigation(`movies.html?filter=genre&value=${encodeURIComponent(genre.name)}`);
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
                            handleNavigation(`movies.html?filter=country&value=${encodeURIComponent(country.name)}`);
                        });
                        li.appendChild(link);
                        countryMenu.appendChild(li);
                    });
                }
            })
            .catch(error => console.error('Lỗi tải quốc gia:', error));
    }

    const elements = {
        movieThumbnail: document.getElementById('movieThumbnail'),
        invoiceMovie: document.getElementById('invoiceMovie'),
        invoiceShowTime: document.getElementById('invoiceShowTime'),
        invoiceRoom: document.getElementById('invoiceRoom'),
        invoiceSeats: document.getElementById('invoiceSeats'),
        invoiceServices: document.getElementById('invoiceServices'),
        invoiceTotal: document.getElementById('invoiceTotal'),
        paymentForm: document.getElementById('paymentForm'),
        amount: document.getElementById('amount'),
        bankCode: document.getElementById('bankCode'),
        orderId: document.getElementById('orderId'),
        paymentMethod: document.getElementById('paymentMethod')
    };

    function formatDateTime(date, time) {
        const [year, month, day] = date.split('-');
        return `${day}/${month}/${year} ${time.split(':').slice(0, 2).join(':')}`;
    }

    function loadOrderDetails() {
        const formData = new FormData();
        formData.append('action', 'get_order_details');
        formData.append('order_id', orderId);

        fetch('http://localhost/WebPhim/api/order.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Order details API response:', data);
            if (data.success && data.data) {
                const order = data.data;

                // Update movie thumbnail
                elements.movieThumbnail.src = order.movie_thumbnail || 'https://via.placeholder.com/300x450';
                elements.movieThumbnail.alt = order.movie_title || 'Không có tiêu đề';

                // Update invoice details
                elements.invoiceMovie.textContent = order.movie_title || 'Không có tiêu đề';
                elements.invoiceShowTime.textContent = formatDateTime(order.date, order.time);
                elements.invoiceRoom.textContent = order.room_name || 'N/A';
                elements.invoiceSeats.textContent = order.seats ? order.seats.join(', ') : 'N/A';

                // Handle services
                if (order.services && Object.keys(order.services).length > 0) {
                    const serviceNames = Object.entries(order.services).map(([id, qty]) => {
                        const service = order.service_details?.find(s => String(s.id) === String(id));
                        return `${service ? service.name : 'Không xác định'} x${qty}`;
                    }).join(', ');
                    elements.invoiceServices.textContent = serviceNames;
                } else {
                    elements.invoiceServices.textContent = 'Không có';
                }

                // Update total amount
                const totalAmount = parseFloat(order.total_amount) || 0;
                elements.invoiceTotal.textContent = totalAmount.toLocaleString('vi-VN');
                elements.amount.value = totalAmount; // Set amount for form submission
            } else {
                console.error('Lỗi tải chi tiết đơn hàng:', data.message);
                window.location.href = 'index.html';
            }
        })
        .catch(error => {
            console.error('Lỗi tải chi tiết đơn hàng:', error);
            window.location.href = 'index.html';
        });
    }

    // Handle payment selection
    function selectPayment(bankCode, method) {
        elements.bankCode.value = bankCode;
        elements.paymentMethod.value = method;
        elements.paymentForm.submit();
    }

    // Attach event listeners to payment buttons
    const vnpayButton = document.getElementById('vnpayButton');
    const momoButton = document.getElementById('momoButton');

    if (vnpayButton) {
        vnpayButton.addEventListener('click', () => {
            selectPayment('', 'vnpay');
        });
    }

    if (momoButton) {
        momoButton.addEventListener('click', () => {
            selectPayment('', 'momo');
        });
    }

    // Handle form submission
    elements.paymentForm.addEventListener('submit', (e) => {
        if (!elements.paymentMethod.value) {
            e.preventDefault();
            console.log('Vui lòng chọn phương thức thanh toán!');
        } else if (!elements.amount.value || elements.amount.value <= 0) {
            e.preventDefault();
            console.log('Số tiền thanh toán không hợp lệ!');
        } else if (!elements.orderId.value || isNaN(elements.orderId.value)) {
            e.preventDefault();
            console.log('Mã đơn hàng không hợp lệ!');
        } else {
            console.log('Submitting payment form:', {
                amount: elements.amount.value,
                orderId: elements.orderId.value,
                bankCode: elements.bankCode.value,
                paymentMethod: elements.paymentMethod.value
            });
        }
    });

    // Setup navigation prompt
    function setupNavigationPrompt() {
        // Check order status
        const formData = new FormData();
        formData.append('action', 'check_order_status');
        formData.append('order_id', orderId);

        fetch('http://localhost/WebPhim/api/order.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.status === 'pending') {
                // Add event listeners to nav links
                const navLinks = document.querySelectorAll('.navbar-nav .nav-link, .dropdown-menu .dropdown-item');
                navLinks.forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        handleNavigation(link.getAttribute('href') || '#');
                    });
                });
            }
        })
        .catch(error => {
            console.error('Lỗi kiểm tra trạng thái đơn hàng:', error);
        });
    }

    // Handle navigation with confirmation
    function handleNavigation(targetUrl) {
        // Delete order
        const formData = new FormData();
        formData.append('action', 'delete_order');
        formData.append('order_id', orderId);

        fetch('http://localhost/WebPhim/api/order.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Đơn hàng đã được xóa:', orderId);
                window.location.href = targetUrl || 'index.html';
            } else {
                console.error('Lỗi xóa đơn hàng:', data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi xóa đơn hàng:', error);
        });
    }
});