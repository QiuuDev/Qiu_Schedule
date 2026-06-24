<?php
require_once './helpers/auth_helper.php';
secure_session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitik - Qiu's Schedule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased bg-[var(--color-bg-base)]">

    <div class="flex flex-col min-h-screen">
        <header class="bg-[var(--color-bg-primary)] shadow-sm sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <a href="index.php" class="flex items-center space-x-2">
                        <img src="assets/images/logo.png" alt="Qiu's Schedule Logo" class="h-12 w-auto">
                        <span class="font-bold text-lg hidden sm:inline">Analitik</span>
                    </a>
                    <div class="flex items-center space-x-2">
                        <a href="index.php" class="p-2 sm:px-4 sm:py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-[var(--color-text-on-accent)] bg-[var(--color-accent-primary)] hover:bg-[var(--color-accent-primary-hover)] flex items-center">
                            <i data-lucide="arrow-left" class="h-4 w-4 sm:mr-1"></i>
                            <span class="hidden sm:inline">Kembali</span>
                        </a>
                        <button id="theme-toggle" class="p-2 rounded-md text-[var(--color-text-muted)] hover:text-[var(--color-text-base)] hover:bg-[var(--color-bg-secondary)]" title="Ganti Tema">
                            <i data-lucide="sun" class="h-5 w-5 block dark:hidden"></i>
                            <i data-lucide="moon" class="h-5 w-5 hidden dark:block"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <div id="period-filter" class="flex items-center bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-md p-1 text-sm font-medium w-fit">
                        <button class="period-btn px-4 py-1.5 rounded-md" data-period="this_month">Bulan Ini</button>
                        <button class="period-btn px-4 py-1.5 rounded-md" data-period="last_month">Bulan Lalu</button>
                        <button class="period-btn px-4 py-1.5 rounded-md" data-period="this_year">Tahun Ini</button>
                    </div>
                </div>

                <div id="analytics-content" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-[var(--color-bg-primary)] p-6 rounded-lg shadow-md border border-[var(--color-border-primary)]">
                        <h2 id="chart-title" class="text-xl font-semibold mb-4">Distribusi Waktu</h2>
                        <div class="relative h-96">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                    <div id="summary" class="bg-[var(--color-bg-primary)] p-6 rounded-lg shadow-md border border-[var(--color-border-primary)]">
                        <h2 id="summary-title" class="text-xl font-semibold mb-4">Ringkasan</h2>
                        <div id="summary-content" class="space-y-4">
                        </div>
                    </div>
                </div>

                <div id="loading-state" class="text-center py-20 hidden">
                    <i data-lucide="loader-2" class="mx-auto h-12 w-12 text-[var(--color-text-muted)] animate-spin"></i>
                    <p class="mt-4 text-lg font-semibold">Memuat data analitik...</p>
                </div>
                <div id="empty-state" class="text-center py-20 hidden">
                    <i data-lucide="bar-chart-3" class="mx-auto h-12 w-12 text-[var(--color-text-muted)]"></i>
                    <p class="mt-4 text-lg font-semibold">Tidak Ada Data</p>
                    <p class="text-sm text-[var(--color-text-muted)] mt-1">Tidak ada acara yang tercatat pada periode ini.</p>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/apiService.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const periodFilter = document.getElementById('period-filter');
            const analyticsContent = document.getElementById('analytics-content');
            const loadingState = document.getElementById('loading-state');
            const emptyState = document.getElementById('empty-state');
            const chartTitle = document.getElementById('chart-title');
            const summaryTitle = document.getElementById('summary-title');

            let chartInstance = null;

            // fungsi untuk merender grafik ni wak
            function renderChart(data) {
                const ctx = document.getElementById('categoryChart').getContext('2d');

                const labels = data.map(d => d.category_name);
                const totalMinutes = data.map(d => d.total_minutes);
                const backgroundColors = data.map(d => d.category_color);

                if (chartInstance) {
                    chartInstance.destroy();
                }

                chartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Jam',
                            data: totalMinutes.map(m => (m / 60).toFixed(2)), // Konversi ke jam
                            backgroundColor: backgroundColors,
                            hoverOffset: 8,
                            borderWidth: 2,
                            borderColor: getComputedStyle(document.body).getPropertyValue('--color-bg-primary').trim()
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: getComputedStyle(document.body).getPropertyValue('--color-text-base').trim(),
                                    boxWidth: 20,
                                    padding: 20,
                                    font: { size: 14 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                            label += `${context.parsed.toFixed(2)} jam`;
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function renderSummary(data) {
                const summaryContent = document.getElementById('summary-content');
                if (!data || data.length === 0) {
                    summaryContent.innerHTML = '';
                    return;
                }

                const totalMinutes = data.reduce((sum, item) => sum + parseFloat(item.total_minutes), 0);
                const totalHours = (totalMinutes / 60).toFixed(1);

                let summaryHTML = `<div class="text-sm text-[var(--color-text-muted)]">Total Waktu Terjadwal</div>
                                   <div class="text-3xl font-bold">${totalHours} Jam</div>`;

                if (data.length > 0) {
                    const mostProductive = data[0];
                    const mostProductiveHours = (mostProductive.total_minutes / 60).toFixed(1);
                    summaryHTML += `<div class="pt-4 mt-4 border-t border-[var(--color-border-primary)]">
                                        <div class="text-sm text-[var(--color-text-muted)]">Kategori Teratas</div>
                                        <div class="flex items-center mt-1">
                                            <span class="h-3 w-3 rounded-full mr-2" style="background-color: ${mostProductive.category_color};"></span>
                                            <span class="font-semibold">${mostProductive.category_name}</span>
                                            <span class="ml-auto text-sm font-medium">${mostProductiveHours} jam</span>
                                        </div>
                                    </div>`;
                }

                summaryContent.innerHTML = summaryHTML;
            }

            // fungsi untuk mengambil data analitik dari server ni wak
            async function fetchAndRenderAnalytics(period = 'this_month') {
                loadingState.classList.remove('hidden');
                analyticsContent.classList.add('hidden');
                emptyState.classList.add('hidden');

                // UI filter untuk button perpindahan ni wak
                periodFilter.querySelectorAll('.period-btn').forEach(btn => {
                    btn.classList.remove('bg-white', 'dark:bg-blue-900', 'shadow');
                    if (btn.dataset.period === period) {
                        btn.classList.add('bg-white', 'dark:bg-blue-900', 'shadow');
                    }
                });

                const periodText = periodFilter.querySelector(`[data-period="${period}"]`).textContent;
                chartTitle.textContent = `Distribusi Waktu (${periodText})`;
                summaryTitle.textContent = `Ringkasan (${periodText})`;

                try {
                    const data = await apiCall(`events.php?action=analytics&period=${period}`);

                    loadingState.classList.add('hidden');
                    if (data && data.length > 0) {
                        analyticsContent.classList.remove('hidden');
                        renderChart(data);
                        renderSummary(data);
                    } else {
                        emptyState.classList.remove('hidden');
                    }
                } catch (error) {
                    loadingState.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                    emptyState.querySelector('p.font-semibold').textContent = 'Gagal Memuat Data';
                    emptyState.querySelector('p.text-sm').textContent = error.message;
                    console.error("Gagal memuat data analitik:", error);
                }
            }

            periodFilter.addEventListener('click', (e) => {
                const button = e.target.closest('.period-btn');
                if (button && button.dataset.period) {
                    fetchAndRenderAnalytics(button.dataset.period);
                }
            });

            fetchAndRenderAnalytics('this_month');
        });
    </script>
</body>
</html>
