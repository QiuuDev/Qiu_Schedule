document.addEventListener('DOMContentLoaded', () => {
    window.appState = {
        isLoggedIn: false,
        user: null,
        audioUnlocked: false,
        notifications: []
    };

    // Fungsi untuk mengaktifkan konteks audio ni wak
    function unlockAudioContext() {
        if (window.appState.audioUnlocked) return;
        const sound = document.getElementById('notification-sound');
        if (sound) {
            sound.play().then(() => {
                sound.pause();
                sound.currentTime = 0;
                window.appState.audioUnlocked = true;
                console.log("Konteks audio berhasil diaktifkan.");
            }).catch(e => {});
        }
        document.body.removeEventListener('click', unlockAudioContext);
    }
    document.body.addEventListener('click', unlockAudioContext, { once: true });

    // Inisialisasi notifikasi desktop ni wak
    function initializeNotifications() {
        if (!('Notification' in window)) {
            console.log("Browser ini tidak mendukung notifikasi desktop.");
        } else if (Notification.permission === "default") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    console.log("Izin notifikasi diberikan.");
                }
            });
        }
    }

    // Fungsi untuk menampilkan notifikasi pengingat acara ni wak
    function showEventNotification(event) {
        if (Notification.permission !== "granted") return;
        const notificationBody = `Acara: "${event.title}" akan dimulai dalam ${event.reminder_minutes} menit.`;
        const notification = new Notification("Pengingat Acara - Qiu's Schedule", {
            body: notificationBody,
            icon: 'assets/images/logo.png',
            tag: `event-reminder-${event.id}`
        });
        const sound = document.getElementById('notification-sound');
        if (sound && window.appState.audioUnlocked) {
            sound.currentTime = 0;
            sound.play().catch(e => console.error("Gagal memainkan suara notifikasi:", e));
        }
        apiCall('events.php', 'POST', {
            action: 'mark_notified',
            event_id: event.id
        }).catch(e => console.error("Gagal menandai notifikasi:", e));
    }

    // Fungsi untuk memeriksa acara yang akan datang ni wak
    async function checkUpcomingEvents() {
        if (!window.appState.isLoggedIn) return;
        try {
            const eventsToNotify = await apiCall('events.php?action=upcoming', 'GET');
            if (eventsToNotify && eventsToNotify.length > 0) {
                eventsToNotify.forEach(event => {
                    showEventNotification(event);
                });
            }
        } catch (error) {
            console.error("Gagal memeriksa acara akan datang:", error.message);
        }
    }

    const notificationBtn = document.getElementById('notification-btn');
    const notificationPanel = document.getElementById('notification-panel');
    const notificationBadge = document.getElementById('notification-badge');
    const notificationList = document.getElementById('notification-list');
    const markAllReadBtn = document.getElementById('mark-all-read-btn');

    // Fungsi untuk toggle panel notifikasi ni wak
    function toggleNotificationPanel() {
        notificationPanel.classList.toggle('hidden');
        if (!notificationPanel.classList.contains('hidden')) {
            notificationBadge.classList.add('hidden');
        }
    }

    // Render notifikasi ke dalam panel ni wak
    function renderNotifications() {
        notificationList.innerHTML = '';
        if (window.appState.notifications.length === 0) {
            notificationList.innerHTML = '<div class="p-4 text-center text-sm text-[var(--color-text-muted)]">Tidak ada notifikasi baru.</div>';
            notificationBadge.classList.add('hidden');
            return;
        }

        const unreadCount = window.appState.notifications.filter(n => !n.is_read).length;
        if (unreadCount > 0) {
            notificationBadge.classList.remove('hidden');
        } else {
            notificationBadge.classList.add('hidden');
        }

        window.appState.notifications.forEach(notif => {
            const notifEl = document.createElement('div');
            notifEl.className = `p-3 border-b border-[var(--color-border-primary)] hover:bg-[var(--color-bg-secondary)] cursor-pointer notification-item ${!notif.is_read ? 'font-semibold' : ''}`;
            notifEl.dataset.link = notif.link;
            notifEl.dataset.id = notif.id;
            notifEl.innerHTML = `<p class="text-sm">${notif.message}</p><p class="text-xs text-[var(--color-text-muted)] mt-1">${timeAgo(notif.created_at)}</p>`;
            notificationList.appendChild(notifEl);
        });
    }

    // Fungsi untuk mengambil notifikasi ni wak
    async function fetchNotifications() {
        if (!window.appState.isLoggedIn) return;
        try {
            const data = await apiCall('notifications.php', 'GET');
            window.appState.notifications = data.records;
            renderNotifications();
        } catch (error) {
            console.error("Gagal memuat notifikasi:", error);
        }
    }

    // Fungsi untuk menangani klik pada notifikasi ni wak
    async function handleNotificationClick(e) {
        const item = e.target.closest('.notification-item');
        if (!item) return;

        const link = item.dataset.link;
        if (link && link.startsWith('event:')) {
            const eventId = link.split(':')[1];
            window.dispatchEvent(new CustomEvent('openEventModal', { detail: { eventId: eventId } }));
            toggleNotificationPanel();
        }
    }

    // Fungsi untuk menandai semua notifikasi sebagai dibaca ni wak
    async function handleMarkAllRead() {
        try {
            await apiCall('notifications.php', 'POST', { mark_all_as_read: true });
            fetchNotifications();
        } catch (error) {
            alert('Gagal menandai notifikasi.');
        }
    }

    // Fungsi untuk mengubah format waktu menjadi "x waktu ni wak
    function timeAgo(dateString) {
        const date = new Date(dateString.replace(' ', 'T') + 'Z');
        const seconds = Math.floor((new Date() - date) / 1000);
        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + " tahun lalu";
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + " bulan lalu";
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + " hari lalu";
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + " jam lalu";
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + " menit lalu";
        return "Baru saja";
    }

    // Fungsi untuk memeriksa sesi pengguna dan menginisialisasi aplikasi ni wak
    async function checkSessionAndInitialize() {
        try {
            const sessionData = await apiCall('auth.php?action=check_session', 'GET');
            if (!sessionData.loggedIn) {
                const protectedPages = ['index.php', 'profile.php', 'analytics.php'];
                const currentPage = window.location.pathname.split('/').pop();
                if (protectedPages.includes(currentPage)) {
                    window.location.href = 'login.php';
                }
                return;
            }

            window.appState.isLoggedIn = true;
            window.appState.user = sessionData.user;
            const fullUserData = await apiCall('users.php', 'GET');
            window.appState.user = { ...sessionData.user, ...fullUserData };

            initializeUI();

        } catch (error) {
            console.error('Gagal menginisialisasi aplikasi:', error);
        }
    }

    // Fungsi untuk menginisialisasi UI ni wak
    function initializeUI() {
        if (!window.appState.user) return;

        lucide.createIcons();
        handleTheme();
        handleMobileSidebar();
        updateUserProfileUI();
        setupLogout();
        initializeNotifications();

        notificationBtn.addEventListener('click', toggleNotificationPanel);
        notificationList.addEventListener('click', handleNotificationClick);
        markAllReadBtn.addEventListener('click', handleMarkAllRead);

        setInterval(() => {
            checkUpcomingEvents();
            fetchNotifications();
        }, 30000);

        checkUpcomingEvents();
        fetchNotifications();
    }

    // Fungsi untuk memperbarui UI profil pengguna ni wak
    function updateUserProfileUI() {
        const user = window.appState.user;
        const profileLink = document.getElementById('profile-link');
        if (!profileLink) return;
        const userAvatar = profileLink.querySelector('img');
        const userName = profileLink.querySelector('#user-full-name');
        const userEmail = profileLink.querySelector('#user-email');
        userName.textContent = user.full_name;
        userEmail.textContent = user.email;
        const initial = user.full_name ? user.full_name.charAt(0).toUpperCase() : 'Q';
        const placeholder = `https://placehold.co/40x40/7c3aed/ffffff?text=${initial}`;
        const avatarSrc = user.profile_picture && user.profile_picture !== 'default_avatar.png'
            ? `uploads/${user.profile_picture}?t=${new Date().getTime()}`
            : placeholder;
        userAvatar.src = avatarSrc;
        userAvatar.onerror = () => { userAvatar.src = placeholder; };
    }

    // Fungsi untuk mengatur logout ni wak
    function setupLogout() {
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (confirm('Anda yakin ingin logout?')) {
                    try {
                        await apiCall('auth.php', 'POST', { action: 'logout' });
                        window.location.href = 'login.php';
                    } catch (error) {
                        alert('Gagal untuk logout. Silakan coba lagi.');
                    }
                }
            });
        }
    }

    // menangani tema gelap/terang ni wak
    const handleTheme = () => {
        const themeToggle = document.getElementById('theme-toggle');
        if (!themeToggle) return;
        const htmlElement = document.documentElement;
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
        }
        themeToggle.addEventListener('click', () => {
            htmlElement.classList.toggle('dark');
            localStorage.setItem('theme', htmlElement.classList.contains('dark') ? 'dark' : 'light');
            window.dispatchEvent(new CustomEvent('themeChanged'));
        });
    };

    // menangani sidebar mobile ni wak
    const handleMobileSidebar = () => {
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!menuToggle || !sidebar || !overlay) return;

        const openSidebar = () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        };

        const closeSidebar = () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        };

        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar.classList.contains('-translate-x-full')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        });

        overlay.addEventListener('click', closeSidebar);
    };

    checkSessionAndInitialize();
});
