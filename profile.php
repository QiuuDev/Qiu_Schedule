<?php
require_once './helpers/auth_helper.php';
secure_session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Qiu's Schedule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased">

    <div class="flex flex-col min-h-screen">
        <header class="bg-[var(--color-bg-primary)] shadow-sm sticky top-0 z-10">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <a href="index.php" class="p-2 sm:px-4 sm:py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-[var(--color-text-on-accent)] bg-[var(--color-accent-primary)] hover:bg-[var(--color-accent-primary-hover)] flex items-center">
                        <i data-lucide="arrow-left" class="h-4 w-4 sm:mr-1"></i>
                        <span class="hidden sm:inline">Kembali ke Kalender</span>
                    </a>
                    <h1 class="text-lg sm:text-xl font-semibold absolute left-1/2 -translate-x-1/2">Profil</h1>
                    <div class="flex items-center space-x-2">
                        <button id="theme-toggle" class="p-2 rounded-md text-[var(--color-text-muted)] hover:text-[var(--color-text-base)] hover:bg-[var(--color-bg-secondary)]" title="Ganti Tema">
                            <i data-lucide="sun" class="h-5 w-5 block dark:hidden"></i>
                            <i data-lucide="moon" class="h-5 w-5 hidden dark:block"></i>
                        </button>
                        <a href="#" id="logout-btn" class="p-2 sm:px-4 sm:py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-[var(--color-text-on-accent)] bg-[var(--color-text-danger)] hover:bg-red-700 flex items-center">
                            <i data-lucide="log-out" class="h-4 w-4 sm:mr-1"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow">
            <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div id="status-message" class="hidden mb-6 p-4 rounded-md text-sm"></div>

                <div class="bg-[var(--color-bg-primary)] rounded-lg shadow-md overflow-hidden border border-[var(--color-border-primary)]">
                    <form id="profile-form" novalidate>
                        <div class="p-6 md:p-8 space-y-8">
                            <div>
                                <h3 class="text-lg font-medium leading-6">Informasi Profil</h3>
                                <p class="mt-1 text-sm text-[var(--color-text-muted)]">Perbarui foto dan detail pribadi Anda di sini.</p>
                                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="md:col-span-1">
                                        <label class="block text-sm font-medium mb-2">Foto Profil</label>
                                        <div class="flex items-center space-x-4">
                                            <img id="profile-picture-preview" class="h-24 w-24 rounded-full object-cover bg-[var(--color-bg-secondary)]" src="https://placehold.co/96x96/e2e8f0/adb5bd?text=..." alt="Foto Profil">
                                            <input type="file" name="profile_picture" id="profile_picture_input" class="hidden" accept="image/png, image/jpeg, image/gif">
                                            <button type="button" id="change-picture-btn" class="px-3 py-2 bg-[var(--color-bg-primary)] border border-[var(--color-border-secondary)] rounded-md text-sm font-medium hover:bg-[var(--color-bg-secondary)]">Ganti</button>
                                        </div>
                                    </div>
                                    <div class="md:col-span-2 space-y-4">
                                        <div>
                                            <label for="full_name" class="block text-sm font-medium">Nama Lengkap</label>
                                            <input type="text" name="full_name" id="full_name" required class="mt-1 input-field">
                                        </div>
                                        <div>
                                            <label for="email" class="block text-sm font-medium">Alamat Email</label>
                                            <input type="email" name="email" id="email" disabled class="mt-1 block w-full bg-[var(--color-bg-secondary)]/50 border-transparent rounded-md p-2 text-sm text-[var(--color-text-muted)] cursor-not-allowed">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-[var(--color-border-primary)] pt-8">
                                <h3 class="text-lg font-medium leading-6">Ubah Kata Sandi</h3>
                                <p class="mt-1 text-sm text-[var(--color-text-muted)]">Kosongkan jika Anda tidak ingin mengubah kata sandi.</p>
                                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium">Kata Sandi Saat Ini</label>
                                        <input type="password" name="current_password" id="current_password" class="mt-1 input-field">
                                    </div>
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium">Kata Sandi Baru</label>
                                        <input type="password" name="new_password" id="new_password" class="mt-1 input-field">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="confirm_new_password" class="block text-sm font-medium">Konfirmasi Kata Sandi Baru</label>
                                        <input type="password" name="confirm_new_password" id="confirm_new_password" class="mt-1 input-field">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 md:px-8 py-4 bg-[var(--color-bg-muted)] text-right border-t border-[var(--color-border-primary)]">
                            <button type="submit" id="save-profile-btn" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-[var(--color-text-on-accent)] bg-[var(--color-accent-primary)] hover:bg-[var(--color-accent-primary-hover)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-accent-primary)]">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="assets/js/apiService.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/profil.js"></script>
</body>
</html>
