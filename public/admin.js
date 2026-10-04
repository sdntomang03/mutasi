(() => {
    const wilayahBase = 'https://www.emsifa.com/api-wilayah-indonesia/v2';
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const $ = (selector) => document.querySelector(selector);
    let districts = [];
    let sudins = [];

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        })[character]);
    }

    function showToast(message, isError = false) {
        const toast = $('#toast');
        toast.textContent = message;
        toast.classList.toggle('toast-error', isError);
        toast.classList.add('toast-visible');
        window.clearTimeout(showToast.timer);
        showToast.timer = window.setTimeout(() => toast.classList.remove('toast-visible'), 4200);
    }

    async function request(path, options = {}) {
        const response = await fetch(path, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token } : {}),
                ...options.headers,
            },
        });
        const body = await response.json();
        if (!response.ok) {
            const validationError = body.errors ? Object.values(body.errors).flat()[0] : null;
            throw new Error(validationError || body.message || 'Permintaan tidak dapat diproses.');
        }
        return body;
    }

    async function fetchWilayah(path) {
        const response = await fetch(`${wilayahBase}${path}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Data wilayah DKI Jakarta tidak dapat dimuat.');
        const result = await response.json();
        if (!Array.isArray(result.data)) throw new Error('Format data wilayah tidak sesuai.');
        return result.data;
    }

    async function loadDistricts() {
        const regencies = await fetchWilayah('/regencies/31.json');
        const groups = await Promise.all(regencies.map(async (regency) => ({
            regency,
            districts: await fetchWilayah(`/districts/${encodeURIComponent(regency.id)}.json`),
        })));
        districts = groups.flatMap(({ regency, districts: regencyDistricts }) => regencyDistricts.map((district) => ({
            code: district.id,
            name: district.name,
            regency_code: regency.id,
            regency_name: regency.name,
        })));
        renderDistricts();
    }

    function renderDistricts(selected = new Set()) {
        const groups = new Map();
        districts.forEach((district) => {
            if (!groups.has(district.regency_code)) groups.set(district.regency_code, { name: district.regency_name, items: [] });
            groups.get(district.regency_code).items.push(district);
        });
        $('#district-groups').innerHTML = [...groups.entries()].map(([code, group]) => `
            <details class="district-group" open>
                <summary>${escapeHtml(group.name)}<span>${group.items.length} kecamatan</span></summary>
                <div class="district-options">${group.items.map((district) => `
                    <label class="check-option"><input type="checkbox" value="${escapeHtml(district.code)}" ${selected.has(district.code) ? 'checked' : ''}><span>${escapeHtml(district.name)}</span></label>
                `).join('')}</div>
            </details>`).join('');
        updateDistrictCount();
    }

    function updateDistrictCount() {
        const count = document.querySelectorAll('#district-groups input:checked').length;
        $('#district-count').textContent = `${count} dipilih`;
    }

    function renderSudins() {
        $('#sudin-total').textContent = sudins.length;
        const container = $('#sudin-list');
        if (!sudins.length) {
            container.innerHTML = '<div class="empty-state compact"><span class="empty-symbol">＋</span><p>Belum ada Sudin. Tambahkan cakupan di formulir.</p></div>';
            return;
        }
        container.innerHTML = sudins.map((sudin) => `
            <article class="sudin-card">
                <div class="sudin-card-heading"><div><h3>${sudin.abbreviation ? `<span class="sudin-abbr">${escapeHtml(sudin.abbreviation)}</span> ` : '}${escapeHtml(sudin.name)}</h3><span>${sudin.districts.length} kecamatan · ${sudin.teacher_profiles_count} profil guru</span></div><span class="sudin-card-mark">⌖</span></div>
                <p class="sudin-district-list">${sudin.districts.map((district) => `${escapeHtml(district.name)} <small>${escapeHtml(district.regency_name)}</small>`).join(' · ') || 'Belum ada kecamatan'}</p>
                <div class="sudin-card-actions"><button type="button" class="text-button" data-edit="${sudin.id}">Ubah cakupan</button><button type="button" class="text-button text-danger" data-delete="${sudin.id}">Hapus</button></div>
            </article>`).join('');
    }

    async function loadSudins() {
        const response = await request('/api/admin/sudins');
        sudins = response.data;
        renderSudins();
    }

    function resetForm() {
        $('#sudin-id').value = '';
        $('#sudin-name').value = '';
        $('#sudin-abbreviation').value = '';
        $('#sudin-form-title').textContent = 'Tambah Sudin';
        $('#cancel-sudin-edit').hidden = true;
        renderDistricts();
    }

    $('#district-groups').addEventListener('change', updateDistrictCount);

    $('#sudin-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = event.currentTarget.querySelector('[type="submit"]');
        const selectedCodes = new Set([...document.querySelectorAll('#district-groups input:checked')].map((input) => input.value));
        if (selectedCodes.size === 0) {
            showToast('Pilih setidaknya satu kecamatan untuk Sudin ini.', true);
            return;
        }
        const payload = {
            name: $('#sudin-name').value.trim(),
            abbreviation: $('#sudin-abbreviation').value.trim() || null,
            districts: districts.filter((district) => selectedCodes.has(district.code)),
        };
        const id = $('#sudin-id').value;
        button.disabled = true;
        button.classList.add('is-loading');
        try {
            await request(id ? `/api/admin/sudins/${id}` : '/api/admin/sudins', {
                method: id ? 'PUT' : 'POST',
                body: JSON.stringify(payload),
            });
            await loadSudins();
            resetForm();
            showToast(id ? 'Cakupan Sudin berhasil diperbarui.' : 'Sudin berhasil ditambahkan.');
        } catch (error) {
            showToast(error.message, true);
        } finally {
            button.disabled = false;
            button.classList.remove('is-loading');
        }
    });

    $('#cancel-sudin-edit').addEventListener('click', resetForm);

    $('#sudin-list').addEventListener('click', async (event) => {
        const editButton = event.target.closest('[data-edit]');
        if (editButton) {
            const sudin = sudins.find((item) => item.id === Number(editButton.dataset.edit));
            if (!sudin) return;
            $('#sudin-id').value = sudin.id;
            $('#sudin-name').value = sudin.name;
            $('#sudin-abbreviation').value = sudin.abbreviation || '';
            $('#sudin-form-title').textContent = 'Ubah cakupan Sudin';
            $('#cancel-sudin-edit').hidden = false;
            renderDistricts(new Set(sudin.districts.map((district) => district.code)));
            document.querySelector('.sudin-form-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        const deleteButton = event.target.closest('[data-delete]');
        if (deleteButton) {
            const sudin = sudins.find((item) => item.id === Number(deleteButton.dataset.delete));
            if (!sudin || !window.confirm(`Hapus ${sudin.name}?`)) return;
            try {
                await request(`/api/admin/sudins/${sudin.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token } });
                await loadSudins();
                showToast('Sudin berhasil dihapus.');
            } catch (error) {
                showToast(error.message, true);
            }
        }
    });

    Promise.all([loadDistricts(), loadSudins()]).catch((error) => showToast(error.message, true));
})();
