 <?php
// sections/siswa_management.php
// Pastikan variabel PHP yang dibutuhkan sudah didefinisikan di admin.php sebelum include ini

// Variabel yang dibutuhkan:
// $edit_data
// $siswa_list
?>
<h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Siswa</h2>

<!-- Form Tambah/Edit Siswa -->
<form action="admin.php?section=siswa" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
    <h3 class="text-xl font-semibold mb-4"><?= !empty($edit_data) ? 'Edit Siswa' : 'Tambah Siswa Baru' ?></h3>
    <?php if (!empty($edit_data)): ?>
        <input type="hidden" name="id_siswa" value="<?= htmlspecialchars($edit_data['id_siswa'] ?? '') ?>">
    <?php endif; ?>
    <div class="form-group">
        <label for="nama_siswa">Nama Siswa:</label>
        <input type="text" id="nama_siswa" name="nama_siswa" value="<?= htmlspecialchars($edit_data['nama_siswa'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label for="login_code">Kode Login (Username & Password Siswa):</label>
        <input type="text" id="login_code" name="login_code" value="<?= htmlspecialchars($edit_data['login_code'] ?? '') ?>" required>
        <p class="text-sm text-gray-500 mt-1">Kode ini akan digunakan siswa untuk login. Pastikan unik.</p>
    </div>
    <div class="flex space-x-2">
        <button type="submit" name="<?= !empty($edit_data) ? 'edit_siswa' : 'add_siswa' ?>"
                class="btn btn-primary">
            <?= !empty($edit_data) ? 'Perbarui Siswa' : 'Tambah Siswa' ?>
        </button>
        <?php if (!empty($edit_data)): ?>
            <a href="admin.php?section=siswa" class="btn bg-gray-400 hover:bg-gray-500 text-white">
                Batal
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- Fitur Impor Siswa -->
<div class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
    <h3 class="text-xl font-semibold mb-4">Impor Data Siswa (dari CSV)</h3>
    <p class="text-gray-600 text-sm mb-3">
        Unggah file CSV dengan dua kolom: "nama_siswa" dan "login_code" (tanpa header).
        Pastikan kode login unik untuk setiap siswa.
        Contoh format CSV:
    </p>
    <pre class="bg-gray-100 p-2 rounded-md text-sm mb-4 border border-gray-300">
nama_siswa_1,kode_1
nama_siswa_2,kode_2
nama_siswa_3,kode_3</pre>
    <form action="import_siswa.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csv_file">Pilih File CSV:</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        </div>
        <button type="submit" name="import_siswa" class="btn btn-primary">
            Impor Siswa dari CSV
        </button>
    </form>
</div>

<!-- Daftar Siswa -->
<h3 class="text-xl font-semibold mb-4">Daftar Siswa</h3>
<?php if (!empty($siswa_list)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
            <thead class="bg-gray-200">
                <tr>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">ID Siswa</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kode Login</th>
                    <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($siswa_list as $siswa): ?>
                    <tr>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($siswa['id_siswa']) ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-gray-800 font-mono"><?= htmlspecialchars($siswa['login_code']) ?></td>
                        <td class="py-3 px-4 whitespace-nowrap text-center">
                            <button onclick="openEditModal('editSiswaModal', {id: <?= $siswa['id_siswa'] ?>, nama: '<?= htmlspecialchars($siswa['nama_siswa']) ?>', loginCode: '<?= htmlspecialchars($siswa['login_code']) ?>'})"
                                    class="btn btn-warning py-1 px-3 text-xs">Edit</button>
                            <a href="admin.php?section=siswa&delete_siswa=<?= htmlspecialchars($siswa['id_siswa']) ?>"
                               onclick="return confirm('Apakah Anda yakin ingin menghapus siswa <?= htmlspecialchars($siswa['nama_siswa']) ?>? Ini akan menghapus semua data pembayaran terkait!');"
                               class="btn btn-danger py-1 px-3 text-xs">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-gray-600">Belum ada data siswa.</p>
<?php endif; ?>