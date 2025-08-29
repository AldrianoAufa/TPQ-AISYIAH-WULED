 <?php
// sections/spp2_management.php
// Pastikan variabel PHP yang dibutuhkan sudah didefinisikan di admin.php sebelum include ini

// Variabel yang dibutuhkan:
// $academic_start_year
// $academic_end_year
// $siswa_list
// $records_per_page
// $order_by
// $search_query
// $spp2_payments_summary
// $total_spp2_records
// $current_page
// create_pagination_link (function)
// format_date_indo (function)
?>
<h3 class="text-xl font-bold text-gray-800 mb-4">Pembayaran Infaq (Tahun Akademik <?= $academic_start_year ?>/<?= $academic_end_year ?>)</h3>

<div class="bg-gray-50 p-4 rounded-lg shadow-sm mb-6">
    <h4 class="text-lg font-semibold text-gray-700 mb-3">Input Pembayaran Infaq</h4>
    <form action="admin.php?section=pembayaran&spp_tab=spp2_mgmt" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="form-group">
            <label for="id_siswa_pay_spp2">Pilih Siswa:</label>
            <select id="id_siswa_pay_spp2" name="id_siswa_pay_spp2" required>
                <option value="">-- Pilih Siswa --</option>
                <?php foreach ($siswa_list as $siswa): ?>
                    <option value="<?= htmlspecialchars($siswa['id_siswa']) ?>"><?= htmlspecialchars($siswa['nama_siswa']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="jumlah_pembayaran_spp2">Jumlah Pembayaran (Rp):</label>
            <input type="number" id="jumlah_pembayaran_spp2" name="jumlah_pembayaran_spp2" placeholder="Contoh: 5000" required min="0">
        </div>
        <div class="flex items-center">
            <button type="submit" name="pay_spp2_by_amount" class="btn btn-success w-full">Proses Pembayaran Infaq</button>
        </div>
    </form>
</div>

<div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
    <!-- Search Bar -->
    <form action="admin.php" method="GET" class="w-full md:w-1/3">
        <input type="hidden" name="section" value="pembayaran">
        <input type="hidden" name="spp_tab" value="spp2_mgmt">
        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
        <input type="text" name="search" placeholder="Cari nama siswa..." value="<?= htmlspecialchars($search_query) ?>">
    </form>

    <!-- Sort By -->
    <form action="admin.php" method="GET" class="w-full md:w-1/4">
        <input type="hidden" name="section" value="pembayaran">
        <input type="hidden" name="spp_tab" value="spp2_mgmt">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
        <label for="order_by_spp2" class="sr-only">Urutkan Berdasarkan:</label>
        <select name="order_by" id="order_by_spp2" onchange="this.form.submit()">
            <option value="tanggal_desc" <?= ($order_by == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
            <option value="tanggal_asc" <?= ($order_by == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terlama</option>
            <option value="nama_siswa_asc" <?= ($order_by == 'nama_siswa_asc') ? 'selected' : '' ?>>Nama Siswa (A-Z)</option>
            <option value="nama_siswa_desc" <?= ($order_by == 'nama_siswa_desc') ? 'selected' : '' ?>>Nama Siswa (Z-A)</option>
        </select>
    </form>

    <!-- Records per page -->
    <form action="admin.php" method="GET" class="w-full md:w-1/4">
        <input type="hidden" name="section" value="pembayaran">
        <input type="hidden" name="spp_tab" value="spp2_mgmt">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
        <label for="per_page_spp2" class="sr-only">Data per halaman:</label>
        <select name="per_page" id="per_page_spp2" onchange="this.form.submit()">
            <option value="10" <?= ($records_per_page == 10) ? 'selected' : '' ?>>10 per halaman</option>
            <option value="20" <?= ($records_per_page == 20) ? 'selected' : '' ?>>20 per halaman</option>
            <option value="50" <?= ($records_per_page == 50) ? 'selected' : '' ?>>50 per halaman</option>
            <option value="100" <?= ($records_per_page == 100) ? 'selected' : '' ?>>100 per halaman</option>
            <option value="1000" <?= ($records_per_page == 1000) ? 'selected' : '' ?>>1000 per halaman</option>
        </select>
    </form>
</div>

<!-- Tombol Ekspor SPP Mingguan -->
<div class="mb-4 text-right">
    <a href="export_payments.php?spp_type=spp2&start_date=<?= urlencode($academic_start_date_str) ?>&end_date=<?= urlencode($academic_end_date_str) ?>"
        class="btn btn-primary">
        <span class="material-icons-outlined align-middle mr-1">download</span> Ekspor Data Infaq
    </a>
</div>

<?php if (!empty($spp2_payments_summary)): ?>
    <form id="bulkDeleteSpp2Form" action="admin.php?section=pembayaran&spp_tab=spp2_mgmt" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pembayaran Infaq yang dipilih?');">
        <input type="hidden" name="bulk_delete_spp2" value="true">
        <div class="table-container">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">
                            <input type="checkbox" id="selectAllSpp2" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                        </th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Minggu Ke-</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tgl Mulai Minggu</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tgl Akhir Minggu</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jumlah</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Admin Input</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Waktu Input</th>
                        <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($spp2_payments_summary as $row): ?>
                        <tr>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <input type="checkbox" name="selected_spp2_ids[]" value="<?= htmlspecialchars($row['id_pembayaran_spp_kedua']) ?>" class="spp2-checkbox form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-center"><?= htmlspecialchars($row['minggu_ke_akademik']) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($row['tanggal_mulai_minggu'])) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($row['tanggal_akhir_minggu'])) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_by_admin_username'] ?? 'N/A') ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_at_pembayaran']) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-center">
                                <a href="admin.php?section=pembayaran&spp_tab=spp2_mgmt&delete_spp2_group=true&id_siswa=<?= htmlspecialchars($row['id_siswa']) ?>&minggu=<?= htmlspecialchars($row['minggu_ke_akademik']) ?>&tahun=<?= htmlspecialchars($row['tahun_akademik_mulai']) ?>"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus pembayaran Infaq untuk siswa <?= htmlspecialchars($row['nama_siswa']) ?> minggu ke-<?= htmlspecialchars($row['minggu_ke_akademik']) ?>?');"
                                    class="btn btn-danger py-1 px-3 text-xs">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-danger mt-4">Hapus yang Dipilih</button>
    </form>

    <!-- Pagination SPP2 -->
    <div class="mt-6 flex justify-center items-center space-x-2">
        <?php
        $total_pages_spp2 = ceil($total_spp2_records / $records_per_page);
        if ($total_pages_spp2 > 1) {
            // Previous Page
            if ($current_page > 1) {
                echo '<a href="' . create_pagination_link('admin.php', $current_page - 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">&laquo; Sebelumnya</a>';
            }

            // Page Numbers
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages_spp2, $current_page + 2);

            if ($start_page > 1) {
                echo '<a href="' . create_pagination_link('admin.php', 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">1</a>';
                if ($start_page > 2) {
                    echo '<span class="pagination-link">...</span>';
                }
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = ($i == $current_page) ? 'active' : '';
                echo '<a href="' . create_pagination_link('admin.php', $i, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link ' . $active_class . '">' . $i . '</a>';
            }

            if ($end_page < $total_pages_spp2) {
                if ($end_page < $total_pages_spp2 - 1) {
                    echo '<span class="pagination-link">...</span>';
                }
                echo '<a href="' . create_pagination_link('admin.php', $total_pages_spp2, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">' . $total_pages_spp2 . '</a>';
            }

            // Next Page
            if ($current_page < $total_pages_spp2) {
                echo '<a href="' . create_pagination_link('admin.php', $current_page + 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">Selanjutnya &raquo;</a>';
            }
        }
        ?>
    </div>
<?php else: ?>
    <p class="text-gray-600">Belum ada ringkasan pembayaran Infaq.</p>
<?php endif; ?>