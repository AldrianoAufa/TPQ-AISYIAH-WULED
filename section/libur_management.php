 <?php
// sections/libur_management.php
// Pastikan variabel PHP yang dibutuhkan sudah didefinisikan di admin.php sebelum include ini

// Variabel yang dibutuhkan:
// $edit_data
// $libur_list
// format_date_indo (function)
// $academic_start_year
// $academic_end_year
?>
<h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Hari Libur</h2>

<!-- Form Tambah/Edit Hari Libur (Individual) -->
<form action="admin.php?section=libur" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
    <h3 class="text-xl font-semibold mb-4"><?= !empty($edit_data) ? 'Edit Hari Libur (Individual)' : 'Tambah Hari Libur Baru (Individual)' ?></h3>
    <?php if (!empty($edit_data)): ?>
        <input type="hidden" name="id_libur" value="<?= htmlspecialchars($edit_data['id_libur'] ?? '') ?>">
    <?php endif; ?>
    <div class="form-group">
        <label for="tanggal">Tanggal:</label>
        <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($edit_data['tanggal'] ?? date('Y-m-d')) ?>" required>
    </div>
    <div class="form-group">
        <label for="keterangan">Keterangan:</label>
        <input type="text" id="keterangan" name="keterangan" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>" placeholder="Contoh: Libur Nasional" required>
    </div>
    <div class="flex space-x-2">
        <button type="submit" name="<?= !empty($edit_data) ? 'edit_libur' : 'add_libur' ?>"
                class="btn btn-primary">
            <?= !empty($edit_data) ? 'Perbarui Libur' : 'Tambah Libur' ?>
        </button>
        <?php if (!empty($edit_data)): ?>
            <a href="admin.php?section=libur" class="btn bg-gray-400 hover:bg-gray-500 text-white">
                Batal
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- Form Tambah Hari Libur Per Rentang -->
<form action="admin.php?section=libur" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
    <h3 class="text-xl font-semibold mb-4">Tambah Hari Libur Per Rentang Tanggal</h3>
    <div class="form-group">
        <label for="tanggal_mulai_libur">Tanggal Mulai:</label>
        <input type="date" id="tanggal_mulai_libur" name="tanggal_mulai_libur" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="form-group">
        <label for="tanggal_akhir_libur">Tanggal Akhir:</label>
        <input type="date" id="tanggal_akhir_libur" name="tanggal_akhir_libur" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="form-group">
        <label for="keterangan_libur_range">Keterangan:</label>
        <input type="text" id="keterangan_libur_range" name="keterangan_libur_range" placeholder="Contoh: Libur Semesteran" required>
    </div>
    <button type="submit" name="add_libur_range" class="btn btn-success">
        Tambah Libur Rentang
    </button>
</form>

<div class="card p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Hari Libur (Tahun Akademik <?= htmlspecialchars($academic_start_year) ?> - <?= htmlspecialchars($academic_end_year) ?>)</h3>
    <?php if (!empty($libur_list)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">ID Libur</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Keterangan</th>
                        <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($libur_list as $libur): ?>
                        <tr>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($libur['id_libur']) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($libur['tanggal'])) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($libur['keterangan']) ?></td>
                            <td class="py-3 px-4 whitespace-nowrap text-center">
                                <button onclick="openEditModal('editLiburModal', {id: <?= $libur['id_libur'] ?>, tanggal: '<?= htmlspecialchars($libur['tanggal']) ?>', keterangan: '<?= htmlspecialchars($libur['keterangan']) ?>'})"
                                        class="btn btn-warning py-1 px-3 text-xs">Edit</button>
                                <a href="admin.php?section=libur&delete_libur=<?= htmlspecialchars($libur['id_libur']) ?>"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus hari libur <?= htmlspecialchars(format_date_indo($libur['tanggal'])) ?>?');"
                                   class="btn btn-danger py-1 px-3 text-xs">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-600">Belum ada data hari libur untuk tahun akademik ini.</p>
    <?php endif; ?>
</div>