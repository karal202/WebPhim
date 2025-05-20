document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM đã tải xong');

    // Lấy orderId từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('orderId');
    console.log('orderId:', orderId);

    // Validate orderId
    if (!orderId || isNaN(orderId) || orderId <= 0) {
        console.error('Invalid or missing orderId in URL:', orderId);
        alert('Đơn hàng không hợp lệ!');
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
            loadPaymentStatus();
            loadOrderDetails();
            return;
        }

        const formData = new FormData();
        formData.append('action', 'check_session');

        fetch('http://localhost/WebPhim/api/auth.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
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
                loadGenresAndCountries();
                loadPaymentStatus();
                loadOrderDetails();
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

    const elements = {
        movieThumbnail: document.getElementById('movieThumbnail'),
        paymentStatus: document.getElementById('paymentStatus'),
        txnRef: document.getElementById('txnRef'),
        invoiceMovie: document.getElementById('invoiceMovie'),
        invoiceShowTime: document.getElementById('invoiceShowTime'),
        invoiceRoom: document.getElementById('invoiceRoom'),
        invoiceSeats: document.getElementById('invoiceSeats'),
        invoiceServices: document.getElementById('invoiceServices'),
        invoiceTotal: document.getElementById('invoiceTotal')
    };

    function formatDateTime(date, time) {
        const [year, month, day] = date.split('-');
        return `${day}/${month}/${year} ${time.split(':').slice(0, 2).join(':')}`;
    }

    function loadPaymentStatus() {
        const formData = new FormData();
        formData.append('action', 'get_transaction_details');
        formData.append('order_id', orderId);

        fetch('http://localhost/WebPhim/api/order.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Transaction details API response:', data);
            if (data.success && data.data) {
                const transaction = data.data;
                let paymentStatus = 'Không xác định';
                let txnRef = 'N/A';
                
                if (transaction.vnp_response_code === '00') {
                    paymentStatus = 'Thành công';
                    txnRef = transaction.vnp_txn_ref || 'N/A';
                    elements.paymentStatus.classList.add('payment-success');
                } else if (transaction.momo_result_code === '0') {
                    paymentStatus = 'Thành công';
                    txnRef = transaction.momo_txn_ref || 'N/A';
                    elements.paymentStatus.classList.add('payment-success');
                } else if (transaction.vnp_response_code) {
                    paymentStatus = 'Thất bại: Mã lỗi VNPay ' + transaction.vnp_response_code;
                    txnRef = transaction.vnp_txn_ref || 'N/A';
                    elements.paymentStatus.classList.add('payment-failure');
                } else if (transaction.momo_result_code) {
                    paymentStatus = 'Thất bại: Mã lỗi MoMo ' + transaction.momo_result_code;
                    txnRef = transaction.momo_txn_ref || 'N/A';
                    elements.paymentStatus.classList.add('payment-failure');
                } else {
                    elements.paymentStatus.classList.add('payment-unknown');
                }

                elements.paymentStatus.textContent = paymentStatus;
                elements.txnRef.textContent = txnRef;

                clearSessionPaymentData();
            } else {
                elements.paymentStatus.textContent = 'Không xác định';
                elements.txnRef.textContent = 'N/A';
                elements.paymentStatus.classList.add('payment-unknown');
                console.error('Lỗi tải trạng thái thanh toán:', data.message);
                alert('Không thể tải trạng thái thanh toán: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi tải trạng thái thanh toán:', error);
            elements.paymentStatus.textContent = 'Không xác định';
            elements.txnRef.textContent = 'N/A';
            elements.paymentStatus.classList.add('payment-unknown');
            alert('Lỗi hệ thống khi tải trạng thái thanh toán: ' + error.message);
        });
    }

    function clearSessionPaymentData() {
        const formData = new FormData();
        formData.append('action', 'clear_payment_status');

        fetch('http://localhost/WebPhim/api/auth.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.warn('Lỗi xóa dữ liệu phiên thanh toán:', data.message);
            }
        })
        .catch(error => console.error('Lỗi xóa dữ liệu phiên thanh toán:', error));
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
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
                });
            }
            return response.json();
        })
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

                // Handle seats (support both seat_numbers and seats)
                if (order.seat_numbers) {
                    elements.invoiceSeats.textContent = order.seat_numbers.split(',').join(', ') || 'N/A';
                } else if (order.seats && Array.isArray(order.seats)) {
                    elements.invoiceSeats.textContent = order.seats.join(', ') || 'N/A';
                } else {
                    elements.invoiceSeats.textContent = 'N/A';
                }

                // Handle services
                if (order.services && Object.keys(order.services).length > 0) {
                    const serviceNames = Object.entries(order.services).map(([id, qty]) => {
                        const service = order.service_details?.find(s => String(s.id) === String(id));
                        return `${service ? service.name : 'Không xác định'} x${qty}`;
                    }).join(', ');
                    elements.invoiceServices.textContent = serviceNames || 'Không có';
                } else if (order.service_details && order.service_details.length > 0) {
                    elements.invoiceServices.textContent = order.service_details.map(s => s.name).join(', ') || 'Không có';
                } else {
                    elements.invoiceServices.textContent = 'Không có';
                }

                // Update total amount
                const totalAmount = parseFloat(order.total_amount) || 0;
                elements.invoiceTotal.textContent = totalAmount.toLocaleString('vi-VN');
            } else {
                console.error('Lỗi tải chi tiết đơn hàng:', data.message);
                alert('Không thể tải chi tiết đơn hàng: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi tải chi tiết đơn hàng:', error);
            alert('Lỗi hệ thống khi tải chi tiết đơn hàng: ' + error.message);
        });
    }

    // Tải hóa đơn dưới dạng PDF
    document.getElementById('downloadInvoice').addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4'
        });

        const pageWidth = doc.internal.pageSize.getWidth();
        const margin = 10;
        let yPosition = margin;

        // Sử dụng font Times New Roman
        doc.setFont("times", "normal");

        // Tiêu đề
        doc.setFontSize(20);
        doc.setTextColor(248, 193, 70); // Màu #f8c146
        doc.text('Hóa Đơn Thanh Toán', pageWidth / 2, yPosition, { align: 'center' });
        yPosition += 15;

        // Hình ảnh phim
        const img = elements.movieThumbnail;
        if (img.src && img.src !== 'https://via.placeholder.com/300x450') {
            try {
                const imgData = getImageData(img);
                const imgWidth = 60;
                const imgHeight = (img.naturalHeight / img.naturalWidth) * imgWidth;
                doc.addImage(imgData, 'JPEG', (pageWidth - imgWidth) / 2, yPosition, imgWidth, imgHeight);
                yPosition += imgHeight + 10;
            } catch (error) {
                console.error('Lỗi thêm hình ảnh vào PDF:', error);
            }
        }

        // Thông tin chi tiết
        doc.setFontSize(12);
        doc.setTextColor(0, 0, 0); // Màu đen cho nội dung
        const details = [
            `Trạng thái thanh toán: ${elements.paymentStatus.textContent}`,
            `Mã giao dịch: ${elements.txnRef.textContent}`,
            `Phim: ${elements.invoiceMovie.textContent}`,
            `Thời gian chiếu: ${elements.invoiceShowTime.textContent}`,
            `Phòng: ${elements.invoiceRoom.textContent}`,
            `Danh sách ghế: ${elements.invoiceSeats.textContent}`,
            `Dịch vụ đi kèm: ${elements.invoiceServices.textContent}`,
            `Tổng giá: ${elements.invoiceTotal.textContent} VNĐ`
        ];

        details.forEach(line => {
            const splitText = doc.splitTextToSize(line, pageWidth - 2 * margin);
            splitText.forEach(textLine => {
                if (yPosition > 270) { // Chuyển trang nếu nội dung vượt quá chiều cao
                    doc.addPage();
                    yPosition = margin;
                }
                doc.text(textLine, margin, yPosition);
                yPosition += 7;
            });
        });

        // Lưu file PDF
        doc.save(`hoa_don_thanh_toan_${orderId}.pdf`);
    });

    // Hàm hỗ trợ lấy dữ liệu hình ảnh
    function getImageData(img) {
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        return canvas.toDataURL('image/jpeg');
    }
});