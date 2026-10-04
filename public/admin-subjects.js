(() => {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const toast = document.getElementById('toast');
    const list = document.getElementById('subject-list');
    const form = document.getElementById('subject-form');

    function showToast(message, isError = false) {
        toast.textContent = message;
        toast.classList.toggle('toast-error', isError);
        toast.classList.add('toast-visible');
        window.clearTimeout(showToast.timer);
        showToast.timer = window.setTimeout(() => toast.classList.remove('toast-visible'), 4200);
    }

    async function request(url, method = 'GET', body) {
        const isForm = body instanceof FormData;
        const response = await fetch(url, {
            method,
            headers: { Accept: 'application/json', ...(isForm ? {} : { 'Content-Type': 'application/json' }), 'X-CSRF-TOKEN': token },
            body: isForm ? body : (body ? JSON.stringify(body) : undefined),
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.errors ? Object.values(result.errors)[0][0] : result.message || 'Permintaan tidak dapat diproses.');
        }
        return result;
    }

    function render(subjects) {
        document.getElementById('subject-count').textContent = subjects.length;
        list.replaceChildren(...subjects.map((subject) => {
            const row = document.createElement('article');
            row.className = 'admin-user-card';
            const input = document.createElement('input');
            input.value = subject.name;
            input.maxLength = 100;
            input.setAttribute('aria-label', 'Nama mapel');
            const save = document.createElement('button');
            save.className = 'button button-secondary';
            save.type = 'button';
            save.textContent = 'Simpan';
            save.dataset.action = 'save';
            const remove = document.createElement('button');
            remove.className = 'button button-secondary';
            remove.type = 'button';
            remove.textContent = 'Hapus';
            remove.dataset.action = 'delete';
            row.dataset.id = subject.id;
            row.append(input, save, remove);
            return row;
        }));
    }

    async function load() {
        try {
            render((await request('/api/admin/subjects')).data);
        } catch (error) {
            showToast(error.message, true);
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const result = await request('/api/admin/subjects', 'POST', { name: form.elements.name.value });
            form.reset();
            showToast(result.message);
            load();
        } catch (error) {
            showToast(error.message, true);
        }
    });

    document.getElementById('subject-import-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const importForm = event.currentTarget;
        try {
            const result = await request('/api/admin/subjects/import', 'POST', new FormData(importForm));
            importForm.reset();
            showToast(result.message);
            load();
        } catch (error) {
            showToast(error.message, true);
        }
    });

    list.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const row = button.closest('[data-id]');
        try {
            const result = button.dataset.action === 'save'
                ? await request(`/api/admin/subjects/${row.dataset.id}`, 'PUT', { name: row.querySelector('input').value })
                : await request(`/api/admin/subjects/${row.dataset.id}`, 'DELETE');
            showToast(result.message);
            load();
        } catch (error) {
            showToast(error.message, true);
        }
    });

    load();
})();