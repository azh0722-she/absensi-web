<?php
include 'koneksi.php';
session_start();

// Ambil data guru untuk dropdown wali kelas
$guru_query = mysqli_query($conn, "SELECT id, nama_lengkap FROM guru ORDER BY nama_lengkap ASC");
$guru_list = [];
while ($row = mysqli_fetch_assoc($guru_query)) {
    $guru_list[] = $row;
}

// Ambil data siswa yang belum masuk ke kelas manapun
$siswa_query = mysqli_query($conn, "
    SELECT id, nama_lengkap, nis 
    FROM siswa 
    WHERE id NOT IN (SELECT siswa_id FROM siswa_kelas) 
    ORDER BY nama_lengkap ASC
");
$siswa_list = [];
while ($row = mysqli_fetch_assoc($siswa_query)) {
    $siswa_list[] = $row;
}

// Proses simpan kelas
if (isset($_POST['simpan'])) {
    $jurusan = $_POST['jurusan'];
    $tingkat = $_POST['tingkat'];
    $wali_kelas_id = $_POST['wali_kelas_id'];
    $siswa_ids = $_POST['siswa_ids'] ?? [];

    // 1️⃣ Tambah data kelas ke tabel kelas
    $query_kelas = "INSERT INTO kelas (jurusan, tingkat, wali_kelas_id) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query_kelas);
    mysqli_stmt_bind_param($stmt, "ssi", $jurusan, $tingkat, $wali_kelas_id);
    $success_kelas = mysqli_stmt_execute($stmt);

    if ($success_kelas) {
        $kelas_id = mysqli_insert_id($conn);

        // 2️⃣ Masukkan semua siswa terpilih ke tabel siswa_kelas
        if (!empty($siswa_ids)) {
            $stmt_siswa = mysqli_prepare($conn, "INSERT INTO siswa_kelas (kelas_id, siswa_id) VALUES (?, ?)");
            foreach ($siswa_ids as $sid) {
                mysqli_stmt_bind_param($stmt_siswa, "ii", $kelas_id, $sid);
                mysqli_stmt_execute($stmt_siswa);
            }
        }

        echo "<script>alert('Kelas dan daftar siswa berhasil disimpan!'); window.location='data_kelas.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data kelas.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Kelas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
  font-family: "Poppins", sans-serif;
  background: linear-gradient(135deg, #1e293b, #0f172a);
  color: #fff;
  min-height: 100vh;
}
.container {
  max-width: 900px;
  background: rgba(255, 255, 255, 0.1);
  padding: 25px;
  border-radius: 15px;
  margin-top: 40px;
}
h2 {
  color: #fff;
  text-align: center;
  margin-bottom: 25px;
}
label { font-weight: 500; color: #fff; }
.form-check-label { color: #fff; }
.btn-primary { background-color: #0ea5e9; border: none; }
.btn-primary:hover { background-color: #0284c7; }
</style>
</head>
<body>

<div class="container">
  <h2><i class="bi bi-building-add"></i> TAMBAH KELAS BARU</h2>

  <form method="POST" action="">
    <div class="mb-3">
      <label for="jurusan" class="form-label">Jurusan</label>
      <select name="jurusan" id="jurusan" class="form-select" required>
        <option value="">-- Pilih Jurusan --</option>
        <option value="IPA 1">IPA (ILMU PENGETAHUAN ALAM) 1</option>
        <option value="IPA 2">IPA (ILMU PENGETAHUAN ALAM) 2</option>
        <option value="IPA 3">IPA (ILMU PENGETAHUAN ALAM) 3</option>
        <option value="IPA 4">IPA (ILMU PENGETAHUAN ALAM) 4</option>
        <option value="IPA 5">IPA (ILMU PENGETAHUAN ALAM) 5</option>
        <option value="IPA 6">IPA (ILMU PENGETAHUAN ALAM) 6</option>
        <option value="IPS 1">IPS (ILMU PENGETAHUAN SOSIAL) 1</option>
        <option value="IPS 2">IPS (ILMU PENGETAHUAN SOSIAL) 2</option>
        <option value="IPS 3">IPS (ILMU PENGETAHUAN SOSIAL) 3</option>
        <option value="IPS 4">IPS (ILMU PENGETAHUAN SOSIAL) 4</option>
        <option value="IPS 5">IPS (ILMU PENGETAHUAN SOSIAL) 5</option>
        <option value="IPS 6">IPS (ILMU PENGETAHUAN SOSIAL) 6</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="tingkat" class="form-label">Kelas</label>
      <select name="tingkat" id="tingkat" class="form-select" required>
        <option value="">-- Pilih Kelas --</option>
        <option value="10">10</option>
        <option value="11">11</option>
        <option value="12">12</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="wali_kelas_id" class="form-label"> --Wali Kelas-- </label>
      <select name="wali_kelas_id" id="wali_kelas_id" class="form-select" required>
        <option value="">-- Pilih Wali Kelas --</option>
        <?php foreach ($guru_list as $g): ?>
          <option value="<?= $g['id']; ?>"><?= htmlspecialchars($g['nama_lengkap']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label"> --Pilih Siswa untuk Kelas Ini-- </label>
      <div class="border rounded p-3" style="max-height: 300px; overflow-y: scroll; background: rgba(255,255,255,0.05);">
        <?php if (!empty($siswa_list)): ?>
          <?php foreach ($siswa_list as $s): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="siswa_ids[]" value="<?= $s['id']; ?>" id="siswa<?= $s['id']; ?>">
              <label class="form-check-label" for="siswa<?= $s['id']; ?>">
                <?= htmlspecialchars($s['nama_lengkap']) ?> (<?= htmlspecialchars($s['nis']) ?>)
              </label>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-center"><em>Semua siswa sudah masuk ke daftar kelas.</em></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="text-center">
      <button type="submit" name="simpan" class="btn btn-primary px-4"><i class="bi bi-save"></i> Simpan</button>
      <a href="data_kelas.php" class="btn btn-secondary">Kembali</a>
    </div>
  </form>
</div>

</body>
</html>
