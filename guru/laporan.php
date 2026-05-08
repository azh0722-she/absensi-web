<?php
session_start();
include '../koneksi.php';

// Cek login role guru
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    header('Location: ../index.php');
    exit;
}

$guru_id = $_SESSION['user_id'];

// Ambil data guru
$stmt = mysqli_prepare($conn, "SELECT * FROM guru WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $guru_id);
mysqli_stmt_execute($stmt);
$guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

// Ambil daftar kelas guru
$kelas_list = [];
$res_kelas = mysqli_prepare($conn, "
    SELECT DISTINCT k.id, CONCAT(k.tingkat,' ',k.jurusan) AS nama_kelas
    FROM siswa_kelas sk
    JOIN kelas k ON k.id = sk.kelas_id
    JOIN absensi a ON a.siswa_id = sk.siswa_id
    WHERE a.user_id = ?
    ORDER BY k.tingkat, k.jurusan ASC
");
mysqli_stmt_bind_param($res_kelas, "i", $guru_id);
mysqli_stmt_execute($res_kelas);
$result_kelas = mysqli_stmt_get_result($res_kelas);
while ($row = mysqli_fetch_assoc($result_kelas)) {
    $kelas_list[$row['id']] = $row['nama_kelas'];
}

// Ambil data absensi per kelas
$data_perkelas = [];
foreach($kelas_list as $kelas_id => $kelas_nama){
    $query = "
        SELECT s.nama_lengkap,
               SUM(a.status='Hadir') as hadir,
               SUM(a.status='Izin') as izin,
               SUM(a.status='Alpha' OR a.status='Alfa') as alfa,
               COUNT(*) as total,
               GROUP_CONCAT(a.keterangan SEPARATOR ' | ') AS keterangan
        FROM absensi a
        JOIN siswa s ON s.id = a.siswa_id
        JOIN siswa_kelas sk ON sk.siswa_id = s.id
        WHERE a.user_id = ? AND sk.kelas_id = ? AND a.tanggal BETWEEN ? AND ?
        GROUP BY s.id
        ORDER BY s.nama_lengkap ASC
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiss", $guru_id, $kelas_id, $tanggal_awal, $tanggal_akhir);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data_perkelas[$kelas_nama] = mysqli_fetch_all($res, MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Absensi - Guru</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: "Poppins", sans-serif; background: linear-gradient(135deg,#1e293b,#0f172a); color: #fff; min-height:100vh; }
.content { padding:30px; }
.navbar { background: rgba(255,255,255,0.1); backdrop-filter: blur(12px); border-bottom:1px solid rgba(255,255,255,0.1); }
.navbar img { width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; }
.card { background: rgba(199,199,199,0.85); border:1px solid rgba(255,255,255,0.05); border-radius:15px; box-shadow:0 0 20px rgba(0,0,0,0.3); }
.table { --bs-table-bg: transparent !important; color:#fff; border-color:rgba(255,255,255,0.1) !important; }
.table thead { background-color:rgba(255,255,255,0.15) !important; }
.table tbody tr:hover { background-color:rgba(255,255,255,0.10) !important; }
.btn-filter { background-color:#0ea5e9; color:white; }
.btn-filter:hover { background-color:#0284c7; color:white; }
.btn-pdf { background-color:#ef4444; color:white; }
.btn-pdf:hover { background-color:#b91c1c; color:white; }

body { 
    font-family: "Poppins", sans-serif; 
    background: linear-gradient(135deg,#1e293b,#0f172a); 
    color: #fff; 
    min-height:100vh; 
    overflow-x:hidden; /* sama seperti admin */
}
.content { 
    padding:30px; 
    animation:fadeIn 0.8s ease; /* tambahkan animasi */
}
.navbar { 
    background: rgba(255,255,255,0.1); 
    backdrop-filter: blur(12px); 
    border-bottom:1px solid rgba(255,255,255,0.1); 
}
.navbar img { 
    width:32px; 
    height:32px; 
    border-radius:50%; 
    object-fit:cover; 
    margin-right:8px; 
}
.card { 
    background: rgba(199,199,199,0.85); 
    border:1px solid rgba(255,255,255,0.05); 
    border-radius:15px; 
    box-shadow:0 0 20px rgba(0,0,0,0.3); 
}
.table { 
    --bs-table-bg: transparent !important; 
    color:#fff; 
    border-color:rgba(255,255,255,0.1) !important; 
}
.table thead { 
    background-color:rgba(255,255,255,0.1) !important; 
}
.table tbody tr:hover { 
    background-color:rgba(255,255,255,0.1) !important; 
}
.btn-filter { 
    background-color:#0ea5e9; 
    color:white; 
}
.btn-filter:hover { 
    background-color:#0284c7; 
    color:white; 
}
.btn-pdf { 
    background-color:#ef4444; 
    color:white; 
}
.btn-pdf:hover { 
    background-color:#b91c1c; 
    color:white; 
}

/* Animasi fadeIn */
@keyframes fadeIn { 
    from {opacity:0; transform:translateY(10px);} 
    to {opacity:1; transform:translateY(0);} 
}

</style>
</head>

<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="content flex-grow-1">
<nav class="navbar navbar-expand-lg px-3 mb-4">
  <div class="container-fluid">
    <span class="navbar-brand text-light fw-semibold"><i class="bi bi-graph-up"></i> LAPORAN ABSENSI</span>
    <div class="d-flex align-items-center">
      <img src="<?= !empty($guru['foto']) ? $guru['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
      <span><?= htmlspecialchars($guru['nama_lengkap']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</span>
    </div>
  </div>
</nav>

<!-- Filter -->
<form method="GET" class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Tanggal Awal</label>
        <input type="date" name="tanggal_awal" class="form-control" value="<?= $tanggal_awal ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tanggal Akhir</label>
        <input type="date" name="tanggal_akhir" class="form-control" value="<?= $tanggal_akhir ?>">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-filter w-100"><i class="bi bi-funnel"></i> Tampilkan</button>
    </div>
</form>

<!-- Tabel per kelas -->
<?php foreach($data_perkelas as $kelas => $siswa_list): ?>
<div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold mb-0"><i class="bi bi-buildings"></i> Kelas <?= htmlspecialchars($kelas) ?></h5>
        <a href="laporan_pdf.php?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>&kelas=<?= urlencode($kelas) ?>" class="btn btn-pdf btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-borderless table-hover align-middle text-center">
            <thead>
                <tr>
                    <th>No</th>
                    <th class="text-start">Nama Siswa</th>
                    <th>Hadir</th>
                    <th>Izin</th>
                    <th>Alfa</th>
                    <th>Persentase</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($siswa_list as $row): ?>
                <?php $persen = ($row['total']>0)?round(($row['hadir']/$row['total'])*100):0; ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td><span class="badge bg-success"><?= $row['hadir'] ?></span></td>
                    <td><span class="badge bg-warning text-dark"><?= $row['izin'] ?></span></td>
                    <td><span class="badge bg-danger"><?= $row['alfa'] ?></span></td>
                    <td><?= $persen ?>%</td>
                    <td class="text-start"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
