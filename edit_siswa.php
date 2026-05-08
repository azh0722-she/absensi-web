<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    $query = mysqli_query($conn, "SELECT * FROM siswa WHERE id='$id'");
    $siswa = mysqli_fetch_assoc($query);
    if (!$siswa) die("Data siswa tidak ditemukan, Yang Mulia.");
} else {
    die("ID siswa tidak ditemukan, Yang Mulia.");
}

if (isset($_POST['edit'])) {
    $nis = $_POST['nis'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jurusan = $_POST['jurusan'];
    $tahun_masuk = $_POST['tahun_masuk'];
    $no_telp = $_POST['no_telp'];
    $alamat = $_POST['alamat'];
    $status = $_POST['status'];

    $foto = $siswa['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $target_dir = "uploads/";
        $file_name = time() . "_" . basename($_FILES['foto']['name']);
        $target_file = $target_dir . $file_name;
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_file);
        $foto = $file_name;
    }

    $sql = "UPDATE siswa SET 
        nis='$nis',
        nama_lengkap='$nama_lengkap',
        jenis_kelamin='$jenis_kelamin',
        tempat_lahir='$tempat_lahir',
        tanggal_lahir='$tanggal_lahir',
        jurusan='$jurusan',
        tahun_masuk='$tahun_masuk',
        no_telp='$no_telp',
        alamat='$alamat',
        foto='$foto',
        status='$status',
        updated_at=NOW()
        WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        header('Location: data_siswa.php');
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan perubahan, Yang Mulia.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Data Siswa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body {
  font-family: "Poppins", sans-serif;
  background: linear-gradient(135deg,#1e293b,#0f172a);
  color: #fff;
  min-height: 100vh;
}
.container {
  max-width: 700px;
  margin: 50px auto;
}
.card {
  background: rgba(172,172,172,0.85);
  border-radius: 15px;
  border: 1px solid rgba(255,255,255,0.05);
  backdrop-filter: blur(10px);
  padding: 30px;
  box-shadow: 0 0 20px rgba(0,0,0,0.3);
}
label {
  font-weight: 500;
  color: #fff;
  margin-top: 15px;
}
input, select, textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 6px;
  margin-top: 5px;
  background: rgba(255,255,255,0.1);
  color: #000;
}
input:focus, select:focus, textarea:focus {
  background: rgba(255,255,255,0.15);
  outline: none;
  border-color: #fff;
}
button, .btn-secondary {
  margin-top: 20px;
  padding: 10px 20px;
  font-weight: 500;
}
button.btn-success {
  background-color: #28a745;
  border-color: #28a745;
  color: #fff;
}
button.btn-success:hover {
  background-color: #218838;
}
.btn-secondary {
  background-color: transparent;
  color: #fff;
  border: 1px solid #fff;
}
.btn-secondary:hover {
  background-color: rgba(255,255,255,0.1);
  border-color: #fff;
  color: #fff;
}
.foto-preview {
  margin-top: 10px;
  width: 120px;
  border-radius: 6px;
}
</style>
</head>
<body>

<div class="container">
  <div class="card">
    <h4 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Data Siswa</h4>
    <form action="" method="POST" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6">
          <label>NIS</label>
          <input type="text" name="nis" value="<?= $siswa['nis'] ?>" required>
        </div>
        <div class="col-md-6">
          <label>Nama Lengkap</label>
          <input type="text" name="nama_lengkap" value="<?= $siswa['nama_lengkap'] ?>" required>
        </div>
        <div class="col-md-6">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin" required>
            <option value="L" <?= $siswa['jenis_kelamin']=='L'?'selected':'' ?>>Laki-laki</option>
            <option value="P" <?= $siswa['jenis_kelamin']=='P'?'selected':'' ?>>Perempuan</option>
          </select>
        </div>
        <div class="col-md-6">
          <label>Tempat Lahir</label>
          <input type="text" name="tempat_lahir" value="<?= $siswa['tempat_lahir'] ?>">
        </div>
        <div class="col-md-6">
          <label>Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" value="<?= $siswa['tanggal_lahir'] ?>">
        </div>
        <div class="col-md-6">
          <label>Jurusan</label>
          <select name="jurusan" required>
            <option value="IPA" <?= $siswa['jurusan']=='IPA'?'selected':'' ?>>IPA</option>
            <option value="IPS" <?= $siswa['jurusan']=='IPS'?'selected':'' ?>>IPS</option>
          </select>
        </div>
        <div class="col-md-6">
          <label>Tahun Masuk</label>
          <input type="number" name="tahun_masuk" value="<?= $siswa['tahun_masuk'] ?>" required>
        </div>
        <div class="col-md-6">
          <label>No. Telepon</label>
          <input type="text" name="no_telp" value="<?= $siswa['no_telp'] ?>">
        </div>
        <div class="col-12">
          <label>Alamat</label>
          <textarea name="alamat"><?= $siswa['alamat'] ?></textarea>
        </div>
        <div class="col-12">
          <label>Foto</label>
          <?php if ($siswa['foto']) { ?>
            <img src="uploads/<?= $siswa['foto'] ?>" class="foto-preview" alt="Foto Siswa">
          <?php } ?>
          <input type="file" name="foto" accept="image/*">
        </div>
        <div class="col-12">
          <label>Status</label>
          <select name="status">
            <option value="aktif" <?= $siswa['status']=='aktif'?'selected':'' ?>>Aktif</option>
            <option value="lulus" <?= $siswa['status']=='lulus'?'selected':'' ?>>Lulus</option>
            <option value="nonaktif" <?= $siswa['status']=='nonaktif'?'selected':'' ?>>Nonaktif</option>
            <option value="keluar" <?= $siswa['status']=='keluar'?'selected':'' ?>>Keluar</option>
          </select>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <a href="data_siswa.php" class="btn btn-secondary">Batal</a>
        <button type="submit" name="edit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
