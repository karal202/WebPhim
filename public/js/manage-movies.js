function showToast(message, isError = false) {
    const toastEl = document.getElementById('notificationToast');
    const toastBody = toastEl.querySelector('.toast-body');
    toastBody.textContent = message;
    toastEl.classList.remove('bg-success', 'bg-danger');
    toastEl.classList.add(isError ? 'bg-danger' : 'bg-success');
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

async function fetchGenres() {
    try {
        const response = await fetch(`http://localhost/WebPhim/api/genre.php?action=get_all`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const genres = await response.json();
        if (genres.error) {
            console.error('Error fetching genres:', genres.error);
            showToast('Không thể tải thể loại', true);
            return [];
        }
        return genres.map(genre => genre.name);
    } catch (error) {
        console.error('Lỗi khi tải thể loại:', error.message);
        showToast('Không thể tải thể loại: ' + error.message, true);
        return [];
    }
}

async function fetchCountries() {
    try {
        const response = await fetch(`http://localhost/WebPhim/api/country.php?action=get_all`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const countries = await response.json();
        if (countries.error) {
            console.error('Error fetching countries:', countries.error);
            showToast('Không thể tải quốc gia', true);
            return [];
        }
        return countries.map(country => {
            if (!country.name) {
                console.warn('Country object missing name:', country);
                return null;
            }
            return country.name;
        }).filter(name => name !== null);
    } catch (error) {
        console.error('Lỗi khi tải quốc gia:', error.message);
        showToast('Không thể tải quốc gia: ' + error.message, true);
        return [];
    }
}

async function populateDatalists() {
    const genres = await fetchGenres();
    const countries = await fetchCountries();

    document.getElementById('genresList').innerHTML = genres.map(genre => `<option value="${genre}">`).join('');
    document.getElementById('countriesList').innerHTML = countries.map(country => `<option value="${country}">`).join('');
}

function initializeMultiSelect(inputId, selectedDivId, hiddenInputId, isTextarea = false) {
    const input = document.getElementById(inputId);
    const selectedDiv = document.getElementById(selectedDivId);
    const hiddenInput = document.getElementById(hiddenInputId);
    let selectedItems = [];

    function updateSelectedDisplay() {
        selectedDiv.innerHTML = selectedItems.map(item => `<span>${item}</span>`).join('');
        hiddenInput.value = selectedItems.join(', ');
        if (!isTextarea) {
            input.value = '';
        }
    }

    if (!isTextarea) {
        input.addEventListener('input', () => {
            const value = input.value.trim();
            if (value.endsWith(',') && value.length > 1) {
                const item = value.slice(0, -1).trim();
                if (item && !selectedItems.includes(item)) {
                    selectedItems.push(item);
                    updateSelectedDisplay();
                }
            }
        });

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && input.value.trim()) {
                e.preventDefault();
                const item = input.value.trim();
                if (item && !selectedItems.includes(item)) {
                    selectedItems.push(item);
                    updateSelectedDisplay();
                }
            }
        });
    } else {
        input.addEventListener('input', () => {
            const value = input.value.trim();
            selectedItems = value ? value.split(',').map(item => item.trim()).filter(item => item) : [];
            updateSelectedDisplay();
        });
    }

    selectedDiv.addEventListener('click', (e) => {
        if (e.target.tagName === 'SPAN') {
            const item = e.target.textContent;
            selectedItems = selectedItems.filter(i => i !== item);
            updateSelectedDisplay();
            if (isTextarea) {
                input.value = selectedItems.join(', ');
            }
        }
    });

    return {
        setItems: (items) => {
            selectedItems = items.filter(item => item.trim());
            updateSelectedDisplay();
            if (isTextarea) {
                input.value = selectedItems.join(', ');
            }
        },
        getItems: () => selectedItems
    };
}

document.addEventListener('DOMContentLoaded', async () => {
    await populateDatalists();

    window.addGenresSelector = initializeMultiSelect('addGenresInput', 'addGenresSelected', 'addGenresHidden');
    window.addCountriesSelector = initializeMultiSelect('addCountriesInput', 'addCountriesSelected', 'addCountriesHidden');
    window.addActorsSelector = initializeMultiSelect('addActorsInput', 'addActorsSelected', 'addActorsHidden', true);

    window.editGenresSelector = initializeMultiSelect('editGenresInput', 'editGenresSelected', 'editGenresHidden');
    window.editCountriesSelector = initializeMultiSelect('editCountriesInput', 'editCountriesSelected', 'editCountriesHidden');
    window.editActorsSelector = initializeMultiSelect('editActorsInput', 'editActorsSelected', 'editActorsHidden', true);

    loadMovies();
});

async function loadMovies() {
    try {
        const response = await fetch(`http://localhost/WebPhim/api/movie.php?action=get_all`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const movies = await response.json();
        if (movies.error) {
            showToast(movies.error, true);
            return;
        }
        const tableBody = document.getElementById('moviesTableBody');
        tableBody.innerHTML = '';
        movies.forEach(movie => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${movie.id}</td>
                <td><img src="${movie.thumbnail ? '/WebPhim/' + movie.thumbnail : '/WebPhim/img/default.jpg'}" alt="${movie.title}" class="thumbnail-img"></td>
                <td>${movie.title}</td>
                <td>${movie.description || ''}</td>
                <td>${movie.duration || ''}</td>
                <td>${movie.language || ''}</td>
                <td>${movie.genres || ''}</td>
                <td>${movie.countries || ''}</td>
                <td>${movie.actors || ''}</td>
                <td>${movie.release_year || ''}</td>
                <td>${movie.ticket_price || ''}</td>
                <td>${movie.average_rating || '0'}</td>
                <td class="action-buttons">
                    <button class="btn btn-sm btn-warning" onclick='editMovie(${JSON.stringify(movie)})'>
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMovie(${movie.id})">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    } catch (error) {
        console.error('Lỗi khi tải phim:', error.message);
        showToast(`Không thể tải phim: ${error.message}`, true);
    }
}

async function addMovie() {
    const form = document.getElementById('addMovieForm');
    const formData = new FormData();

    formData.append('addTitle', document.getElementById('addTitle').value);
    formData.append('addDescription', document.getElementById('addDescription').value);
    formData.append('addDuration', document.getElementById('addDuration').value);
    formData.append('addLanguage', document.getElementById('addLanguage').value);
    formData.append('addGenres', document.getElementById('addGenresHidden').value);
    formData.append('addCountries', document.getElementById('addCountriesHidden').value);
    formData.append('addActors', document.getElementById('addActorsHidden').value);
    formData.append('addReleaseYear', document.getElementById('addReleaseYear').value);
    formData.append('addStatus', document.getElementById('addStatus').value);
    formData.append('addTicketPrice', document.getElementById('addTicketPrice').value);
    const thumbnailFile = document.getElementById('addThumbnail').files[0];
    if (thumbnailFile) {
        formData.append('addThumbnail', thumbnailFile);
    }
    const videoFile = document.getElementById('addVideo').files[0];
    if (videoFile) {
        formData.append('addVideo', videoFile);
    }

    for (let [key, value] of formData.entries()) {
        console.log(`FormData: ${key} = ${value}`);
    }

    if (!formData.get('addTitle')) {
        showToast('Tên phim là bắt buộc', true);
        return;
    }

    try {
        const response = await fetch(`http://localhost/WebPhim/api/movie.php?action=add`, {
            method: 'POST',
            body: formData
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        if (result.error) {
            showToast(result.error, true);
            return;
        }
        showToast(result.success);
        form.reset();
        window.addGenresSelector.setItems([]);
        window.addCountriesSelector.setItems([]);
        window.addActorsSelector.setItems([]);
        bootstrap.Modal.getInstance(document.getElementById('addMovieModal')).hide();
        loadMovies();
    } catch (error) {
        console.error('Lỗi khi thêm phim:', error.message);
        showToast(`Không thể thêm phim: ${error.message}`, true);
    }
}

function editMovie(movie) {
    document.getElementById('editMovieId').value = movie.id;
    document.getElementById('editTitle').value = movie.title;
    document.getElementById('editDescription').value = movie.description || '';
    document.getElementById('editDuration').value = movie.duration || '';
    document.getElementById('editLanguage').value = movie.language || '';
    window.editGenresSelector.setItems(movie.genres ? movie.genres.split(',').map(g => g.trim()) : []);
    window.editCountriesSelector.setItems(movie.countries ? movie.countries.split(',').map(c => c.trim()) : []);
    window.editActorsSelector.setItems(movie.actors ? movie.actors.split(',').map(a => a.trim()) : []);
    document.getElementById('editReleaseYear').value = movie.release_year || '';
    document.getElementById('editStatus').value = movie.status || '';
    document.getElementById('editTicketPrice').value = movie.ticket_price || '';

    const currentThumbnail = document.getElementById('currentThumbnail');
    if (movie.thumbnail) {
        currentThumbnail.src = '/WebPhim/' + movie.thumbnail;
        currentThumbnail.style.display = 'block';
    } else {
        currentThumbnail.style.display = 'none';
    }

    const currentVideo = document.getElementById('currentVideo');
    if (movie.video_path) {
        currentVideo.textContent = `Current video: ${movie.video_path}`;
        currentVideo.style.display = 'block';
    } else {
        currentVideo.style.display = 'none';
    }

    const editModal = new bootstrap.Modal(document.getElementById('editMovieModal'));
    editModal.show();
}

async function updateMovie() {
    const form = document.getElementById('editMovieForm');
    const formData = new FormData();

    formData.append('editMovieId', document.getElementById('editMovieId').value);
    formData.append('editTitle', document.getElementById('editTitle').value);
    formData.append('editDescription', document.getElementById('editDescription').value);
    formData.append('editDuration', document.getElementById('editDuration').value);
    formData.append('editLanguage', document.getElementById('editLanguage').value);
    formData.append('editGenres', document.getElementById('editGenresHidden').value);
    formData.append('editCountries', document.getElementById('editCountriesHidden').value);
    formData.append('editActors', document.getElementById('editActorsHidden').value);
    formData.append('editReleaseYear', document.getElementById('editReleaseYear').value);
    formData.append('editStatus', document.getElementById('editStatus').value);
    formData.append('editTicketPrice', document.getElementById('editTicketPrice').value);
    const thumbnailFile = document.getElementById('editThumbnail').files[0];
    if (thumbnailFile) {
        formData.append('editThumbnail', thumbnailFile);
    }
    const videoFile = document.getElementById('editVideo').files[0];
    if (videoFile) {
        formData.append('editVideo', videoFile);
    }

    for (let [key, value] of formData.entries()) {
        console.log(`FormData: ${key} = ${value}`);
    }

    if (!formData.get('editTitle')) {
        showToast('Tên phim là bắt buộc', true);
        return;
    }

    try {
        const response = await fetch(`http://localhost/WebPhim/api/movie.php?action=update`, {
            method: 'POST',
            body: formData
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        if (result.error) {
            showToast(result.error, true);
            return;
        }
        showToast(result.success);
        bootstrap.Modal.getInstance(document.getElementById('editMovieModal')).hide();
        loadMovies();
    } catch (error) {
        console.error('Lỗi khi cập nhật phim:', error.message);
        showToast(`Không thể cập nhật phim: ${error.message}`, true);
    }
}

async function deleteMovie(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa phim này không?')) return;

    try {
        const response = await fetch(`http://localhost/WebPhim/api/movie.php?action=delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        if (result.error) {
            showToast(result.error, true);
            return;
        }
        showToast(result.success);
        loadMovies();
    } catch (error) {
        console.error('Lỗi khi xóa phim:', error.message);
        showToast(`Không thể xóa phim: ${error.message}`, true);
    }
}