 <?php
// sections/biaya_lain_management.php
// Pastikan variabel PHP yang dibutuhkan sudah didefinisikan di admin.php sebelum include ini

// Variabel yang dibutuhkan:
// $edit_data
// $biaya_lain_list
?>
<h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Biaya Lain (Master Data)</h2>
<p class="text-gray-600 mb-6">Kelola jenis-jenis biaya lain yang dapat dibayarkan oleh siswa. Ini adalah daftar master, bukan catatan pembayaran per siswa.</p>

<!-- Form Tambah/Edit Biaya Lain -->
<form action="admin.php?section=biaya_lain" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
    <h3 class="text-xl font-semibold mb-4"><?= !empty($edit_data) ? 'Edit Biaya Lain' : 'Tambah Biaya Lain Baru' ?></h3>
    <?php if (!empty($edit_data)): ?>
        <input type="hidden" name="id_biaya" value="<?= htmlspecialchars($edit_data['id_biaya'] ?? '') ?>">
    <?php endif; ?>
    <div class="form-group">
        <label for="nama_biaya">Nama Biaya:</label>
        <input type="text" id="nama_biaya" name="nama_biaya" value="<?= htmlspecialchars($edit_data['nama_biaya'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label for="jumlah">Jumlah Default (Rp):</label>
        <input type="number" id="jumlah" name="jumlah" value="<?= htmlspecialchars($edit_data['jumlah'] ?? '') ?>" required min="0">
    </div>
    <div class="flex space-x-2">
        <button type="submit" name="<?= !empty($edit_data) ? 'edit_biaya_lain' : 'add_biaya_lain' ?>"
                class="btn btn-primary">
            <?= !empty($edit_data) ? 'Perbarui Biaya' : 'Tambah Biaya' ?>
        </button>
        <?php if (!empty($edit_data)): ?>
            <a href="admin.php?section=biaya_lain" class="btn bg-gray-400 hover:bg-gray-500 text-white">
                Batal
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- Daftar Biaya Lain -->
<h3 class="text-xl font-semibold mb-4">Daftar Biaya Lain</h3>
<?php if (!empty($biaya_lain_list)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
            <thead class="bg-gray-200">
                <tr>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">ID Biaya</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Biaya</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jumlah Default</th>
                    <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($biaya_lain_list as $biaya): ?>
                    <tr>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($biaya['id_biaya']) ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($biaya['nama_biaya']) ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($biaya['jumlah'], 0, ',', '.') ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-center">
                            <button onclick="openEditModal('editBiayaModal', {id: <?= $biaya['id_biaya'] ?>, nama: '<?= htmlspecialchars($biaya['nama_biaya']) ?>', jumlah: <?= $biaya['jumlah'] ?>})"
                                    class="btn btn-warning py-1 px-3 text-xs">Edit</button>
                            <a href="admin.php?section=biaya_lain&delete_biaya_lain=<?= htmlspecialchars($biaya['id_biaya']) ?>"
                               onclick="return confirm('Apakah Anda yakin ingin menghapus biaya <?= htmlspecialchars($biaya['nama_biaya']) ?>? Ini akan menghapus semua data pembayaran terkait!');"
                               class="btn btn-danger py-1 px-3 text-xs">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-gray-600">Belum ada data biaya lain.</p>
<?php endif; ?>