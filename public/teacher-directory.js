(() => {
    const table = document.getElementById('teachers-table');
    if (!table) return;
    const rows = [...table.tBodies[0].rows];
    const state = { query: '', origin: '', destination: '', page: 1, size: 10, sortCol: null, sortDir: 1 };
    const info = document.getElementById('dt-info');
    const pages = document.getElementById('dt-pages');
    const emptyBox = document.getElementById('dt-empty');
    const cellText = (row, index) => {
        const cell = row.cells[index];
        return (cell.dataset.order || cell.textContent).replace(/\s+/g, ' ').trim().toLowerCase();
    };

    function pageButton(label, page, disabled, current = false) {
        const element = document.createElement('button');
        element.type = 'button';
        element.className = `admin-pagination-link${current ? ' is-current' : ''}${disabled ? ' is-disabled' : ''}`;
        element.textContent = label;
        element.disabled = disabled;
        element.addEventListener('click', () => { state.page = page; render(); });
        return element;
    }

    function render() {
        const query = state.query.toLowerCase();
        let visible = rows.filter((row) => (!state.origin || row.dataset.origin === state.origin)
            && (!state.destination || row.dataset.destination === state.destination)
            );
        if (state.sortCol !== null) {
            visible = visible.sort((a, b) => cellText(a, state.sortCol).localeCompare(cellText(b, state.sortCol), 'id') * state.sortDir);
        }
        const lastPage = Math.max(1, Math.ceil(visible.length / state.size));
        state.page = Math.min(state.page, lastPage);
        const from = (state.page - 1) * state.size;
        const shown = new Set(visible.slice(from, from + state.size));
        visible.forEach((row) => table.tBodies[0].appendChild(row));
        rows.forEach((row) => { row.hidden = !shown.has(row); });
        emptyBox.hidden = visible.length > 0;
        const chips = [];
        const label = (id) => { const select = document.getElementById(id); return select.options[select.selectedIndex].text; };
        if (state.origin) chips.push('Asal: ' + label('dt-origin'));
        if (state.destination) chips.push('Tujuan: ' + label('dt-destination'));
        const active = document.getElementById('dt-active');
        active.hidden = chips.length === 0;
        active.textContent = chips.join('  ·  ');
        info.textContent = visible.length
            ? `Menampilkan ${from + 1}-${from + shown.size} dari ${visible.length} guru`
            : 'Menampilkan 0 guru';

        const numbers = [];
        for (let page = 1; page <= lastPage; page += 1) {
            if (page === 1 || page === lastPage || Math.abs(page - state.page) <= 1) numbers.push(page);
            else if (numbers[numbers.length - 1] !== '…') numbers.push('…');
        }
        pages.replaceChildren(
            pageButton('‹', state.page - 1, state.page === 1),
            ...numbers.map((page) => {
                if (page === '…') {
                    const gap = document.createElement('span');
                    gap.className = 'admin-pagination-ellipsis admin-pagination-link';
                    gap.textContent = '…';
                    return gap;
                }
                return pageButton(String(page), page, false, page === state.page);
            }),
            pageButton('›', state.page + 1, state.page === lastPage),
        );
    }

    const bind = (id, key, transform = (value) => value) => {
        document.getElementById(id).addEventListener('input', (event) => {
            state[key] = transform(event.target.value);
            state.page = 1;
            render();
        });
    };
    bind('dt-origin', 'origin');
    bind('dt-destination', 'destination');
    bind('dt-length', 'size', Number);

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
    document.getElementById('dt-reset').addEventListener('click', () => {
        ['dt-origin', 'dt-destination'].forEach((id) => { document.getElementById(id).value = ''; });
        state.origin = ''; state.destination = ''; state.page = 1;
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
})();