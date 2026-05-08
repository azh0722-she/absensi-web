<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';

// Jika tombol simpan ditekan
if (isset($_POST['simpan'])) {
    $nis = $_POST['nis'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tempat_lahir = $_POST['tempat_lahir'] ?? '';
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $tahun_masuk = $_POST['tahun_masuk'] ?? '';
    $no_telp = $_POST['no_telp'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $status = "aktif";

    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $foto_name = time() . "_" . basename($_FILES['foto']['name']);
        $foto = $target_dir . $foto_name;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto);
    }

    // 🔍 Cek apakah NIS sudah ada
    $cek = mysqli_query($conn, "SELECT * FROM siswa WHERE nis = '$nis'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<div class='alert alert-warning text-center mt-3'>❗ NIS <b>" . htmlspecialchars($nis) . "</b> sudah terdaftar. Gunakan NIS lain.</div>";
    } else {
        // ✅ Simpan siswa tanpa input kelas/jurusan
        $query = "INSERT INTO siswa 
            (nis, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, tahun_masuk, no_telp, alamat, foto, status, created_at, updated_at)
            VALUES 
            ('$nis', '$nama_lengkap', '$jenis_kelamin', '$tempat_lahir', '$tanggal_lahir', '$tahun_masuk', '$no_telp', '$alamat', '$foto', '$status', NOW(), NOW())";

        if (mysqli_query($conn, $query)) {
            header("Location: data_siswa.php");
            exit;
        } else {
            echo "<div class='alert alert-danger text-center mt-3'>Gagal menyimpan data: " . mysqli_error($conn) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Siswa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
  font-family: "Poppins", sans-serif;
  background: linear-gradient(135deg, #1e293b, #0f172a);
  color: #fff3f3;
  min-height: 100vh;
}
.container { padding-top: 60px; }
.card {
  background: rgba(172,172,172,0.85);
  border-radius: 15px;
  border: 1px solid rgba(255,255,255,0.05);
  backdrop-filter: blur(10px);
  box-shadow: 0 0 20px rgba(0,0,0,0.3);
}
.form-control, textarea, .form-select {
  background: rgba(255,255,255,0.1);
  color: #000;
  border: 1px solid rgba(255,255,255,0.2);
}
.form-control:focus, textarea:focus, .form-select:focus {
  background: rgba(255,255,255,0.15);
  color: #000;
  box-shadow: none;
  border-color: #fff;
}
.btn-success { background-color: #28a745; border-color: #28a745; }
.btn-outline-light { color: #fff; border-color: #fff; }
h4 { color: #fff; }
</style>
</head>
<body>
<div class="container">
  <div class="card p-4 shadow-lg">
    <h4 class="mb-3"><i class="bi bi-person-plus"></i> Tambah Data Siswa</h4>
    <form method="post" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">NIS</label>
          <input type="text" name="nis" class="form-control" required pattern="[0-9]+" title="Hanya boleh angka">
        </div>
        <div class="col-md-6">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Jenis Kelamin</label>
          <select name="jenis_kelamin" class="form-select" required>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tempat Lahir</label>
          <input type="text" name="tempat_lahir" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Tahun Masuk</label>
          <input type="number" name="tahun_masuk" class="form-control" placeholder="2025" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">No. Telepon</label>
          <input type="text" name="no_telp" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Alamat</label>
          <textarea name="alamat" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Foto (opsional)</label>
          <input type="file" name="foto" class="form-control" accept="image/*">
        </div>
      </div>
      <div class="mt-4 d-flex justify-content-between">
        <a href="data_siswa.php" class="btn btn-outline-light">Kembali</a>
        <button type="submit" name="simpan" class="btn btn-success">
          <i class="bi bi-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
