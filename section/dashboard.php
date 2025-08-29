 <?php
// sections/dashboard.php
// Pastikan variabel PHP yang dibutuhkan sudah didefinisikan di admin.php sebelum include ini

// Variabel yang dibutuhkan:
// $total_siswa
// $academic_start_year
// $academic_end_year
// $total_libur
// $total_hari_masuk
// $dashboard_summary
// $biaya_lain_types
?>
<h2 class="text-2xl font-semibold text-gray-800 mb-4">Dashboard Ringkasan Pembayaran</h2>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="card p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-2">Total Siswa</h3>
        <p class="text-3xl font-bold text-teal-700"><?= $total_siswa ?></p>
    </div>
    <div class="card p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-2">Tahun Akademik Aktif</h3>
        <p class="text-3xl font-bold text-teal-700"><?= $academic_start_year ?>/<?= $academic_end_year ?></p>
        <p class="text-gray-600 text-sm">1 Juli <?= $academic_start_year ?> - 30 Juni <?= $academic_end_year ?></p>
    </div>
    <div class="card p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-2">Total Hari Libur</h3>
        <p class="text-3xl font-bold text-teal-700"><?= $total_libur ?></p>
    </div>
    <div class="card p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-2">Total Hari Masuk</h3>
        <p class="text-3xl font-bold text-teal-700"><?= $total_hari_masuk ?></p>
    </div>
</div>

<h3 class="text-xl font-semibold text-gray-800 mb-4">Ringkasan Pembayaran Per Siswa (Tahun Akademik <?= $academic_start_year ?>/<?= $academic_end_year ?>)</h3>

<a href="export_dashboard.php" class="btn btn-primary inline-block mb-4">
    Ekspor ke Excel
</a>

<?php if (!empty($dashboard_summary)): ?>
    <div class="overflow-x-auto table-container">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th rowspan="2" class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">Nama Siswa</th>
                    <th colspan="3" class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider border-b border-gray-300">Ikhsan</th>
                    <th colspan="3" class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider border-b border-gray-300">Infaq</th>
                    <th colspan="<?= count($biaya_lain_types) ?>" class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg border-b border-gray-300">Biaya Lain</th>
                </tr>
                <tr>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Sudah Bayar</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kekurangan</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kembalian</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Sudah Bayar</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kekurangan</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kembalian</th>
                    <?php foreach ($biaya_lain_types as $biaya_id => $biaya_name): ?>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider"><?= htmlspecialchars($biaya_name) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($dashboard_summary as $siswa_id => $data): ?>
                    <tr>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800 font-semibold"><?= htmlspecialchars($data['nama_siswa']) ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($data['spp_harian_dibayar'], 0, ',', '.') ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-red-600">Rp <?= number_format($data['spp_harian_kekurangan'], 0, ',', '.') ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($data['spp_harian_kembalian']) ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($data['spp_mingguan_dibayar'], 0, ',', '.') ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-red-600">Rp <?= number_format($data['spp_mingguan_kekurangan'], 0, ',', '.') ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($data['spp_mingguan_kembalian']) ?></td>
                        <?php foreach ($biaya_lain_types as $biaya_id => $biaya_name): ?>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($data['biaya_lain_' . $biaya_id], 0, ',', '.') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-gray-600">Belum ada data siswa untuk menampilkan ringkasan pembayaran.</p>
<?php endif; ?>