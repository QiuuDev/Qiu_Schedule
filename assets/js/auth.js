// autentikasi dan pendaftaran pengguna disini wak
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const statusMessageContainer = document.getElementById('status-message');

    // fungsi untuk menampilkan pesan status disini wak
    function showStatusMessage(message, type) {
        if (!statusMessageContainer) return;
        statusMessageContainer.textContent = message;
        const successClasses = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        const errorClasses = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        statusMessageContainer.className = `p-3 rounded-md text-sm ${type === 'success' ? successClasses : errorClasses}`;
        statusMessageContainer.classList.remove('hidden');
    }

    // logika untuk menangani login disini wak
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const button = e.submitter;
            button.disabled = true;
            button.textContent = 'Memproses...';

            const data = Object.fromEntries(new FormData(loginForm).entries());
            data.action = 'login';

            try {
                const result = await apiCall('auth.php', 'POST', data);
                showStatusMessage(result.message, 'success');
                window.location.href = 'index.php';
            } catch (error) {
                showStatusMessage(error.message, 'error');
                button.disabled = false;
                button.textContent = 'Masuk';
            }
        });
    }

    // logika untuk menangani pendaftaran disini wak
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const button = e.submitter;
            button.disabled = true;
            button.textContent = 'Memproses...';

            const data = Object.fromEntries(new FormData(registerForm).entries());
            data.action = 'register'; // Backend router memerlukan ini

            if (data.password !== data.confirm_password) {
                showStatusMessage('Konfirmasi kata sandi tidak cocok.', 'error');
                button.disabled = false;
                button.textContent = 'Daftar';
                return;
            }

            try {
                const result = await apiCall('auth.php', 'POST', data);
                showStatusMessage(result.message + ' Anda akan diarahkan untuk login.', 'success');
                setTimeout(() => { window.location.href = 'login.php'; }, 2000);
            } catch (error) {
                showStatusMessage(error.message, 'error');
                button.disabled = false;
                button.textContent = 'Daftar';
            }
        });
    }
});