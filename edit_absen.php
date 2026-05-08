<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Ambil data guru
$stmt = mysqli_prepare($conn, "SELECT * FROM guru WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$guru = mysqli_fetch_assoc($result);

$siswa_id = $_GET['siswa_id'] ?? '';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

if (!$siswa_id || !$tanggal) {
    die("Data tidak lengkap.");
}

// Ambil data absensi siswa
$stmt = mysqli_prepare($conn, "
    SELECT s.nama_lengkap, s.nis, a.status, a.keterangan
    FROM siswa s
    JOIN absensi a ON s.id = a.siswa_id
    WHERE s.id = ? AND a.tanggal = ?
");
mysqli_stmt_bind_param($stmt, "is", $siswa_id, $tanggal);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$absen = mysqli_fetch_assoc($res);

if (!$absen) {
    die("Data absensi tidak ditemukan.");
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'Alpha';
    $keterangan = $_POST['keterangan'] ?? '';

    $stmt = mysqli_prepare($conn, "
        UPDATE absensi SET status=?, keterangan=? 
        WHERE siswa_id=? AND tanggal=?
    ");
    mysqli_stmt_bind_param($stmt, "ssis", $status, $keterangan, $siswa_id, $tanggal);
    mysqli_stmt_execute($stmt);

    header("Location: daftar_hadir.php?tanggal=$tanggal");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Absensi - Sistem Absensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: "Poppins", sans-serif; background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; min-height: 100vh; }
.content { padding: 30px; animation: fadeIn 0.8s ease; }
.card { background: rgba(172,172,172,0.85); border:1px solid rgba(255,255,255,0.05); border-radius:15px; box-shadow:0 0 20px rgba(0,0,0,0.3); color:#f8fafc; }
.form-control, .form-select { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
.form-control:focus { background: rgba(255,255,255,0.15); color:#fff; box-shadow:none; border-color:#fff; }
.btn-primary { background-color:#3b82f6; border:none; }
.btn-success { background-color:#10b981; border:none; }
.btn-secondary { background-color:#6b7280; border:none; }
@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
</style>
</head>
<body>
<div class="d-flex">
  <div id="sidebar-container" class="col-2"></div>
  <div class="content flex-grow-1">
    <nav class="navbar navbar-expand-lg px-3 mb-4">
      <div class="container-fluid">
        <span class="navbar-brand text-light fw-semibold"><i class="bi bi-pencil-square"></i> Edit Absensi</span>
        <div class="d-flex align-items-center">
          <img src="<?= !empty($guru['foto']) ? $guru['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:8px;">
          <span><?= htmlspecialchars($guru['nama_lengkap']) ?> (<?= htmlspecialchars($role) ?>)</span>
        </div>
      </div>
    </nav>

    <div class="card p-4">
      <h5 class="fw-semibold mb-3"><i class="bi bi-person-lines-fill"></i> <?= htmlspecialchars($absen['nama_lengkap']) ?> (<?= htmlspecialchars($absen['nis']) ?>)</h5>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Status</label><br>
          <?php foreach(['Hadir','Izin','Alpha'] as $st): ?>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="status" value="<?= $st ?>" <?= $absen['status']==$st?'checked':'' ?>>
              <label class="form-check-label"><?= $st ?></label>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mb-3">
          <label class="form-label">Keterangan</label>
          <input type="text" class="form-control" name="keterangan" value="<?= htmlspecialchars($absen['keterangan']) ?>">
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
          <a href="daftar_hadir.php?tanggal=<?= $tanggal ?>" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Batal</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
fetch("sidebar.php")
  .then(res => res.text())
  .then(html => document.getElementById("sidebar-container").innerHTML = html);
</script>
</body>
</html>
