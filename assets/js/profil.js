document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('profile-form')) return;

    const waitForAppState = setInterval(() => {
        if (window.appState && window.appState.user) {
            clearInterval(waitForAppState);
            initializeProfile();
        }
    }, 100);

    // fungsi untuk menginisialisasi profil pengguna disini wak
    function initializeProfile() {
        const profileForm = document.getElementById('profile-form');
        const statusMessage = document.getElementById('status-message');
        const profilePicturePreview = document.getElementById('profile-picture-preview');
        const profilePictureInput = document.getElementById('profile_picture_input');
        const changePictureBtn = document.getElementById('change-picture-btn');
        const saveBtn = document.getElementById('save-profile-btn');

        // fungsi untuk mengisi form dengan data pengguna disini wak
        function populateForm() {
            const user = window.appState.user;
            if (!user) return;

            document.getElementById('full_name').value = user.full_name;
            document.getElementById('email').value = user.email;

            const initial = user.full_name ? user.full_name.charAt(0).toUpperCase() : 'Q';
            const placeholder = `https://placehold.co/96x96/7c3aed/ffffff?text=${initial}`;

            if (user.profile_picture && user.profile_picture !== 'default_avatar.png') {
                profilePicturePreview.src = `uploads/${user.profile_picture}?t=${new Date().getTime()}`;
            } else {
                profilePicturePreview.src = placeholder;
            }

            profilePicturePreview.onerror = () => {
                profilePicturePreview.src = placeholder;
            };
        }

        // fungsi untuk menampilkan pesan status disini wak
        function showStatusMessage(message, type) {
            statusMessage.textContent = message;
            statusMessage.className = `mb-6 p-4 rounded-md text-sm ${type === 'success' ? 'bg-[var(--color-accent-secondary)] text-[var(--color-text-base)]' : 'bg-red-500/20 text-red-500'}`;
            statusMessage.classList.remove('hidden');
        }

        // fungsi untuk menangani pembaruan profil disini wak
        async function handleProfileUpdate(e) {
            e.preventDefault();
            e.stopPropagation();

            saveBtn.disabled = true;
            saveBtn.textContent = 'Menyimpan...';
            statusMessage.classList.add('hidden');

            try {
                const result = await apiCall('users.php', 'POST', new FormData(profileForm));

                showStatusMessage(result.message, 'success');

                // Mengarahkan pengguna ke halaman utama (index.php) setelah berhasil disini wak
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);

            } catch (error) {
                showStatusMessage(error.message, 'error');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Simpan Perubahan';
            }
        }

        changePictureBtn.addEventListener('click', () => profilePictureInput.click());
        profilePictureInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    profilePicturePreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        profileForm.addEventListener('submit', handleProfileUpdate);

        populateForm();
    }
});
