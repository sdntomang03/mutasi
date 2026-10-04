(() => {
    const wilayahBase = 'https://www.emsifa.com/api-wilayah-indonesia/v2';
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const form = document.getElementById('profile-form');
    const destinationList = [];
    const cache = new Map();
    let sudins = [];
    const sudinDistricts = new Map();
    const $ = (selector) => document.querySelector(selector);

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

    async function api(path, options = {}) {
        const response = await fetch(`/api${path}`, {
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

    async function wilayah(path) {
        if (!cache.has(path)) {
            cache.set(path, fetch(`${wilayahBase}${path}`, { headers: { Accept: 'application/json' } })
                .then(async (response) => {
                    if (!response.ok) throw new Error('Data wilayah DKI Jakarta tidak dapat dimuat.');
                    const result = await response.json();
                    if (!Array.isArray(result.data)) throw new Error('Format data wilayah tidak sesuai.');
                    return result.data;
                }));
        }
        return cache.get(path);
    }

    function fillSelect(select, items, placeholder) {
        select.replaceChildren(new Option(placeholder, ''));
        items.forEach((item) => select.add(new Option(item.name, item.id)));
        select.disabled = items.length === 0;
    }

    function selectById(items, id) {
        return items.find((item) => item.id === id) || null;
    }

    async function loadDistricts(regencyCode, select) {
        select.replaceChildren(new Option('Memuat kecamatan...', ''));
        select.disabled = true;
        if (select.id === 'origin-district') {
            $('#origin-village').replaceChildren(new Option('Pilih kecamatan dulu', ''));
            $('#origin-village').disabled = true;
        }
        if (!regencyCode) {
            select.replaceChildren(new Option('Pilih kabupaten/kota dulu', ''));
            return [];
        }

        try {
            const districts = await wilayah(`/districts/${encodeURIComponent(regencyCode)}.json`);
            fillSelect(select, districts, 'Pilih kecamatan');
            return districts;
        } catch (error) {
            if (isChecklist) {
                setDistrictMessage(select, 'Gagal memuat kecamatan');
            } else {
                if (isChecklist) {
                setDistrictMessage(select, 'Gagal memuat kecamatan');
            } else {
                select.replaceChildren(new Option('Gagal memuat kecamatan', ''));
            }
            }
            showToast(error.message, true);
            return [];
        }
    }

    async function loadVillages(districtCode, select) {
        select.replaceChildren(new Option('Memuat kelurahan...', ''));
        select.disabled = true;
        if (!districtCode) {
            select.replaceChildren(new Option('Pilih kecamatan dulu', ''));
            return [];
        }
        try {
            const villages = await wilayah(`/villages/${encodeURIComponent(districtCode)}.json`);
            fillSelect(select, villages, 'Pilih kelurahan');
            return villages;
        } catch (error) {
            select.replaceChildren(new Option('Gagal memuat kelurahan', ''));
            showToast(error.message, true);
            return [];
        }
    }

    async function loadSudins() {
        try {
            const result = await api('/sudins');
            sudins = result.data.map((sudin) => ({ id: String(sudin.id), name: sudin.name }));
            const sudinItems = sudins;
            fillSelect($('#origin-sudin'), sudinItems, sudinItems.length ? 'Pilih Sudin asal' : 'Sudin belum dikonfigurasi');
            fillSelect($('#target-sudin'), sudinItems, 'Pilih Sudin tujuan');
            if (sudinItems.length === 0) {
                $('#origin-sudin').disabled = true;
                showToast('Minta admin mengatur cakupan Sudin terlebih dahulu.', true);
            }
        } catch (error) {
            $('#origin-sudin').replaceChildren(new Option('Gagal memuat Sudin', ''));
            showToast(error.message, true);
        }
    }

    const districtBoxes = () => [...document.querySelectorAll('[data-target-district]')];
    const selectedDistrictBoxes = () => districtBoxes().filter((box) => box.checked);

    function setDistrictMessage(container, message) {
        const note = document.createElement('p');
        note.className = 'district-checks-note';
        note.textContent = message;
        container.replaceChildren(note);
    }

    function renderDistrictChecks(container, districts) {
        if (!districts.length) {
            setDistrictMessage(container, 'Belum ada kecamatan pada Sudin ini.');
            return;
        }
        container.replaceChildren(...districts.map((district) => {
            const label = document.createElement('label');
            label.className = 'level-option district-option';
            const box = document.createElement('input');
            box.type = 'checkbox';
            box.value = district.id;
            box.dataset.targetDistrict = '';
            label.append(box, ` ${district.name}`);
            return label;
        }));
    }

    async function loadSudinDistricts(sudinId, select, placeholder) {
        const isChecklist = select.id === 'target-district';
        if (isChecklist) {
            setDistrictMessage(select, 'Memuat kecamatan...');
        } else {
            select.replaceChildren(new Option('Memuat kecamatan...', ''));
            select.disabled = true;
        }
        if (!sudinId) {
            if (isChecklist) {
                setDistrictMessage(select, placeholder);
            } else {
                select.replaceChildren(new Option(placeholder, ''));
            }
            return [];
        }

        try {
            const result = await api(`/sudins/${encodeURIComponent(sudinId)}/districts`);
            sudinDistricts.set(String(sudinId), result.data);
            if (select.id === 'target-district' && $('#target-sudin').value !== String(sudinId)) {
                return result.data;
            }
            if (isChecklist) {
                renderDistrictChecks(select, result.data);
            } else {
                fillSelect(select, result.data.map((district) => ({
                    id: district.id,
                    name: `${district.name} · ${district.regency_name}`,
                })), placeholder);
            }
            return result.data;
        } catch (error) {
            select.replaceChildren(new Option('Gagal memuat kecamatan', ''));
            showToast(error.message, true);
            return [];
        }
    }

    const levelBoxes = () => [...document.querySelectorAll('[data-target-level]')];

    // Guru kelas hanya boleh satu jenjang tujuan; guru mapel boleh beberapa.
    function syncLevelRules() {
        const isClassTeacher = $('#target-position').value === 'guru_kelas';
        const checked = levelBoxes().filter((box) => box.checked);
        if (isClassTeacher && checked.length > 1) {
            checked.slice(1).forEach((box) => { box.checked = false; });
        }
        $('#target-level-help').textContent = isClassTeacher
            ? 'Guru kelas hanya dapat memilih satu jenjang tujuan.'
            : $('#target-position').value === 'guru_mapel'
                ? 'Guru mapel dapat memilih satu atau beberapa jenjang tujuan.'
                : 'Pilih jabatan tujuan terlebih dahulu. Guru mapel dapat memilih beberapa jenjang, guru kelas hanya satu jenjang.';
    }

    const subjectBoxes = () => [...document.querySelectorAll('[data-target-subject]')];

    // Mapel hanya relevan untuk guru mapel, baik di asal maupun di tujuan.
    function syncSubjectFields() {
        const originIsSubject = $('#origin-position').value === 'guru_mapel';
        const targetIsSubject = $('#target-position').value === 'guru_mapel';
        $('#origin-subject-field').hidden = !originIsSubject;
        $('#origin-subject').required = originIsSubject;
        $('#target-subject-field').hidden = !targetIsSubject;
    }

    $('#origin-position').addEventListener('change', syncSubjectFields);
    $('#target-position').addEventListener('change', () => { syncLevelRules(); syncSubjectFields(); });
    $('#target-levels').addEventListener('change', (event) => {
        if ($('#target-position').value === 'guru_kelas' && event.target.checked) {
            levelBoxes().forEach((box) => { box.checked = box === event.target; });
        }
    });
    async function loadExistingProfile() {
        try {
            const result = await api('/profile');
            const profile = result.data;
            if (!profile) return;

            for (const field of ['name', 'phone', 'employment_type', 'position', 'level', 'destination_position', 'school_name', 'school_address', 'sudin_id', 'subject_id']) {
                form.elements.namedItem(field).value = profile[field] ?? '';
            }

            $('#origin-regency').value = profile.regency_code;
            await loadDistricts(profile.regency_code, $('#origin-district'));
            $('#origin-district').value = profile.district_code;
            await loadVillages(profile.district_code, $('#origin-village'));
            $('#origin-village').value = profile.village_code;

            const savedLevels = (profile.destination_levels || []).map((item) => item.level);
            levelBoxes().forEach((box) => { box.checked = savedLevels.includes(box.value); });
            syncLevelRules();
            const savedSubjects = (profile.destination_subjects || []).map((item) => String(item.id));
            subjectBoxes().forEach((box) => { box.checked = savedSubjects.includes(box.value); });
            syncSubjectFields();
            destinationList.splice(0, destinationList.length);
            if (profile.destination_sudin_id) {
                $('#target-sudin').value = String(profile.destination_sudin_id);
                const targetDistricts = await loadSudinDistricts(profile.destination_sudin_id, $('#target-district'), 'Pilih kecamatan tujuan');
                $('#target-scope').disabled = targetDistricts.length === 0;
                const selectedDistrictCodes = (profile.destination_districts || []).map((district) => district.code);
                if (selectedDistrictCodes.length) {
                    $('#target-scope').value = 'districts';
                    $('#target-district-wrap').hidden = false;
                    districtBoxes().forEach((box) => {
                        box.checked = selectedDistrictCodes.includes(box.value);
                    });
                } else {
                    $('#target-scope').value = 'all';
                }
                destinationList.push({
                    sudin_id: profile.destination_sudin_id,
                    sudin_name: profile.destinationSudin?.name || sudins.find((sudin) => sudin.id === String(profile.destination_sudin_id))?.name,
                    district_codes: selectedDistrictCodes,
                    district_names: profile.destination_districts?.map((district) => district.name) || [],
                });
            }
            renderDestinations();
            form.querySelector('.submit-profile span:first-child').textContent = 'Perbarui profil';
        } catch (error) {
            showToast(error.message, true);
        }
    }

    async function initialiseWilayah() {
        try {
            const regencies = await wilayah('/regencies/31.json');
            fillSelect($('#origin-regency'), regencies, 'Pilih kabupaten/kota');
        } catch (error) {
            $('#origin-regency').replaceChildren(new Option('Gagal memuat wilayah', ''));
            showToast(error.message, true);
        }
    }

    function renderDestinations() {
        const container = $('#selected-destinations');
        if (destinationList.length === 0) {
            container.innerHTML = '<p class="empty-inline">Tujuan mutasi belum dipilih.</p>';
            return;
        }
        container.innerHTML = destinationList.map((item, index) => `
            <div class="destination-chip">
                <span class="chip-dot"></span>
                <span><strong>${escapeHtml(item.sudin_name)}</strong><small>${item.district_codes.length ? `Kecamatan: ${item.district_names.map(escapeHtml).join(', ')}` : 'Semua kecamatan dalam Sudin'}</small></span>
                <button type="button" class="remove-destination" data-index="${index}" aria-label="Hapus tujuan mutasi">×</button>
            </div>`).join('');
    }

    async function addDestination() {
        const sudinId = $('#target-sudin').value;
        const scope = $('#target-scope').value;
        const selectedDistricts = selectedDistrictBoxes();
        if (!sudinId || (scope === 'districts' && selectedDistricts.length === 0)) {
            showToast('Pilih Sudin tujuan dan setidaknya satu kecamatan jika membatasi cakupan.', true);
            return;
        }

        const sudin = sudins.find((item) => item.id === sudinId);
        if (!sudin) {
            showToast('Sudin tujuan tidak ditemukan. Silakan muat ulang halaman.', true);
            return;
        }
        const districts = sudinDistricts.get(sudinId)
            || await loadSudinDistricts(sudinId, $('#target-district'), 'Pilih kecamatan tujuan');
        const districtCodes = scope === 'districts' ? selectedDistricts.map((box) => box.value) : [];
        const districtNames = districtCodes.map((code) => districts.find((district) => district.id === code)?.name || code);

        destinationList.push({
            sudin_id: Number(sudin.id),
            sudin_name: sudin.name,
            district_codes: districtCodes,
            district_names: districtNames,
        });
        destinationList.splice(0, destinationList.length - 1);
        renderDestinations();
    }

    $('#origin-regency').addEventListener('change', (event) => loadDistricts(event.target.value, $('#origin-district')));
    $('#origin-district').addEventListener('change', (event) => loadVillages(event.target.value, $('#origin-village')));
    $('#target-sudin').addEventListener('change', async (event) => {
        const districts = await loadSudinDistricts(event.target.value, $('#target-district'), 'Pilih kecamatan tujuan');
        $('#target-scope').disabled = districts.length === 0;
        $('#target-scope').value = 'all';
        $('#target-district-wrap').hidden = true;
    });
    $('#target-scope').addEventListener('change', (event) => {
        const chooseDistrict = event.target.value === 'districts';
        $('#target-district-wrap').hidden = !chooseDistrict;
        if (chooseDistrict) {
            districtBoxes()[0]?.focus();
        }
    });
    $('#add-destination').addEventListener('click', () => addDestination().catch((error) => showToast(error.message, true)));
    $('#selected-destinations').addEventListener('click', (event) => {
        const button = event.target.closest('[data-index]');
        if (button) {
            destinationList.splice(Number(button.dataset.index), 1);
            renderDestinations();
        }
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (destinationList.length !== 1) {
            showToast('Pilih satu Sudin tujuan dan cakupan kecamatannya.', true);
            return;
        }

        let originRegency;
        let originDistrict;
        let originVillage;
        try {
            const regencies = await wilayah('/regencies/31.json');
            originRegency = selectById(regencies, $('#origin-regency').value);
            const districts = await wilayah(`/districts/${encodeURIComponent($('#origin-regency').value)}.json`);
            originDistrict = selectById(districts, $('#origin-district').value);
            const villages = await wilayah(`/villages/${encodeURIComponent($('#origin-district').value)}.json`);
            originVillage = selectById(villages, $('#origin-village').value);
        } catch (error) {
            showToast(error.message, true);
            return;
        }
        if (!originRegency || !originDistrict || !originVillage) {
            showToast('Lengkapi lokasi sekolah asal dengan benar.', true);
            return;
        }

        const selectedLevels = levelBoxes().filter((box) => box.checked).map((box) => box.value);
        if (!selectedLevels.length) {
            showToast('Pilih minimal satu jenjang tujuan.', true);
            return;
        }

        const selectedSubjects = subjectBoxes().filter((box) => box.checked).map((box) => Number(box.value));
        if ($('#target-position').value === 'guru_mapel' && !selectedSubjects.length) {
            showToast('Pilih minimal satu mapel tujuan.', true);
            return;
        }

        const fields = new FormData(form);
        const payload = Object.fromEntries(fields.entries());
        payload.province_code = '31';
        payload.regency_code = originRegency.id;
        payload.regency_name = originRegency.name;
        payload.district_code = originDistrict.id;
        payload.district_name = originDistrict.name;
        payload.village_code = originVillage.id;
        payload.village_name = originVillage.name;
        payload.destination_sudin_id = destinationList[0].sudin_id;
        payload.destination_district_codes = destinationList[0].district_codes;
        payload.destination_levels = selectedLevels;
        payload.destination_subject_ids = selectedSubjects;
        payload.subject_id = $('#origin-position').value === 'guru_mapel' ? payload.subject_id : null;

        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        submit.classList.add('is-loading');
        try {
            const result = await api('/profile', { method: 'POST', body: JSON.stringify(payload) });
            form.querySelector('.submit-profile span:first-child').textContent = 'Perbarui profil';
            showToast(result.message);
        } catch (error) {
            showToast(error.message, true);
        } finally {
            submit.disabled = false;
            submit.classList.remove('is-loading');
        }
    });

    const profileStatus = $('#profile-status');
    if (profileStatus) {
        const statusSelect = $('#mutation-status-select');
        const saveStatusButton = $('#save-mutation-status');
        const deletionButton = $('#request-profile-deletion');

        saveStatusButton.addEventListener('click', async () => {
            saveStatusButton.disabled = true;
            try {
                const result = await api('/teacher-profile/status', {
                    method: 'PATCH',
                    body: JSON.stringify({ is_mutated: statusSelect.value === '1' }),
                });
                profileStatus.dataset.isMutated = result.data.is_mutated ? '1' : '0';
                $('#mutation-status-label').textContent = result.data.is_mutated ? 'Sudah mutasi' : 'Belum mutasi';
                deletionButton.disabled = !result.data.is_mutated || profileStatus.dataset.pendingDeletion === '1';
                showToast(result.message);
            } catch (error) {
                showToast(error.message, true);
            } finally {
                saveStatusButton.disabled = false;
            }
        });

        deletionButton.addEventListener('click', async () => {
            if (!window.confirm('Ajukan penghapusan profil ini kepada admin? Profil tidak akan dihapus sampai admin menyetujuinya.')) return;
            deletionButton.disabled = true;
            try {
                const result = await api('/teacher-profile/deletion-request', {
                    method: 'POST',
                    body: JSON.stringify({}),
                });
                profileStatus.dataset.pendingDeletion = '1';
                deletionButton.textContent = 'Pengajuan menunggu admin';
                showToast(result.message);
            } catch (error) {
                showToast(error.message, true);
                deletionButton.disabled = false;
            }
        });
    }

    Promise.all([initialiseWilayah(), loadSudins()]).then(loadExistingProfile).catch((error) => showToast(error.message, true));
})();
