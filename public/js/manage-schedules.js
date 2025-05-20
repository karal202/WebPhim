document.addEventListener('DOMContentLoaded', () => {
    // Khởi tạo Flatpickr cho thêm lịch chiếu
    flatpickr('#addShowTime', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        minDate: 'today',
        time_24hr: true,
        minTime: '07:00',
        maxTime: '23:00'
    });

    // Khởi tạo Flatpickr cho sửa lịch chiếu
    flatpickr('#editShowTime', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        minDate: 'today',
        time_24hr: true,
        minTime: '07:00',
        maxTime: '23:00'
    });

    // Khởi tạo Flatpickr cho bộ lọc ngày
    flatpickr('#dateFilter', {
        dateFormat: 'Y-m-d',
        onChange: function(selectedDates, dateStr) {
            loadSchedules(dateStr);
        }
    });

    // Hàm hiển thị toast
    function showToast(message) {
        const toast = document.getElementById('successToast');
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.textContent = message;
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    // Tải danh sách lịch chiếu
    function loadSchedules(dateFilter = '') {
        let url = 'http://localhost/WebPhim/api/schedule.php?action=get_all';
        if (dateFilter) {
            url += `&date=${dateFilter}`;
        }
        fetch(url, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('scheduleTableBody');
                tbody.innerHTML = '';
                data.data.forEach(schedule => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${schedule.id}</td>
                        <td>${schedule.movie_title || 'N/A'}</td>
                        <td>${schedule.room_name || 'N/A'}</td>
                        <td>${schedule.date}</td>
                        <td>${schedule.time.slice(0, 5)}</td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-btn" data-id="${schedule.id}">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="${schedule.id}">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Thêm sự kiện cho nút sửa
                document.querySelectorAll('.edit-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const scheduleId = btn.dataset.id;
                        editSchedule(scheduleId);
                    });
                });

                // Thêm sự kiện cho nút xóa
                document.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const scheduleId = btn.dataset.id;
                        if (confirm('Bạn có chắc chắn muốn xóa lịch chiếu này?')) {
                            deleteSchedule(scheduleId);
                        }
                    });
                });
            } else {
                showToast('Lỗi tải danh sách lịch chiếu: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi tải lịch chiếu:', error);
            showToast('Lỗi hệ thống khi tải lịch chiếu.');
        });
    }

    // Tải danh sách phim
    function loadMovies() {
        fetch('http://localhost/WebPhim/api/movie.php?action=get_all', {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                const addMovieSelect = document.getElementById('addMovieId');
                const editMovieSelect = document.getElementById('editMovieId');
                addMovieSelect.innerHTML = '<option value="">Chọn phim</option>';
                editMovieSelect.innerHTML = '<option value="">Chọn phim</option>';
                data.forEach(movie => {
                    const option = document.createElement('option');
                    option.value = movie.id;
                    option.textContent = movie.title;
                    addMovieSelect.appendChild(option.cloneNode(true));
                    editMovieSelect.appendChild(option);
                });
            } else {
                showToast('Lỗi tải danh sách phim: ' + (data.error || 'Dữ liệu không hợp lệ'));
            }
        })
        .catch(error => {
            console.error('Lỗi tải phim:', error);
            showToast('Lỗi hệ thống khi tải danh sách phim.');
        });
    }

    // Tải danh sách phòng
    function loadRooms() {
        fetch('http://localhost/WebPhim/api/room.php?action=get_all', {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const addRoomSelect = document.getElementById('addRoomId');
                const editRoomSelect = document.getElementById('editRoomId');
                addRoomSelect.innerHTML = '<option value="">Chọn phòng</option>';
                editRoomSelect.innerHTML = '<option value="">Chọn phòng</option>';
                data.data.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = room.name;
                    addRoomSelect.appendChild(option.cloneNode(true));
                    editRoomSelect.appendChild(option);
                });
            } else {
                showToast('Lỗi tải danh sách phòng: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi tải phòng:', error);
            showToast('Lỗi hệ thống khi tải danh sách phòng.');
        });
    }

    // Thêm lịch chiếu
    document.getElementById('addScheduleSubmit').addEventListener('click', () => {
        const movieId = document.getElementById('addMovieId').value;
        const roomId = document.getElementById('addRoomId').value;
        const showTime = document.getElementById('addShowTime').value;
        const errorMessage = document.getElementById('addErrorMessage');

        if (!movieId || !roomId || !showTime) {
            errorMessage.textContent = 'Vui lòng điền đầy đủ thông tin!';
            errorMessage.style.display = 'block';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'add_schedule');
        formData.append('movie_id', movieId);
        formData.append('room_id', roomId);
        formData.append('show_time', showTime);

        fetch('http://localhost/WebPhim/api/schedule.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Thêm lịch chiếu thành công!');
                bootstrap.Modal.getInstance(document.getElementById('addScheduleModal')).hide();
                document.getElementById('addScheduleForm').reset();
                errorMessage.style.display = 'none';
                loadSchedules();
            } else {
                errorMessage.textContent = data.message || 'Lỗi khi thêm lịch chiếu!';
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Lỗi thêm lịch chiếu:', error);
            errorMessage.textContent = 'Lỗi hệ thống khi thêm lịch chiếu!';
            errorMessage.style.display = 'block';
        });
    });

    // Sửa lịch chiếu
    function editSchedule(scheduleId) {
        fetch(`http://localhost/WebPhim/api/schedule.php?action=get&id=${scheduleId}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const schedule = data.data;
                document.getElementById('editScheduleId').value = schedule.id;
                document.getElementById('editMovieId').value = schedule.movie_id;
                document.getElementById('editRoomId').value = schedule.room_id;
                document.getElementById('editShowTime').value = `${schedule.date} ${schedule.time.slice(0, 5)}`;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editScheduleModal')).show();
            } else {
                showToast('Lỗi tải thông tin lịch chiếu: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi tải lịch chiếu:', error);
            showToast('Lỗi hệ thống khi tải thông tin lịch chiếu.');
        });
    }

    document.getElementById('editScheduleSubmit').addEventListener('click', () => {
        const scheduleId = document.getElementById('editScheduleId').value;
        const movieId = document.getElementById('editMovieId').value;
        const roomId = document.getElementById('editRoomId').value;
        const showTime = document.getElementById('editShowTime').value;
        const errorMessage = document.getElementById('editErrorMessage');

        if (!movieId || !roomId || !showTime) {
            errorMessage.textContent = 'Vui lòng điền đầy đủ thông tin!';
            errorMessage.style.display = 'block';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'update_schedule');
        formData.append('schedule_id', scheduleId);
        formData.append('movie_id', movieId);
        formData.append('room_id', roomId);
        formData.append('show_time', showTime);

        fetch('http://localhost/WebPhim/api/schedule.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Cập nhật lịch chiếu thành công!');
                bootstrap.Modal.getInstance(document.getElementById('editScheduleModal')).hide();
                document.getElementById('editScheduleForm').reset();
                errorMessage.style.display = 'none';
                loadSchedules();
            } else {
                errorMessage.textContent = data.message || 'Lỗi khi cập nhật lịch chiếu!';
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Lỗi cập nhật lịch chiếu:', error);
            errorMessage.textContent = 'Lỗi hệ thống khi cập nhật lịch chiếu!';
            errorMessage.style.display = 'block';
        });
    });

    // Xóa lịch chiếu
    function deleteSchedule(scheduleId) {
        const formData = new FormData();
        formData.append('action', 'delete_schedule');
        formData.append('schedule_id', scheduleId);

        fetch('http://localhost/WebPhim/api/schedule.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Xóa lịch chiếu thành công!');
                loadSchedules();
            } else {
                showToast('Lỗi xóa lịch chiếu: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi xóa lịch chiếu:', error);
            showToast('Lỗi hệ thống khi xóa lịch chiếu.');
        });
    }

    // Khởi tạo dữ liệu ban đầu
    loadSchedules();
    loadMovies();
    loadRooms();
});