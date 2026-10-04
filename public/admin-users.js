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
        const button = statusButton || reviewButton || verifyEmailButton || deleteUserButton;
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
})();
