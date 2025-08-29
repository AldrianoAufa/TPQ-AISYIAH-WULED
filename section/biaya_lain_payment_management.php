 <?php
// sections/biaya_lain_payment_management.php
// Pastikan variabel PHP yang dibutuhkan sudah didefinisikan di admin.php sebelum include ini

// Variabel yang dibutuhkan:
// $academic_start_year
// $academic_end_year
// $siswa_list
// $biaya_lain_list_dropdown
// $records_per_page
// $order_by
// $search_query
// $biaya_lain_payments_summary
// $total_biaya_lain_records
// $current_page
// create_pagination_link (function)
// format_date_indo (function)
?>
<h3 class="text-xl font-bold text-gray-800 mb-4">Pembayaran Biaya Lain (Tahun Akademik <?= $academic_start_year ?>/<?= $academic_end_year ?>)</h3>

<div class="bg-gray-50 p-4 rounded-lg shadow-sm mb-6">
    <h4 class="text-lg font-semibold text-gray-700 mb-3">Input Pembayaran Biaya Lain</h4>
    <form action="admin.php?section=pembayaran&spp_tab=biaya_lain_mgmt" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="form-group">
            <label for="id_siswa_pay_biaya_lain">Pilih Siswa:</label>
            <select id="id_siswa_pay_biaya_lain" name="id_siswa_pay_biaya_lain" required>
                <option value="">-- Pilih Siswa --</option>
                <?php foreach ($siswa_list as $siswa): ?>
                    <option value="<?= htmlspecialchars($siswa['id_siswa']) ?>"><?= htmlspecialchars($siswa['nama_siswa']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="id_biaya_type">Pilih Jenis Biaya:</label>
            <select id="id_biaya_type" name="id_biaya_type" required>
                <option value="">-- Pilih Biaya --</option>
                <?php foreach ($biaya_lain_list_dropdown as $biaya): ?>
                    <option value="<?= htmlspecialchars($biaya['id_biaya']) ?>"><?= htmlspecialchars($biaya['nama_biaya']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="jumlah_dibayar_biaya_lain">Jumlah Dibayar (Rp):</label>
            <input type="number" id="jumlah_dibayar_biaya_lain" name="jumlah_dibayar_biaya_lain" placeholder="Contoh: 50000" required min="0">
        </div>
        <div class="form-group">
            <label for="tanggal_pembayaran_biaya_lain">Tanggal Pembayaran:</label>
            <input type="date" id="tanggal_pembayaran_biaya_lain" name="tanggal_pembayaran_biaya_lain" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="flex items-center col-span-full md:col-span-1">
            <button type="submit" name="pay_biaya_lain" class="btn btn-success w-full">Catat Pembayaran</button>
        </div>
    </form>
</div>

<div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
    <!-- Search Bar -->
    <form action="admin.php" method="GET" class="w-full md:w-1/3">
        <input type="hidden" name="section" value="pembayaran">
        <input type="hidden" name="spp_tab" value="biaya_lain_mgmt">
        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
        <input type="text" name="search" placeholder="Cari nama siswa atau biaya..." value="<?= htmlspecialchars($search_query) ?>">
    </form>

    <!-- Sort By -->
    <form action="admin.php" method="GET" class="w-full md:w-1/4">
        <input type="hidden" name="section" value="pembayaran">
        <input type="hidden" name="spp_tab" value="biaya_lain_mgmt">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
        <label for="order_by_biaya_lain" class="sr-only">Urutkan Berdasarkan:</label>
        <select name="order_by" id="order_by_biaya_lain" onchange="this.form.submit()">
            <option value="tanggal_desc" <?= ($order_by == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
            <option value="tanggal_asc" <?= ($order_by == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terlama</option>
            <option value="nama_siswa_asc" <?= ($order_by == 'nama_siswa_asc') ? 'selected' : '' ?>>Nama Siswa (A-Z)</option>
            <option value="nama_siswa_desc" <?= ($order_by == 'nama_siswa_desc') ? 'selected' : '' ?>>Nama Siswa (Z-A)</option>
        </select>
    </form>

    <!-- Records per page -->
    <form action="admin.php" method="GET" class="w-full md:w-1/4">
        <input type="hidden" name="section" value="pembayaran">
        <input type="hidden" name="spp_tab" value="biaya_lain_mgmt">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
        <label for="per_page_biaya_lain" class="sr-only">Data per halaman:</label>
        <select name="per_page" id="per_page_biaya_lain" onchange="this.form.submit()">
            <option value="10" <?= ($records_per_page == 10) ? 'selected' : '' ?>>10 per halaman</option>
            <option value="20" <?= ($records_per_page == 20) ? 'selected' : '' ?>>20 per halaman</option>
            <option value="50" <?= ($records_per_page == 50) ? 'selected' : '' ?>>50 per halaman</option>
            <option value="1000" <?= ($records_per_page == 1000) ? 'selected' : '' ?>>1000 per halaman</option>
            <option value="10000" <?= ($records_per_page == 10000) ? 'selected' : '' ?>>10000 per halaman</option>
            <option value="100000" <?= ($records_per_page == 100000) ? 'selected' : '' ?>>100000 per halaman</option>
        </select>
    </form>
</div>

<?php if (!empty($biaya_lain_payments_summary)): ?>
    <form id="bulkDeleteBiayaLainForm" action="admin.php?section=pembayaran&spp_tab=biaya_lain_mgmt" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pembayaran biaya lain yang dipilih?');">
        <input type="hidden" name="bulk_delete_biaya_lain" value="true">
        <div class="table-container">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">
                            <input type="checkbox" id="selectAllBiayaLain" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                        </th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jenis Biaya</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jumlah Dibayar</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tanggal Pembayaran</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Admin Input</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Waktu Input</th>
                        <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($biaya_lain_payments_summary as $row): ?>
                        <tr>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <input type="checkbox" name="selected_biaya_lain_ids[]" value="<?= htmlspecialchars($row['id_pembayaran_biaya_lain']) ?>" class="biaya-lain-checkbox form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_biaya']) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($row['jumlah_dibayar'], 0, ',', '.') ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($row['tanggal_pembayaran'])) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_by_admin_username'] ?? 'N/A') ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_at_pembayaran']) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-center">
                                <span class="text-gray-500 text-xs">Hapus via checkbox</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-danger mt-4">Hapus yang Dipilih</button>
    </form>

    <!-- Pagination Biaya Lain -->
    <div class="mt-6 flex justify-center items-center space-x-2">
        <?php
        $total_pages_biaya_lain = ceil($total_biaya_lain_records / $records_per_page);
        if ($total_pages_biaya_lain > 1) {
            // Previous Page
            if ($current_page > 1) {
                echo '<a href="' . create_pagination_link('admin.php', $current_page - 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">&laquo; Sebelumnya</a>';
            }

            // Page Numbers
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages_biaya_lain, $current_page + 2);

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

            if ($end_page < $total_pages_biaya_lain) {
                if ($end_page < $total_pages_biaya_lain - 1) {
                    echo '<span class="pagination-link">...</span>';
                }
                echo '<a href="' . create_pagination_link('admin.php', $total_pages_biaya_lain, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">' . $total_pages_biaya_lain . '</a>';
            }

            // Next Page
            if ($current_page < $total_pages_biaya_lain) {
                echo '<a href="' . create_pagination_link('admin.php', $current_page + 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">Selanjutnya &raquo;</a>';
            }
        }
        ?>
    </div>
<?php else: ?>
    <p class="text-gray-600">Belum ada pembayaran biaya lain.</p>
<?php endif; ?>