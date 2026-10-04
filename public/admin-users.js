(() => {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const toast = document.getElementById('toast');

    function showToast(message, isError = false) {
        toast.textContent = message;
        toast.classList.toggle('toast-error', isError);
        toast.classList.add('toast-visible');
        window.clearTimeout(showToast.timer);
        showToast.timer = window.setTimeout(() => toast.classList.remove('toast-visible'), 4200);
    }

    async function request(url, method, body) {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify(body),
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || 'Permintaan tidak dapat diproses.');
        }
        return result;
    }

    document.addEventListener('click', async (event) => {
        const statusButton = event.target.closest('[data-save-admin-status]');
        const reviewButton = event.target.closest('[data-review-deletion]');
        const verifyEmailButton = event.target.closest('[data-verify-user-email]');
        const deleteUserButton = event.target.closest('[data-delete-user]');
        const matchButton = event.target.closest('[data-match-action]');
        const button = statusButton || reviewButton || verifyEmailButton || deleteUserButton || matchButton;
        if (!button) return;

        button.disabled = true;
        try {
            let result;
            if (statusButton) {
                const card = statusButton.closest('[data-admin-profile]');
                result = await request(statusButton.dataset.saveAdminStatus, 'PATCH', {
                    is_mutated: card.querySelector('[data-admin-mutation-status]').value === '1',
                });
            } else if (verifyEmailButton) {
                result = await request(verifyEmailButton.dataset.verifyUserEmail, 'PATCH', {});
            } else if (matchButton) {
                showToast('Mengirim email, mohon tunggu...');
                result = await request(matchButton.dataset.url, 'POST', {});
            } else if (deleteUserButton) {
                if (!window.confirm(`Hapus permanen akun ${deleteUserButton.dataset.userEmail} beserta profil dan data terkait? Tindakan ini tidak dapat dibatalkan.`)) {
                    return;
                }
                result = await request(deleteUserButton.dataset.deleteUser, 'DELETE', {});
            } else {
                const decision = reviewButton.dataset.reviewDeletion;
                const confirmation = decision === 'approve'
                    ? 'Setujui penghapusan profil guru ini? Akun login tetap disimpan.'
                    : 'Tolak pengajuan penghapusan profil ini?';
                if (!window.confirm(confirmation)) return;
                result = await request(reviewButton.dataset.url, 'POST', { decision });
            }
            showToast(result.message);
            window.setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message, true);
            button.disabled = false;
        }
    });

    const table = document.getElementById('users-table');
    if (table) {
        const rows = [...table.tBodies[0].rows];
        const state = { query: '', position: '', page: 1, size: 10, sortCol: null, sortDir: 1 };
        const sizeSelect = document.getElementById('dt-length');
        const searchInput = document.getElementById('dt-search');
        const positionSelect = document.getElementById('dt-position');
        const info = document.getElementById('dt-info');
        const pages = document.getElementById('dt-pages');
        const emptyBox = document.getElementById('dt-empty');
        const cellText = (row, index) => {
            const cell = row.cells[index];
            return (cell.dataset.order || cell.textContent).replace(/\s+/g, ' ').trim().toLowerCase();
        };

        function render() {
            const query = state.query.toLowerCase();
            let visible = rows.filter((row) => (!state.position || row.dataset.position === state.position) && (!query || row.textContent.replace(/\s+/g, ' ').toLowerCase().includes(query)));
            if (state.sortCol !== null) {
                visible = visible.sort((a, b) => cellText(a, state.sortCol).localeCompare(cellText(b, state.sortCol), 'id') * state.sortDir);
            }
            const lastPage = Math.max(1, Math.ceil(visible.length / state.size));
            state.page = Math.min(state.page, lastPage);
            const from = (state.page - 1) * state.size;
            const shown = new Set(visible.slice(from, from + state.size));
            const body = table.tBodies[0];
            visible.forEach((row) => body.appendChild(row));
            rows.forEach((row) => { row.hidden = !shown.has(row); });
            emptyBox.hidden = visible.length > 0;
            info.textContent = visible.length
                ? `Menampilkan ${from + 1}-${from + shown.size} dari ${visible.length} user`
                : 'Menampilkan 0 user';

            const button = (label, page, disabled, current = false) => {
                const element = document.createElement('button');
                element.type = 'button';
                element.className = `admin-pagination-link${current ? ' is-current' : ''}${disabled ? ' is-disabled' : ''}`;
                element.textContent = label;
                element.disabled = disabled;
                element.addEventListener('click', () => { state.page = page; render(); });
                return element;
            };
            const numbers = [];
            for (let page = 1; page <= lastPage; page += 1) {
                if (page === 1 || page === lastPage || Math.abs(page - state.page) <= 1) numbers.push(page);
                else if (numbers[numbers.length - 1] !== '…') numbers.push('…');
            }
            pages.replaceChildren(
                button('‹', state.page - 1, state.page === 1),
                ...numbers.map((page) => {
                    if (page === '…') {
                        const gap = document.createElement('span');
                        gap.className = 'admin-pagination-ellipsis admin-pagination-link';
                        gap.textContent = '…';
                        return gap;
                    }
                    return button(String(page), page, false, page === state.page);
                }),
                button('›', state.page + 1, state.page === lastPage),
            );
        }

        searchInput.addEventListener('input', () => { state.query = searchInput.value.trim(); state.page = 1; render(); });
        positionSelect.addEventListener('change', () => { state.position = positionSelect.value; state.page = 1; render(); });
        sizeSelect.addEventListener('change', () => { state.size = Number(sizeSelect.value); state.page = 1; render(); });
        table.tHead.addEventListener('click', (event) => {
            const header = event.target.closest('th[data-sort]');
            if (!header) return;
            const column = Number(header.dataset.sort);
            state.sortDir = state.sortCol === column ? -state.sortDir : 1;
            state.sortCol = column;
            table.tHead.querySelectorAll('th').forEach((th) => th.removeAttribute('aria-sort'));
            header.setAttribute('aria-sort', state.sortDir === 1 ? 'ascending' : 'descending');
            render();
        });
        render();

        const modal = document.getElementById('user-modal');
        const modalBody = document.getElementById('user-modal-body');
        const closeModal = () => { modal.hidden = true; modalBody.replaceChildren(); document.body.classList.remove('modal-open'); };
        document.addEventListener('click', (event) => {
            const opener = event.target.closest('[data-open-user]');
            if (opener) {
                modalBody.replaceChildren(document.getElementById(opener.dataset.openUser).content.cloneNode(true));
                modal.hidden = false;
                document.body.classList.add('modal-open');
                return;
            }
            if (event.target === modal || event.target.closest('#user-modal-close')) closeModal();
        });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
    }
})();