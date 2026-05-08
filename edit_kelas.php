<?php
include 'koneksi.php';
session_start();

// Cek apakah ada ID kelas yang dikirim
if (!isset($_GET['id'])) {
    echo "<script>alert('Kelas tidak ditemukan!'); window.location='data_kelas.php';</script>";
    exit;
}
$id = $_GET['id'];

// Ambil data kelas
$query_kelas = mysqli_query($conn, "
    SELECT * FROM kelas WHERE id = '$id'
");
$kelas = mysqli_fetch_assoc($query_kelas);
if (!$kelas) {
    echo "<script>alert('Data kelas tidak ditemukan!'); window.location='data_kelas.php';</script>";
    exit;
}

// Ambil semua guru untuk dropdown wali kelas
$guru_query = mysqli_query($conn, "SELECT id, nama_lengkap FROM guru ORDER BY nama_lengkap ASC");
$guru_list = [];
while ($g = mysqli_fetch_assoc($guru_query)) {
    $guru_list[] = $g;
}

// Ambil semua siswa
$siswa_query = mysqli_query($conn, "SELECT id, nama_lengkap, nis FROM siswa ORDER BY nama_lengkap ASC");
$siswa_list = [];
while ($s = mysqli_fetch_assoc($siswa_query)) {
    $siswa_list[] = $s;
}

// Ambil siswa yang sudah ada di kelas ini
$siswa_kelas_query = mysqli_query($conn, "SELECT siswa_id FROM siswa_kelas WHERE kelas_id = '$id'");
$siswa_di_kelas = [];
while ($sk = mysqli_fetch_assoc($siswa_kelas_query)) {
    $siswa_di_kelas[] = $sk['siswa_id'];
}

// Proses simpan perubahan
if (isset($_POST['simpan'])) {
    $jurusan = $_POST['jurusan'];
    $tingkat = $_POST['tingkat'];
    $wali_kelas_id = $_POST['wali_kelas_id'];
    $siswa_ids = isset($_POST['siswa']) ? $_POST['siswa'] : [];

    // Update tabel kelas
    $update_kelas = "
        UPDATE kelas 
        SET jurusan = '$jurusan', tingkat = '$tingkat', wali_kelas_id = '$wali_kelas_id'
        WHERE id = '$id'
    ";
    mysqli_query($conn, $update_kelas);

    // Hapus data siswa lama, lalu masukkan siswa baru
    mysqli_query($conn, "DELETE FROM siswa_kelas WHERE kelas_id = '$id'");
    foreach ($siswa_ids as $sid) {
        mysqli_query($conn, "INSERT INTO siswa_kelas (kelas_id, siswa_id) VALUES ('$id', '$sid')");
    }

    echo "<script>alert('Data kelas berhasil diperbarui!'); window.location='data_kelas.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Kelas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #0f172a, #1e293b);
  color: #fff;
  font-family: "Poppins", sans-serif;
  min-height: 100vh;
}
.container {
  max-width: 900px;
  margin-top: 40px;
  background: rgba(255,255,255,0.1);
  padding: 25px;
  border-radius: 15px;
}
h2 {
  text-align: center;
  margin-bottom: 25px;
  color: #fff;
}
.form-select, .form-control {
  background-color: rgba(255,255,255,0.1);
  color: #fff;
  border: none;
}
.form-select option {
  color: #000;
}
.siswa-box {
  max-height: 250px;
  overflow-y: auto;
  background: rgba(255,255,255,0.05);
  border-radius: 10px;
  padding: 10px;
}
.siswa-item {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
}
.siswa-item input {
  margin-right: 10px;
}
.btn {
  border-radius: 8px;
}
</style>
</head>
<body>

<div class="container">
  <h2><i class="bi bi-pencil-square"></i> Edit Kelas</h2>

  <form method="POST" action="">
    <div class="mb-3">
      <label for="jurusan" class="form-label">Jurusan</label>
      <select id="jurusan" name="jurusan" class="form-select" required>
        <option value="IPA 1" <?= $kelas['jurusan']=='IPA 1'?'selected':'' ?>>IPA 1</option>
        <option value="IPA 2" <?= $kelas['jurusan']=='IPA 2'?'selected':'' ?>>IPA 2</option>
        <option value="IPA 3" <?= $kelas['jurusan']=='IPA 3'?'selected':'' ?>>IPA 3</option>
        <option value="IPS 1" <?= $kelas['jurusan']=='IPS 1'?'selected':'' ?>>IPS 1</option>
        <option value="IPS 2" <?= $kelas['jurusan']=='IPS 2'?'selected':'' ?>>IPS 2</option>
        <option value="IPS 3" <?= $kelas['jurusan']=='IPS 3'?'selected':'' ?>>IPS 3</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="tingkat" class="form-label">Tingkat</label>
      <select id="tingkat" name="tingkat" class="form-select" required>
        <option value="10" <?= $kelas['tingkat']=='10'?'selected':'' ?>>10</option>
        <option value="11" <?= $kelas['tingkat']=='11'?'selected':'' ?>>11</option>
        <option value="12" <?= $kelas['tingkat']=='12'?'selected':'' ?>>12</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="wali_kelas_id" class="form-label">Wali Kelas</label>
      <select id="wali_kelas_id" name="wali_kelas_id" class="form-select" required>
        <option value="">-- Pilih Wali Kelas --</option>
        <?php foreach ($guru_list as $g): ?>
          <option value="<?= $g['id']; ?>" <?= $kelas['wali_kelas_id']==$g['id']?'selected':'' ?>>
            <?= htmlspecialchars($g['nama_lengkap']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Daftar Siswa</label>
      <div class="siswa-box">
        <?php foreach ($siswa_list as $s): ?>
          <div class="siswa-item">
            <input type="checkbox" name="siswa[]" value="<?= $s['id']; ?>"
              <?= in_array($s['id'], $siswa_di_kelas) ? 'checked' : ''; ?>>
            <label><?= htmlspecialchars($s['nama_lengkap']); ?> <small>[<?= htmlspecialchars($s['nis']); ?>]</small></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="text-end">
      <a href="data_kelas.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
      <button type="submit" name="simpan" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
    </div>
  </form>
</div>

</body>
</html>
