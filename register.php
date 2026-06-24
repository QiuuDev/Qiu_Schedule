<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - Qiu's Schedule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md p-8 space-y-8 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
        <div>
            <div class="flex justify-center">
                <img src="assets/images/logo.png" alt="Qiu's Schedule Logo" class="h-20 w-auto">
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Buat Akun Baru
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Sudah punya akun? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Masuk di sini</a>
            </p>
        </div>
        <form id="register-form" class="mt-8 space-y-6" action="#" method="POST">
            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label for="full_name" class="sr-only">Nama Lengkap</label>
                    <input id="full_name" name="full_name" type="text" required
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Nama Lengkap">
                </div>
                <div>
                    <label for="email" class="sr-only">Alamat Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Alamat Email">
                </div>
                <div>
                    <label for="password" class="sr-only">Kata Sandi</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Kata Sandi">
                </div>
                <div>
                    <label for="confirm_password" class="sr-only">Konfirmasi Kata Sandi</label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Konfirmasi Kata Sandi">
                </div>
            </div>

            <div id="status-message" class="hidden p-3 rounded-md text-sm"></div>

            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i data-lucide="user-plus" class="h-5 w-5 text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Daftar
                </button>
            </div>
        </form>
    </div>

    <script src="assets/js/apiService.js"></script>
    <script src="assets/js/auth.js"></script>
</body>
</html>
