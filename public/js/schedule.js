document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM đã tải xong');

    // Lấy movieId từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const movieId = urlParams.get('movieId');
    console.log('movieId:', movieId);

    // Validate movieId
    if (!movieId || isNaN(movieId) || movieId <= 0) {
        console.error('Invalid or missing movieId in URL:', movieId);
        alert('Vui lòng chọn phim hợp lệ!');
        window.location.href = 'index.html';
        return;
    }

    // Kiểm tra trạng thái đăng nhập
    checkSession();

    function checkSession() {
        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        if (isLoggedIn) {
            updateNav(true, localStorage.getItem('username'), localStorage.getItem('role'));
            loadGenresAndCountries();
            loadMovies();
            loadServices();
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
                loadMovies();
                loadServices();
            } else {
                localStorage.clear();
                const redirectUrl = encodeURIComponent(window.location.href);
                window.location.href = `login.html?redirect=${redirectUrl}`;
            }
        })
        .catch(error => {
            console.error('Lỗi kiểm tra phiên:', error);
            alert('Lỗi kiểm tra trạng thái đăng nhập. Vui lòng thử lại!');
            const redirectUrl = encodeURIComponent(window.location.href);
            window.location.href = `login.html?redirect=${redirectUrl}`;
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
                        localStorage.clear();
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

    let moviesData = [];
    let servicesData = [];
    let selectedMovie = null;
    let selectedSchedule = null;
    let selectedSeats = [];
    let selectedRoomId = null;
    let selectedServices = {};
    let allSchedules = [];
    let selectedDate = null;

    const elements = {
        movieThumbnail: document.getElementById('movieThumbnail'),
        selectedMovie: document.getElementById('selectedMovie'),
        dateList: document.getElementById('dateList'),
        scheduleList: document.getElementById('scheduleList'),
        seatMap: document.getElementById('seatMap'),
        serviceSection: document.getElementById('serviceSection'),
        serviceList: document.getElementById('serviceList'),
        totalPrice: document.getElementById('totalPrice'),
        basePrice: document.getElementById('basePrice'),
        form: document.getElementById('scheduleForm'),
        invoiceDetails: document.getElementById('invoiceDetails'),
        invoiceMovie: document.getElementById('invoiceMovie'),
        invoiceShowTime: document.getElementById('invoiceShowTime'),
        invoiceRoom: document.getElementById('invoiceRoom'),
        invoiceSeats: document.getElementById('invoiceSeats'),
        invoiceServices: document.getElementById('invoiceServices'),
        invoiceTotal: document.getElementById('invoiceTotal')
    };

    // Hàm định dạng ngày giờ
    function formatDateTime(date, time) {
        return `${date} ${time.split(':').slice(0, 2).join(':')}`;
    }

    // Tải danh sách lịch chiếu và ngày tháng
    function loadSchedules() {
        const formData = new FormData();
        formData.append('action', 'get_schedules');
        formData.append('movie_id', movieId);

        fetch('http://localhost/WebPhim/api/schedule.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Schedules API response:', data);
            if (data.success) {
                allSchedules = data.data;
                updateDateList();
                updateScheduleList(allSchedules);
            } else {
                elements.dateList.innerHTML = '<p style="color: #d1d1d1;">Không có ngày chiếu nào.</p>';
                elements.scheduleList.innerHTML = '<p style="color: #d1d1d1;">Không có lịch chiếu nào.</p>';
            }
        })
        .catch(error => {
            console.error('Lỗi tải lịch chiếu:', error);
            elements.dateList.innerHTML = '<p style="color: #d1d1d1;">Lỗi khi tải ngày chiếu.</p>';
            elements.scheduleList.innerHTML = '<p style="color: #d1d1d1;">Lỗi khi tải lịch chiếu.</p>';
        });
    }

    // Cập nhật danh sách ngày tháng
    function updateDateList() {
        const uniqueDates = [...new Set(allSchedules.map(schedule => schedule.date))].sort();
        elements.dateList.innerHTML = '';
        if (uniqueDates.length === 0) {
            elements.dateList.innerHTML = '<p style="color: #d1d1d1;">Không có ngày chiếu nào.</p>';
            return;
        }

        uniqueDates.forEach(date => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'date-btn';
            btn.dataset.date = date;
            btn.textContent = formatDate(date);
            btn.addEventListener('click', () => {
                elements.dateList.querySelectorAll('.date-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                selectedDate = date;
                const filteredSchedules = allSchedules.filter(schedule => schedule.date === date);
                updateScheduleList(filteredSchedules);
            });
            elements.dateList.appendChild(btn);
        });

        if (uniqueDates.length > 0) {
            selectedDate = uniqueDates[0];
            elements.dateList.querySelector('.date-btn').classList.add('selected');
            const filteredSchedules = allSchedules.filter(schedule => schedule.date === selectedDate);
            updateScheduleList(filteredSchedules);
        }
    }

    // Định dạng ngày (ví dụ: 2025-04-24 -> 24/04/2025)
    function formatDate(dateStr) {
        const [year, month, day] = dateStr.split('-');
        return `${day}/${month}/${year}`;
    }

    // Cập nhật danh sách lịch chiếu
    function updateScheduleList(schedules) {
        elements.scheduleList.innerHTML = '';
        if (schedules.length === 0) {
            elements.scheduleList.innerHTML = '<p style="color: #d1d1d1;">Không có lịch chiếu nào cho ngày này.</p>';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'get_bookings');
        formData.append('date', selectedDate);
        formData.append('movie_id', movieId);

        fetch('http://localhost/WebPhim/api/order.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                elements.scheduleList.innerHTML = '';

                schedules.forEach(schedule => {
                    const booking = data.data.find(b => b.time === schedule.time && b.room_id === schedule.room_id);
                    const totalTickets = booking ? parseInt(booking.total_tickets) || 0 : 0;
                    const capacity = booking ? parseInt(booking.capacity) || 50 : 50;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `showtime-btn ${totalTickets >= capacity ? 'disabled' : ''}`;
                    btn.dataset.scheduleId = schedule.id;
                    btn.dataset.date = schedule.date;
                    btn.dataset.time = schedule.time;
                    btn.dataset.roomId = schedule.room_id;
                    btn.dataset.roomName = schedule.room_name || 'N/A';
                    btn.innerHTML = `${schedule.time.split(':').slice(0, 2).join(':')} <span class="booking-count">${totalTickets}/${capacity}</span>`;
                    btn.disabled = totalTickets >= capacity;
                    btn.addEventListener('click', () => {
                        if (totalTickets >= capacity) {
                            alert(`Lịch chiếu ${schedule.date} ${schedule.time.split(':').slice(0, 2).join(':')} đã đầy (${totalTickets}/${capacity} ghế). Vui lòng chọn lịch khác!`);
                            return;
                        }
                        elements.scheduleList.querySelectorAll('.showtime-btn').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        selectedSchedule = {
                            id: schedule.id,
                            date: schedule.date,
                            time: schedule.time,
                            room_id: schedule.room_id,
                            room_name: schedule.room_name
                        };
                        selectedRoomId = schedule.room_id;
                        loadSeats(schedule.room_id, schedule.date, schedule.time);
                        updateTotalPrice();
                    });
                    elements.scheduleList.appendChild(btn);
                });
            } else {
                console.error('Lỗi kiểm tra ghế:', data.message);
                elements.scheduleList.innerHTML = '<p style="color: #d1d1d1;">Lỗi khi kiểm tra ghế.</p>';
            }
        })
        .catch(error => {
            console.error('Lỗi kiểm tra ghế:', error);
            elements.scheduleList.innerHTML = '<p style="color: #d1d1d1;">Lỗi khi kiểm tra ghế.</p>';
        });
    }

    function loadMovies() {
        console.log('Fetching movie with ID:', movieId);
        fetch(`http://localhost/WebPhim/api/movie.php?action=get&id=${movieId}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('Movie API response data:', data);
            if (!data || typeof data !== 'object') {
                throw new Error('Phản hồi API không đúng định dạng');
            }

            let movieData = data;
            if ('success' in data) {
                if (!data.success) {
                    throw new Error(data.message || 'Không thể tải thông tin phim');
                }
                movieData = data.data;
            }

            moviesData = Array.isArray(movieData) ? movieData : [movieData];
            selectedMovie = moviesData.find(m => String(m.id) === String(movieId));

            if (!selectedMovie) {
                throw new Error(`Không tìm thấy phim với ID ${movieId}`);
            }

            elements.movieThumbnail.src = selectedMovie.thumbnail || 'https://via.placeholder.com/300x450';
            elements.movieThumbnail.alt = selectedMovie.title || 'Không có tiêu đề';
            elements.selectedMovie.textContent = selectedMovie.title || 'Không có tiêu đề';
            elements.basePrice.textContent = selectedMovie.ticket_price ? `${parseFloat(selectedMovie.ticket_price).toLocaleString('vi-VN')}đ` : 'Không có giá';

            loadSchedules();
            updateTotalPrice();
        })
        .catch(error => {
            console.error('Lỗi khi tải phim:', error.message);
            alert(`Lỗi: ${error.message}. Vui lòng chọn phim khác!`);
            window.location.href = 'index.html';
        });
    }

    // Tải sơ đồ ghế
    function loadSeats(roomId, date, time) {
        const formData = new FormData();
        formData.append('action', 'get_available_seats');
        formData.append('room_id', roomId);
        formData.append('date', date);
        formData.append('time', time);

        fetch('http://localhost/WebPhim/api/order.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Seat API response:', data);
            if (data.success) {
                if (!data.data || data.data.length === 0) {
                    console.warn('No seat data returned from API');
                    elements.seatMap.innerHTML = '<p style="color: #d1d1d1;">Không có dữ liệu ghế.</p>';
                    return;
                }
                renderSeatMap(data.data);
            } else {
                console.error('Error fetching seats:', data.message);
                elements.seatMap.innerHTML = `<p style="color: #d1d1d1;">${data.message || 'Lỗi khi tải sơ đồ ghế.'}</p>`;
            }
        })
        .catch(error => {
            console.error('Lỗi tải ghế:', error);
            elements.seatMap.innerHTML = '<p style="color: #d1d1d1;">Lỗi khi tải sơ đồ ghế.</p>';
        });
    }

    // Hiển thị sơ đồ ghế
    function renderSeatMap(seats) {
        elements.seatMap.innerHTML = '';
        const rows = 5;
        const cols = 10;
        let seatIndex = 0;

        const rowLabels = ['A', 'B', 'C', 'D', 'E'];

        for (let row = 0; row < rows; row++) {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'seat-row';
            for (let col = 0; col < cols; col++) {
                if (seatIndex < seats.length) {
                    const seat = seats[seatIndex];
                    const seatNumber = `${rowLabels[row]}${String(col + 1).padStart(2, '0')}`;
                    const seatDiv = document.createElement('div');
                    seatDiv.className = `seat ${seat.status || 'available'}`;
                    seatDiv.dataset.seatId = seat.id;
                    seatDiv.dataset.seatNumber = seatNumber;
                    seatDiv.textContent = seatNumber;
                    console.log(`Seat ${seatNumber}: status=${seat.status}, id=${seat.id}`);

                    if (seat.status === 'available') {
                        seatDiv.addEventListener('click', () => {
                            toggleSeatSelection(seatDiv, seat.id);
                        });
                    } else if (seat.status === 'booked') {
                        seatDiv.style.cursor = 'not-allowed';
                    }
                    rowDiv.appendChild(seatDiv);
                    seatIndex++;
                } else {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'seat empty';
                    rowDiv.appendChild(emptyDiv);
                }
            }
            elements.seatMap.appendChild(rowDiv);
        }
    }

    function toggleSeatSelection(seatDiv, seatId) {
        if (seatDiv.classList.contains('booked')) return;

        if (seatDiv.classList.contains('selected')) {
            seatDiv.classList.remove('selected');
            selectedSeats = selectedSeats.filter(id => id !== seatId);
        } else {
            if (selectedSeats.length >= 10) {
                alert('Bạn chỉ có thể chọn tối đa 10 ghế!');
                return;
            }
            seatDiv.classList.add('selected');
            selectedSeats.push(seatId);
        }
        updateTotalPrice();
    }

    function loadServices() {
        fetch('http://localhost/WebPhim/api/service.php?action=get_all')
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('Service API response:', data);
            let servicesDataTemp = [];

            if (Array.isArray(data)) {
                servicesDataTemp = data;
            } else if (data.success) {
                servicesDataTemp = data.data || [];
            } else {
                throw new Error(data.message || 'Không thể tải dịch vụ');
            }

            servicesData = servicesDataTemp;
            console.log('Services loaded:', servicesData);

            if (servicesData.length === 0) {
                elements.serviceList.innerHTML = '<p>Không có dịch vụ nào.</p>';
                return;
            }

            elements.serviceList.innerHTML = servicesData.map(service => `
                <div class="service-option">
                    <div class="service-content">
                        <img src="${service.image_url || 'https://via.placeholder.com/150'}" alt="${service.name || 'Dịch vụ'}">
                    </div>
                    <div class="service-overlay">
                        <span class="service-price">${(parseFloat(service.price) || 0).toLocaleString('vi-VN')}đ</span>
                        <h5>${service.name || 'Không có tên'}</h5>
                    </div>
                    <div class="service-quantity-wrapper">
                        <button type="button" class="service-quantity-btn decrease" data-service-id="${service.id}">-</button>
                        <span class="service-quantity" data-service-id="${service.id}" data-quantity="0">0</span>
                        <button type="button" class="service-quantity-btn increase" data-service-id="${service.id}">+</button>
                    </div>
                    <span class="price-change">+${((parseFloat(service.price) || 0) * 0).toLocaleString('vi-VN')}đ</span>
                </div>
            `).join('');

            elements.serviceList.querySelectorAll('.service-option').forEach(div => {
                const serviceId = div.querySelector('.service-quantity').dataset.serviceId;
                const service = servicesData.find(s => String(s.id) === String(serviceId));
                const quantitySpan = div.querySelector('.service-quantity');
                const priceChange = div.querySelector('.price-change');

                div.querySelector('.decrease').addEventListener('click', () => {
                    let qty = parseInt(quantitySpan.dataset.quantity) || 0;
                    qty = Math.max(0, qty - 1);
                    updateServiceQuantity(quantitySpan, priceChange, service, qty);
                });

                div.querySelector('.increase').addEventListener('click', () => {
                    let qty = parseInt(quantitySpan.dataset.quantity) || 0;
                    qty = Math.min(10, qty + 1);
                    updateServiceQuantity(quantitySpan, priceChange, service, qty);
                });
            });

            document.querySelectorAll('.service-toggle').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.service-toggle').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                    elements.serviceSection.style.display = btn.dataset.value === 'yes' ? 'block' : 'none';
                    if (btn.dataset.value === 'no') {
                        selectedServices = {};
                        elements.serviceList.querySelectorAll('.service-quantity').forEach(span => {
                            span.dataset.quantity = '0';
                            span.textContent = '0';
                            const priceChange = span.closest('.service-option').querySelector('.price-change');
                            priceChange.textContent = '+0đ';
                        });
                    }
                    updateTotalPrice();
                });
            });
        })
        .catch(error => {
            console.error('Lỗi tải dịch vụ:', error);
            elements.serviceList.innerHTML = '<p>Lỗi khi tải dịch vụ.</p>';
        });
    }

    function updateServiceQuantity(quantitySpan, priceChange, service, qty) {
        quantitySpan.dataset.quantity = qty;
        quantitySpan.textContent = qty;
        selectedServices[service.id] = qty;
        priceChange.textContent = `+${((parseFloat(service.price) || 0) * qty).toLocaleString('vi-VN')}đ`;
        updateTotalPrice();
    }

    function updateTotalPrice() {
        if (!selectedMovie || selectedSeats.length === 0) {
            elements.totalPrice.textContent = '0đ';
            elements.basePrice.textContent = '0đ';
            return;
        }

        const ticketPrice = parseFloat(selectedMovie.ticket_price) || 0;
        let total = ticketPrice * selectedSeats.length;

        let servicesTotal = 0;
        if (Object.keys(selectedServices).length > 0) {
            servicesData.forEach(service => {
                const qty = selectedServices[service.id] || 0;
                if (qty > 0) {
                    servicesTotal += (parseFloat(service.price) || 0) * qty;
                }
            });
        }

        total += servicesTotal;

        elements.basePrice.textContent = `${(ticketPrice * selectedSeats.length).toLocaleString('vi-VN')}đ`;
        elements.totalPrice.textContent = `${total.toLocaleString('vi-VN')}đ`;
    }

    elements.form.addEventListener('submit', (e) => {
        e.preventDefault();

        // Kiểm tra ghế đã chọn
        const invalidSeats = selectedSeats.filter(id => {
            const seat = elements.seatMap.querySelector(`[data-seat-id="${id}"]`);
            return seat && seat.classList.contains('booked');
        });

        if (invalidSeats.length > 0) {
            alert('Một số ghế đã chọn đã được đặt bởi người khác. Vui lòng chọn lại!');
            return;
        }

        if (!selectedMovie) {
            alert('Vui lòng chọn phim!');
            return;
        }

        if (!selectedSchedule) {
            alert('Vui lòng chọn lịch chiếu!');
            return;
        }

        if (selectedSeats.length === 0) {
            alert('Vui lòng chọn ít nhất một ghế!');
            return;
        }

        const userId = localStorage.getItem('userId');
        if (!userId) {
            alert('Vui lòng đăng nhập để đặt vé!');
            const redirectUrl = encodeURIComponent(window.location.href);
            window.location.href = `login.html?redirect=${redirectUrl}`;
            return;
        }

        const formData = new FormData();
        formData.append('action', 'get_bookings');
        formData.append('date', selectedSchedule.date);
        formData.append('movie_id', movieId);

        fetch('http://localhost/WebPhim/api/order.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const booking = data.data.find(b => b.time === selectedSchedule.time && b.room_id === selectedSchedule.room_id);
                const currentTickets = booking ? parseInt(booking.total_tickets) || 0 : 0;
                const capacity = booking ? parseInt(booking.capacity) || 50 : 50;

                if (currentTickets + selectedSeats.length > capacity) {
                    alert(`Lịch chiếu ${selectedSchedule.date} ${selectedSchedule.time.split(':').slice(0, 2).join(':')} chỉ còn ${capacity - currentTickets} ghế. Vui lòng chọn số ghế phù hợp!`);
                    return;
                }

                const ticketPrice = parseFloat(selectedMovie.ticket_price) || 0;
                let totalAmount = ticketPrice * selectedSeats.length;

                let servicesTotal = 0;
                const services = {};
                if (Object.keys(selectedServices).length > 0) {
                    servicesData.forEach(service => {
                        const qty = selectedServices[service.id] || 0;
                        if (qty > 0) {
                            servicesTotal += (parseFloat(service.price) || 0) * qty;
                            services[service.id] = qty;
                        }
                    });
                }
                totalAmount += servicesTotal;

                const formData = new FormData();
                formData.append('action', 'create_order');
                formData.append('movie_id', selectedMovie.id);
                formData.append('room_id', selectedRoomId);
                formData.append('date', selectedSchedule.date);
                formData.append('time', selectedSchedule.time);
                formData.append('quantity', selectedSeats.length);
                formData.append('seat_ids', JSON.stringify(selectedSeats));
                formData.append('total_amount', totalAmount);
                if (Object.keys(services).length > 0) {
                    formData.append('services', JSON.stringify(services));
                }

                fetch('http://localhost/WebPhim/api/order.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.order_id) {
                        const orderId = data.data.order_id;
                        window.location.href = `pay_transaction_details.html?orderId=${encodeURIComponent(orderId)}`;
                    } else {
                        alert(data.message || 'Lỗi khi tạo đơn hàng!');
                    }
                })
                .catch(error => {
                    console.error('Lỗi tạo đơn hàng:', error);
                    alert('Lỗi hệ thống khi tạo đơn hàng!');
                });
            } else {
                alert('Lỗi kiểm tra ghế!');
            }
        })
        .catch(error => {
            console.error('Lỗi kiểm tra ghế:', error);
            alert('Lỗi kiểm tra ghế!');
        });
    });

    updateTotalPrice();
});