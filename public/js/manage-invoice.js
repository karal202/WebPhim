let invoicesData = [];
let moviesData = [];

function showToast(message, isError = false) {
    const toastEl = document.getElementById('notificationToast');
    const toastBody = toastEl.querySelector('.toast-body');
    toastBody.textContent = message;
    toastEl.classList.remove('bg-success', 'bg-danger');
    toastEl.classList.add(isError ? 'bg-danger' : 'bg-success');
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const searchUsername = document.getElementById('searchUsername');
    const filterMovie = document.getElementById('filterMovie');

    searchUsername.addEventListener('input', debounce(filterInvoices, 300));
    filterMovie.addEventListener('change', filterInvoices);

    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('orderId');

    if (orderId && !isNaN(orderId) && orderId > 0) {
        fetchInvoiceByOrderId(orderId).then(() => {
            fetchMoviesForFilter().then(() => {
                filterInvoices();
            });
        });
    } else {
        fetchMoviesForFilter().then(() => {
            fetchInvoices().then(() => {
                filterInvoices();
            });
        });
    }

    setInterval(fetchInvoices, 10000);
});

async function fetchMoviesForFilter() {
    try {
        const response = await fetch(`http://localhost/WebPhim/api/movie.php?action=get_all`, {
            method: 'GET',
            credentials: 'include'
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const result = await response.json();
        
        let success = false;
        let data = [];
        let message = 'Không thể tải danh sách phim';

        if (Array.isArray(result)) {
            success = true;
            data = result;
            message = '';
        } else if (result.success !== undefined) {
            success = !!result.success;
            data = result.data || [];
            message = result.message || message;
        } else if (result.error) {
            success = false;
            message = result.error || message;
        }

        if (!success) {
            showToast('Lỗi khi lấy danh sách phim: ' + message, true);
            return;
        }

        moviesData = data;
        const filterSelect = document.getElementById('filterMovie');
        filterSelect.innerHTML = '<option value="">Tất cả phim</option>';
        data.forEach(movie => {
            const option = document.createElement('option');
            option.value = movie.id;
            option.textContent = movie.title;
            filterSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error fetching movies:', error);
        showToast('Lỗi khi lấy danh sách phim: ' + error.message, true);
    }
}

async function fetchInvoices() {
    showLoading(true);
    try {
        const response = await fetch('http://localhost/WebPhim/api/invoice.php?action=get_all_invoices', {
            method: 'GET',
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const result = await response.json();
        showLoading(false);

        if (result.success) {
            invoicesData = await Promise.all(result.data.map(async invoice => {
                const transactionDetails = await getTransactionDetails(invoice.id);
                return {
                    ...invoice,
                    status: invoice.payment_status, // Use synchronized status from backend
                    transaction_ref: transactionDetails.data.vnp_txn_ref || transactionDetails.data.momo_txn_ref || invoice.transaction_ref || '',
                    payment_time: transactionDetails.data.created_at || '',
                    seats: invoice.seat_numbers || 'N/A',
                    services: invoice.services || 'Không có',
                    quantity: invoice.seat_numbers ? invoice.seat_numbers.split(', ').length : 'N/A'
                };
            }));
            console.log('Invoices data:', invoicesData);
            renderInvoices(invoicesData);
        } else {
            const errorMessage = result.message || 'Không thể tải danh sách hóa đơn';
            showToast('Lỗi khi lấy danh sách hóa đơn: ' + errorMessage, true);
        }
    } catch (error) {
        console.error('Error fetching invoices:', error);
        showLoading(false);
        showToast('Lỗi khi lấy danh sách hóa đơn: ' + error.message, true);
    }
}

async function fetchInvoiceByOrderId(orderId) {
    showLoading(true);
    try {
        const response = await fetch(`http://localhost/WebPhim/api/invoice.php?action=get_invoice_by_order_id&order_id=${orderId}`, {
            method: 'GET',
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const result = await response.json();
        showLoading(false);

        if (result.success) {
            const invoice = result.data;
            const transactionDetails = await getTransactionDetails(invoice.id);
            invoice.status = invoice.payment_status; // Use synchronized status from backend
            invoice.transaction_ref = transactionDetails.data.vnp_txn_ref || transactionDetails.data.momo_txn_ref || invoice.transaction_ref || '';
            invoice.payment_time = transactionDetails.data.created_at || '';
            invoice.seats = invoice.seat_numbers || 'N/A';
            invoice.services = invoice.services || 'Không có';
            invoice.quantity = invoice.seat_numbers ? invoice.seat_numbers.split(', ').length : 'N/A';
            invoicesData = [invoice];
            console.log('Invoice by orderId data:', invoicesData);
            renderInvoices(invoicesData);
        } else {
            const errorMessage = result.message || 'Không thể tải hóa đơn';
            showToast('Lỗi khi lấy hóa đơn: ' + errorMessage, true);
        }
    } catch (error) {
        console.error('Error fetching invoice by orderId:', error);
        showLoading(false);
        showToast('Lỗi khi lấy hóa đơn: ' + error.message, true);
    }
}

async function getTransactionDetails(invoiceId) {
    try {
        const response = await fetch(`http://localhost/WebPhim/api/invoice.php?action=get_transaction_details&invoice_id=${invoiceId}`, {
            method: 'GET',
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('Error fetching transaction details:', error);
        return { success: false, data: {}, message: error.message };
    }
}

function filterInvoices() {
    const searchTerm = document.getElementById('searchUsername').value.toLowerCase();
    const selectedMovie = document.getElementById('filterMovie').value;

    console.log('Filtering with searchTerm:', searchTerm, 'selectedMovie:', selectedMovie);
    console.log('Invoices data for filtering:', invoicesData);
    console.log('Movies data for reference:', moviesData);

    const filteredInvoices = invoicesData.filter(invoice => {
        const matchesUsername = invoice.username ? invoice.username.toLowerCase().includes(searchTerm) : true;

        let matchesMovie = true;
        if (selectedMovie) {
            if (invoice.movie_id && invoice.movie_id.toString() === selectedMovie) {
                matchesMovie = true;
            } else {
                const selectedMovieTitle = moviesData.find(movie => movie.id.toString() === selectedMovie)?.title;
                matchesMovie = selectedMovieTitle ? invoice.movie_title === selectedMovieTitle : false;
            }
        }

        return matchesUsername && matchesMovie;
    });

    renderInvoices(filteredInvoices);
}

function renderInvoices(invoices) {
    const tableBody = document.getElementById('invoicesTableBody');
    tableBody.innerHTML = '';

    invoices.forEach(invoice => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${invoice.id}</td>
            <td>${invoice.username || 'N/A'}</td>
            <td>${invoice.movie_title || 'N/A'}</td>
            <td>${formatDate(invoice.show_time)}</td>
            <td>${invoice.show_time ? new Date(invoice.show_time).toLocaleTimeString('vi-VN') : 'N/A'}</td>
            <td>${invoice.seats}</td>
            <td>${invoice.quantity}</td>
            <td>${formatCurrency(invoice.total_amount)}</td>
            <td>${invoice.services}</td>
            <td>${invoice.transaction_ref}</td>
            <td>${invoice.payment_time ? formatDateTime(invoice.payment_time) : 'N/A'}</td>
            <td class="status-cell">
                <select onchange="changeStatus(${invoice.id}, this.value)">
                    <option value="pending" ${invoice.status === 'pending' ? 'selected' : ''}>Đang Chờ</option>
                    <option value="completed" ${invoice.status === 'completed' ? 'selected' : ''}>Thành Công</option>
                    <option value="cancelled" ${invoice.status === 'cancelled' ? 'selected' : ''}>Đã Hủy</option>
                </select>
                <span class="arrow">▼</span>
            </td>
            <td class="action-buttons">
                <button class="btn btn-sm btn-primary" onclick="openEditModal(${invoice.id}, '${invoice.status}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteInvoice(${invoice.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

async function changeStatus(invoiceId, status) {
    console.log('Changing status for invoiceId:', invoiceId, 'to status:', status);
    try {
        const response = await fetch('http://localhost/WebPhim/api/invoice.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_invoice_status&invoice_id=${invoiceId}&status=${status}`
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const result = await response.json();
        console.log('Response from update status:', result);
        showToast(result.message, !result.success);
        if (result.success) {
            fetchInvoices(); // Refresh the list to reflect the new status
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showToast('Lỗi khi cập nhật trạng thái: ' + error.message, true);
    }
}

function openEditModal(invoiceId, status) {
    document.getElementById('editInvoiceId').value = invoiceId;
    document.getElementById('editStatus').value = status;
    new bootstrap.Modal(document.getElementById('editInvoiceModal')).show();
}

async function updateInvoice() {
    const invoiceId = document.getElementById('editInvoiceId').value;
    const status = document.getElementById('editStatus').value;

    try {
        const response = await fetch('http://localhost/WebPhim/api/invoice.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_invoice_status&invoice_id=${invoiceId}&status=${status}`
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const result = await response.json();
        showToast(result.message, !result.success);
        if (result.success) {
            fetchInvoices();
            bootstrap.Modal.getInstance(document.getElementById('editInvoiceModal')).hide();
        }
    } catch (error) {
        console.error('Error updating invoice:', error);
        showToast('Lỗi khi cập nhật hóa đơn: ' + error.message, true);
    }
}

async function deleteInvoice(invoiceId) {
    if (!confirm('Bạn có chắc chắn muốn xóa hóa đơn này?')) return;

    try {
        const response = await fetch('http://localhost/WebPhim/api/invoice.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_invoice&invoice_id=${invoiceId}`
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const result = await response.json();
        showToast(result.message, !result.success);
        if (result.success) {
            fetchInvoices();
        }
    } catch (error) {
        console.error('Error deleting invoice:', error);
        showToast('Lỗi ketika menghapus faktur: ' + error.message, true);
    }
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('vi-VN');
}

function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return 'N/A';
    const date = new Date(dateTimeStr);
    return isNaN(date.getTime()) ? 'N/A' : date.toLocaleString('vi-VN');
}

function formatCurrency(amount) {
    if (isNaN(amount)) return 'N/A';
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

function showLoading(show) {
    document.getElementById('loading').style.display = show ? 'block' : 'none';
}

function logout() {
    window.location.href = 'login.html';
}