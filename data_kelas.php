<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Ambil data admin
$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Ambil data kelas dan wali kelas
$query_kelas = "
  SELECT k.id AS kelas_id, k.jurusan, k.tingkat, g.nama_lengkap AS wali_kelas
  FROM kelas k
  LEFT JOIN guru g ON k.wali_kelas_id = g.id
  ORDER BY k.tingkat, k.jurusan ASC
";
$result_kelas = mysqli_query($conn, $query_kelas);

// Ambil data siswa per kelas
$siswa_per_kelas = [];
$siswa_query = "
  SELECT sk.kelas_id, s.nama_lengkap, s.nis
  FROM siswa_kelas sk
  JOIN siswa s ON sk.siswa_id = s.id
  ORDER BY s.nama_lengkap ASC
";
$res_siswa = mysqli_query($conn, $siswa_query);
while ($row = mysqli_fetch_assoc($res_siswa)) {
  $siswa_per_kelas[$row['kelas_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Kelas - Sistem Absensi</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body {
  font-family: "Poppins", sans-serif;
  background: linear-gradient(135deg, #1e293b, #0f172a);
  color: #fff3f3;
  min-height: 100vh;
  overflow-x: hidden;
}
.content {
  padding: 30px;
  animation: fadeIn 0.8s ease;
}
.navbar {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.navbar img {
  width: 32px; height: 32px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 8px;
}
.card {
  background: rgba(172, 172, 172, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 15px;
  box-shadow: 0 0 20px rgba(0,0,0,0.3);
  color: #f8fafc;
}
.table {
  --bs-table-bg: transparent !important;
  --bs-table-striped-bg: rgba(255,255,255,0.05);
  --bs-table-hover-bg: rgba(255,255,255,0.1);
  color: #e2e8f0 !important;
  border-color: rgba(255,255,255,0.1) !important;
}
.table thead {
  background-color: rgba(255,255,255,0.1) !important;
  color: #fff;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
</head>

<body>
<div class="d-flex">
<!-- Sidebar -->
<div class="col-2">
  <?php include 'sidebar.php'; ?>
</div>


  <!-- Konten Utama -->
  <div class="content flex-grow-1 ">
    <nav class="navbar navbar-expand-lg px-3 mb-4">
      <div class="container-fluid">
        <span class="navbar-brand text-light fw-semibold"><i class="bi bi-door-open"></i> DATA KELAS </span>
        <div class="d-flex align-items-center">
          <img src="<?= !empty($admin['foto']) ? $admin['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
          <span><?= htmlspecialchars($admin['nama_lengkap']) ?> ( <?= htmlspecialchars($role) ?> )</span>
        </div>
      </div>
    </nav>

    <!-- Tombol Tambah -->
    <div class="mb-3 text-end">
      <a href="tambah_kelas.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah Kelas</a>
    </div>

    <!-- Daftar Kelas -->
    <?php if (mysqli_num_rows($result_kelas) > 0): ?>
      <?php while ($k = mysqli_fetch_assoc($result_kelas)): ?>
        <div class="card mb-4 p-3 card p-4">
          <table class="table table-borderless table-striped table-hover align-middle ">
            <h5 class="fw-semibold mb-2">
              <i class="bi bi-buildings"></i> Kelas <?= htmlspecialchars($k['tingkat'] . " " . $k['jurusan']); ?>
          
            </h5>
          </table>
          <p><strong>Wali Kelas:</strong> <?= htmlspecialchars($k['wali_kelas'] ?? 'Belum ditentukan'); ?></p>
          
          <h6 class="mt-3 mb-2 "><i class="bi bi-people "></i> Daftar Siswa</h6>
          <?php if (!empty($siswa_per_kelas[$k['kelas_id']])): ?>
            <div class="table-responsive card p-4">
              <table class="table table-borderless table-striped table-hover align-middle ">
                <thead class="text-center">
                  <tr>
                    <th style="width:60px;">No</th>
                    <th>Nama Lengkap</th>
                    <th>NIS</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no=1; foreach ($siswa_per_kelas[$k['kelas_id']] as $s): ?>
                    <tr>
                      <td class="text-center"><?= $no++; ?></td>
                      <td class="text-center"><?= htmlspecialchars($s['nama_lengkap']); ?></td>
                      <td class="text-center"><?= htmlspecialchars($s['nis']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p><em>Belum ada siswa di kelas ini.</em></p>
          <?php endif; ?>

          <div class="mt-3 text-end">
            <a href="edit_kelas.php?id=<?= $k['kelas_id']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
            <a href="hapus_kelas.php?id=<?= $k['kelas_id']; ?>" onclick="return confirm('Yakin ingin menghapus kelas ini?')" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Hapus</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-center"><em>Belum ada data kelas.</em></p>
    <?php endif; ?>
  </div>
</div>

<!-- Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
fetch("sidebar.php")
  .then(res => res.text())
  .then(html => document.getElementById("sidebar-container").innerHTML = html);
</script>
</body>
</html>
