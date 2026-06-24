<?php
require_once './helpers/auth_helper.php';
secure_session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qiu's Schedule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[var(--color-bg-base)]">

    <audio id="notification-sound" preload="auto">
        <source src="assets/audio/pengingat.mp3" type="audio/mpeg">
    </audio>

    <div id="app" class="relative flex h-screen w-full">
        <!-- Overlay untuk sidebar mobile disini wak -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-40 hidden md:hidden"></div>

        <!-- Sidebar utama disini wak -->
        <aside id="sidebar" class="absolute inset-y-0 left-0 w-64 bg-[var(--color-bg-primary)] border-r border-[var(--color-border-primary)] flex-shrink-0 flex flex-col transition-transform duration-300 ease-in-out -translate-x-full md:relative md:translate-x-0 z-50">
            <div class="h-16 flex items-center px-4 border-b border-[var(--color-border-primary)]">
                <img src="assets/images/logo.png" alt="Qiu's Schedule Logo" class="h-20 w-auto mr-1">
                <h1 class="text-xl font-bold">Qiu's Schedule</h1>
            </div>

            <div class="flex-grow p-4 space-y-6 overflow-y-auto">
                <button id="create-event-sidebar-btn" class="w-full flex items-center justify-center bg-[var(--color-accent-primary)] hover:bg-[var(--color-accent-primary-hover)] text-[var(--color-text-on-accent)] font-semibold px-4 py-2 rounded-md text-sm transition-colors">
                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                    Buat Acara
                </button>
                <div id="mini-calendar" class="pt-4 border-t border-[var(--color-border-primary)]"></div>
                <div class="border-t border-[var(--color-border-primary)] pt-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-bold uppercase text-[var(--color-text-muted)]">Kalender Saya</h3>
                        <button id="create-calendar-btn" class="text-[var(--color-text-accent)] hover:opacity-80" title="Buat Kalender Baru">
                            <i data-lucide="plus-circle" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div id="my-calendars-list" class="space-y-1">
                        <div class="text-xs text-[var(--color-text-muted)]">Memuat kalender...</div>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <a href="analytics.php" class="w-full flex items-center justify-center bg-[var(--color-accent-primary)] hover:bg-[var(--color-accent-primary-hover)] text-[var(--color-text-on-accent)] font-semibold px-4 py-2 rounded-md text-sm transition-colors"> Lihat Analisis </a>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-[var(--color-border-primary)]">
                <a href="profile.php" id="profile-link" class="flex items-center group p-2 rounded-lg bg-[var(--color-bg-base)] hover:bg-[var(--color-bg-secondary)]">
                    <img src="https://placehold.co/40x40/7c3aed/ffffff?text=Q" alt="User Avatar" class="h-10 w-10 rounded-full object-cover">
                    <div class="ml-3 overflow-hidden">
                        <p id="user-full-name" class="text-sm font-semibold truncate">Memuat...</p>
                        <p id="user-email" class="text-xs text-[var(--color-text-muted)] truncate">Memuat...</p>
                    </div>
                </a>
                <a href="#" id="logout-btn" class="mt-2 w-full flex items-center inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-[var(--color-text-on-accent)] bg-[var(--color-text-danger)] hover:bg-[var(--color-accent-primary-hover)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-accent-primary)]">
                    <i data-lucide="log-out" class="h-4 w-4 mr-2"></i>
                    Logout
                </a>
            </div>
        </aside>

        <main class="flex-1 flex flex-col h-screen">
            <header class="relative z-20 h-16 bg-[var(--color-bg-primary)]/80 backdrop-blur-sm border-b border-[var(--color-border-primary)] flex items-center justify-between px-4 md:px-6 flex-shrink-0">
                <div class="flex items-center space-x-2 md:space-x-4">
                    <button id="menu-toggle" class="md:hidden text-[var(--color-text-muted)] hover:text-[var(--color-text-base)]">
                        <i data-lucide="menu" class="h-6 w-6"></i>
                    </button>
                    <button id="today-btn" class="px-3 py-1.5 border border-[var(--color-border-secondary)] rounded-md text-sm font-medium hover:bg-[var(--color-bg-secondary)]">Hari Ini</button>
                    <div class="flex items-center">
                        <button id="prev-btn" class="p-1.5 rounded-md hover:bg-[var(--color-bg-secondary)]" title="Sebelumnya"><i data-lucide="chevron-left" class="h-5 w-5"></i></button>
                        <button id="next-btn" class="p-1.5 rounded-md hover:bg-[var(--color-bg-secondary)]" title="Selanjutnya"><i data-lucide="chevron-right" class="h-5 w-5"></i></button>
                    </div>
                    <h2 id="current-date-title" class="text-base md:text-xl font-semibold w-28 sm:w-auto text-left truncate">Memuat...</h2>
                </div>
                <div class="flex items-center space-x-1 sm:space-x-2">
                    <div class="relative hidden sm:block">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[var(--color-text-muted)]"></i>
                        <input type="search" id="search-input" placeholder="Cari..." class="w-24 md:w-48 input-field pl-9 pr-4 py-2">
                    </div>
                    <div id="view-switcher" class="hidden sm:flex items-center bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-md p-0.5 text-sm font-medium">
                        <button class="px-3 py-1 rounded-md" data-view="dayGridMonth">Bulan</button>
                        <button class="px-3 py-1 rounded-md" data-view="timeGridWeek">Minggu</button>
                        <button class="px-3 py-1 rounded-md" data-view="timeGridDay">Hari</button>
                    </div>

                    <div class="relative">
                        <button id="notification-btn" class="p-2 rounded-md text-[var(--color-text-muted)] hover:text-[var(--color-text-base)] hover:bg-[var(--color-bg-secondary)]" title="Notifikasi">
                            <i data-lucide="bell" class="h-5 w-5"></i>
                            <span id="notification-badge" class="absolute top-1 right-1 h-2.5 w-2.5 bg-red-500 rounded-full hidden"></span>
                        </button>
                        <div id="notification-panel" class="absolute right-0 mt-2 w-80 bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-lg shadow-xl hidden z-50">
                            <div class="p-3 font-semibold border-b border-[var(--color-border-primary)]">Notifikasi</div>
                            <div id="notification-list" class="max-h-96 overflow-y-auto">
                                <div class="p-4 text-center text-sm text-[var(--color-text-muted)]">Tidak ada notifikasi baru.</div>
                            </div>
                            <div class="p-2 border-t border-[var(--color-border-primary)] text-center">
                                <button id="mark-all-read-btn" class="text-xs font-semibold text-[var(--color-text-accent)] hover:underline">Tandai semua sudah dibaca</button>
                            </div>
                        </div>
                    </div>

                    <button id="theme-toggle" class="p-2 rounded-md text-[var(--color-text-muted)] hover:text-[var(--color-text-base)] hover:bg-[var(--color-bg-secondary)]" title="Ganti Tema">
                        <i data-lucide="sun" class="h-5 w-5 block dark:hidden"></i>
                        <i data-lucide="moon" class="h-5 w-5 hidden dark:block"></i>
                    </button>
                </div>
            </header>

            <div id="calendar-container" class="flex-1 overflow-auto p-1 sm:p-2 md:p-4 relative">
                <div id="search-no-results-message" class="absolute inset-0 flex items-center justify-center hidden bg-[var(--color-bg-base)]/50 z-10 backdrop-blur-sm">
                    <div class="bg-[var(--color-bg-primary)] p-6 rounded-lg shadow-xl text-center border border-[var(--color-border-primary)]">
                        <i data-lucide="search-x" class="mx-auto h-12 w-12 text-[var(--color-text-muted)]"></i>
                        <p class="mt-4 text-lg font-semibold">Acara tidak ditemukan</p>
                        <p class="text-sm text-[var(--color-text-muted)] mt-1">Tidak ada acara yang cocok dengan kata kunci Anda.</p>
                    </div>
                </div>
                <div id="calendar-main" class="h-full w-full"></div>
            </div>
        </main>
    </div>

    <!-- modal-modal disini wak -->
    <div id="event-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden z-[60]">
        <div id="event-modal-box" class="bg-[var(--color-bg-primary)] rounded-lg shadow-xl w-full max-w-lg transform transition-all opacity-0 scale-95">
            <form id="event-form" class="flex flex-col h-full" novalidate>
                <input type="hidden" name="event_id" id="event_id">
                <div class="flex items-center justify-between p-4 border-b border-[var(--color-border-primary)]">
                    <h3 id="modal-title" class="text-lg font-semibold">Buat Acara Baru</h3>
                    <button type="button" class="close-modal-btn p-1 rounded-full hover:bg-[var(--color-bg-secondary)]">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto" id="event-modal-content"></div>
                <div class="flex items-center justify-between p-4 bg-[var(--color-bg-muted)] rounded-b-lg mt-auto border-t border-[var(--color-border-primary)]" id="event-modal-footer"></div>
            </form>
        </div>
    </div>
    <div id="calendar-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden z-[60]">
        <div id="calendar-modal-box" class="bg-[var(--color-bg-primary)] rounded-lg shadow-xl w-full max-w-md transform transition-all opacity-0 scale-95">
            <form id="calendar-form" novalidate>
                <input type="hidden" name="calendar_id" id="calendar_id_field">
                <div class="p-5">
                    <h3 id="calendar-modal-title" class="text-lg font-semibold mb-4">Buat Kalender Baru</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="calendar_name" class="block text-sm font-medium text-[var(--color-text-base)]">Nama Kalender</label>
                            <input type="text" name="name" id="calendar_name" required class="mt-1 input-field">
                        </div>
                        <div>
                            <label for="calendar_color" class="block text-sm font-medium text-[var(--color-text-base)]">Warna</label>
                            <input type="color" name="color" id="calendar_color" value="#4f46e5" class="mt-1 block w-full h-10 rounded-md border-transparent focus:outline-none focus:ring-2 focus:ring-[var(--color-accent-primary)]">
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end p-4 bg-[var(--color-bg-muted)] rounded-b-lg space-x-3 border-t border-[var(--color-border-primary)]">
                    <button type="button" class="close-modal-btn px-4 py-2 text-sm font-medium rounded-md hover:bg-[var(--color-bg-secondary)]">Batal</button>
                    <button type="submit" class="bg-[var(--color-accent-primary)] hover:bg-[var(--color-accent-primary-hover)] text-[var(--color-text-on-accent)] font-bold py-2 px-4 rounded-md transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/apiService.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/calendar.js"></script>
</body>
</html>
