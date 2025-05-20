let servicesData = [];

function showToast(message, isError = false) {
    const toastEl = document.getElementById('notificationToast');
    const toastBody = toastEl.querySelector('.toast-body');
    toastBody.textContent = message;
    toastEl.classList.remove('bg-success', 'bg-danger');
    toastEl.classList.add(isError ? 'bg-danger' : 'bg-success');
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

function showLoading(show) {
    const loadingEl = document.getElementById('loading');
    if (loadingEl) {
        loadingEl.style.display = show ? 'block' : 'none';
    }
}

async function uploadImage(file) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (!allowedTypes.includes(file.type)) {
        throw new Error('Chỉ hỗ trợ định dạng JPG, PNG, hoặc GIF');
    }
    if (file.size > maxSize) {
        throw new Error('Kích thước file không được vượt quá 5MB');
    }

    const formData = new FormData();
    formData.append('image', file);

    try {
        const response = await fetch('http://localhost/WebPhim/api/service.php?action=upload_image', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        const responseText = await response.clone().text();
        console.log('Server response (raw):', responseText);

        if (!response.ok) {
            let errorMessage = `Lỗi HTTP: ${response.status}`;
            try {
                const errorResult = JSON.parse(responseText);
                errorMessage = errorResult.message || errorMessage;
            } catch (parseError) {
                errorMessage = `Không thể phân tích phản hồi: ${responseText}`;
            }
            throw new Error(errorMessage);
        }

        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            throw new Error(`Phản hồi không phải JSON: ${responseText}`);
        }

        if (result.success) {
            return result.data.path;
        } else {
            throw new Error(result.message || 'Không thể tải lên hình ảnh');
        }
    } catch (error) {
        console.error('Error uploading image:', error);
        throw error;
    }
}

async function fetchServices() {
    showLoading(true);
    try {
        const response = await fetch('http://localhost/WebPhim/api/service.php?action=get_all', {
            method: 'GET',
            credentials: 'include'
        });
        const responseText = await response.clone().text();
        if (!response.ok) {
            let errorMessage = `Lỗi HTTP: ${response.status}`;
            try {
                const errorResult = JSON.parse(responseText);
                errorMessage = errorResult.message || errorMessage;
            } catch (parseError) {
                errorMessage = `Không thể phân tích phản hồi: ${responseText}`;
            }
            throw new Error(errorMessage);
        }

        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            throw new Error(`Phản hồi không phải JSON: ${responseText}`);
        }

        showLoading(false);
        if (result.success) {
            servicesData = result.data;
            renderServices(servicesData);
        } else {
            showToast(`Lỗi khi lấy danh sách dịch vụ: ${result.message}`, true);
        }
    } catch (error) {
        console.error('Error fetching services:', error);
        showLoading(false);
        showToast(`Lỗi khi lấy danh sách dịch vụ: ${error.message}`, true);
    }
}

function renderServices(services) {
    const tableBody = document.getElementById('servicesTableBody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    tableBody.innerHTML = '';

    services.forEach(service => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${service.id}</td>
            <td>${service.name}</td>
            <td>${formatCurrency(service.price)}</td>
            <td><img src="${service.image_url}" alt="${service.name}" style="width: 50px; height: auto;" onerror="this.src='public/images/fallback.jpg';"></td>
            <td class="action-buttons">
                <button class="btn btn-sm btn-primary" onclick="openEditModal(${service.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteService(${service.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

async function addService(event) {
    event.preventDefault();
    const name = document.getElementById('addName').value.trim();
    const priceInput = document.getElementById('addPrice').value;
    const imageFile = document.getElementById('addImage').files[0];

    if (!name || !priceInput || !imageFile) {
        showToast('Vui lòng điền đầy đủ thông tin', true);
        return;
    }
    const price = parseFloat(priceInput);
    if (isNaN(price) || price <= 0) {
        showToast('Giá phải là một số dương', true);
        return;
    }

    showLoading(true);
    try {
        const image_url = await uploadImage(imageFile);
        const response = await fetch('http://localhost/WebPhim/api/service.php?action=add', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, price, image_url })
        });
        const responseText = await response.clone().text();
        console.log('Add service response (raw):', responseText);

        if (!response.ok) {
            let errorMessage = `Lỗi HTTP: ${response.status}`;
            try {
                const errorResult = JSON.parse(responseText);
                errorMessage = errorResult.message || errorMessage;
            } catch (parseError) {
                errorMessage = `Không thể phân tích phản hồi: ${responseText}`;
            }
            throw new Error(errorMessage);
        }

        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            throw new Error(`Phản hồi không phải JSON: ${responseText}`);
        }

        showLoading(false);
        showToast(result.message, !result.success);
        if (result.success) {
            fetchServices();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addServiceModal'));
            if (modal) modal.hide();
            const form = document.getElementById('addServiceForm');
            if (form) {
                form.reset();
                document.getElementById('addImage').value = '';
            }
        }
    } catch (error) {
        console.error('Error adding service:', error);
        showLoading(false);
        showToast(`Lỗi khi thêm dịch vụ: ${error.message}`, true);
    }
}

async function openEditModal(id) {
    try {
        const response = await fetch(`http://localhost/WebPhim/api/service.php?action=get_by_id&id=${id}`, {
            method: 'GET',
            credentials: 'include'
        });
        const responseText = await response.clone().text();
        console.log('Get service response (raw):', responseText);

        if (!response.ok) {
            let errorMessage = `Lỗi HTTP: ${response.status}`;
            try {
                const errorResult = JSON.parse(responseText);
                errorMessage = errorResult.message || errorMessage;
            } catch (parseError) {
                errorMessage = `Không thể phân tích phản hồi: ${responseText}`;
            }
            throw new Error(errorMessage);
        }

        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            throw new Error(`Phản hồi không phải JSON: ${responseText}`);
        }

        if (result.success) {
            const service = result.data;
            document.getElementById('editId').value = service.id;
            document.getElementById('editName').value = service.name;
            document.getElementById('editPrice').value = service.price;
            new bootstrap.Modal(document.getElementById('editServiceModal')).show();
        } else {
            showToast(`Lỗi khi lấy thông tin dịch vụ: ${result.message}`, true);
        }
    } catch (error) {
        console.error('Error fetching service:', error);
        showToast(`Lỗi khi lấy thông tin dịch vụ: ${error.message}`, true);
    }
}

async function updateService(event) {
    event.preventDefault();
    const id = document.getElementById('editId').value;
    const name = document.getElementById('editName').value.trim();
    const priceInput = document.getElementById('editPrice').value;
    const imageFile = document.getElementById('editImage').files[0];

    if (!name || !priceInput) {
        showToast('Vui lòng điền đầy đủ thông tin', true);
        return;
    }
    const price = parseFloat(priceInput);
    if (isNaN(price) || price <= 0) {
        showToast('Giá phải là một số dương', true);
        return;
    }

    showLoading(true);
    try {
        let image_url = servicesData.find(s => s.id == id)?.image_url;
        if (!image_url) {
            throw new Error('Không tìm thấy dịch vụ để cập nhật');
        }
        if (imageFile) {
            image_url = await uploadImage(imageFile);
        }
        const response = await fetch('http://localhost/WebPhim/api/service.php?action=update', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id), name, price, image_url })
        });
        const responseText = await response.clone().text();
        console.log('Update service response (raw):', responseText);

        if (!response.ok) {
            let errorMessage = `Lỗi HTTP: ${response.status}`;
            try {
                const errorResult = JSON.parse(responseText);
                errorMessage = errorResult.message || errorMessage;
            } catch (parseError) {
                errorMessage = `Không thể phân tích phản hồi: ${responseText}`;
            }
            throw new Error(errorMessage);
        }

        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            throw new Error(`Phản hồi không phải JSON: ${responseText}`);
        }

        showLoading(false);
        showToast(result.message, !result.success);
        if (result.success) {
            fetchServices();
            const modal = bootstrap.Modal.getInstance(document.getElementById('editServiceModal'));
            if (modal) modal.hide();
            const form = document.getElementById('editServiceForm');
            if (form) {
                form.reset();
                document.getElementById('editImage').value = '';
            }
        }
    } catch (error) {
        console.error('Error updating service:', error);
        showLoading(false);
        showToast(`Lỗi khi cập nhật dịch vụ: ${error.message}`, true);
    }
}

async function deleteService(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa dịch vụ này?')) return;

    showLoading(true);
    try {
        const response = await fetch('http://localhost/WebPhim/api/service.php?action=delete', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id) })
        });
        const responseText = await response.clone().text();
        console.log('Delete service response (raw):', responseText);

        if (!response.ok) {
            let errorMessage = `Lỗi HTTP: ${response.status}`;
            try {
                const errorResult = JSON.parse(responseText);
                errorMessage = errorResult.message || errorMessage;
            } catch (parseError) {
                errorMessage = `Không thể phân tích phản hồi: ${responseText}`;
            }
            throw new Error(errorMessage);
        }

        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            throw new Error(`Phản hồi không phải JSON: ${responseText}`);
        }

        showLoading(false);
        showToast(result.message, !result.success);
        if (result.success) {
            fetchServices();
        }
    } catch (error) {
        console.error('Error deleting service:', error);
        showLoading(false);
        showToast(`Lỗi khi xóa dịch vụ: ${error.message}`, true);
    }
}

function formatCurrency(amount) {
    if (isNaN(amount)) return 'N/A';
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

function logout() {
    window.location.href = 'login.html';
}

document.addEventListener('DOMContentLoaded', () => {
    fetchServices();
    const addForm = document.getElementById('addServiceForm');
    const editForm = document.getElementById('editServiceForm');
    if (addForm) {
        addForm.addEventListener('submit', addService);
    }
    if (editForm) {
        editForm.addEventListener('submit', updateService);
    }
});