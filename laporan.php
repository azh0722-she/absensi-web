<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

// Ambil data admin
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];
$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Filter tanggal range dari GET
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-01-01'); 
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');  

// Ambil daftar kelas
$kelas_list = [];
$res_kelas = mysqli_query($conn, "SELECT DISTINCT CONCAT(tingkat,' ',jurusan) AS kelas FROM kelas ORDER BY tingkat,jurusan ASC");
while($row = mysqli_fetch_assoc($res_kelas)) {
    $kelas_list[] = $row['kelas'];
}

// Filter kelas opsional
$filter_kelas = $_GET['kelas'] ?? '';

// Ambil data absensi per kelas sesuai filter tanggal range
$data_perkelas = [];
$query = "SELECT s.nama_lengkap, k.tingkat, k.jurusan,
                 SUM(a.status='Hadir') as hadir,
                 SUM(a.status='Izin') as izin,
                 SUM(a.status='Alpha' OR a.status='Alfa') as alfa,
                 COUNT(*) as total,
                 a.keterangan
          FROM absensi a
          JOIN siswa s ON a.siswa_id = s.id
          JOIN siswa_kelas sk ON sk.siswa_id = s.id
          JOIN kelas k ON sk.kelas_id = k.id
          WHERE a.tanggal BETWEEN ? AND ?";


// Inisialisasi $types dan $params supaya selalu ada
$types = 'ss';
$params = [$tanggal_awal, $tanggal_akhir];

// Jika ada filter kelas
if($filter_kelas){
    $query .= " AND CONCAT(k.tingkat,' ',k.jurusan) = ?";
    $types .= 's';
    $params[] = $filter_kelas;
}

$query .= " GROUP BY k.tingkat, k.jurusan, s.id ORDER BY k.tingkat, k.jurusan, s.nama_lengkap ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while($row = mysqli_fetch_assoc($res)) {
    $persentase = ($row['total'] > 0) ? round(($row['hadir']/$row['total'])*100) : 0;
    $kelas_id = $row['tingkat'].' '.$row['jurusan'];
$data_perkelas[$kelas_id][] = [
    'nama_siswa' => $row['nama_lengkap'],
    'kelas' => $kelas_id,
    'hadir' => $row['hadir'],
    'izin' => $row['izin'],
    'alfa' => $row['alfa'],
    'persentase' => $persentase,
    'keterangan' => $row['keterangan'] ?? '-'
];

}
?>


<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Laporan - Sistem Absensi Siswa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />

<style>
body { font-family: "Poppins", sans-serif; background: linear-gradient(135deg,#1e293b,#0f172a); color: #fff; min-height:100vh; overflow-x:hidden; }
.content { padding:30px; animation:fadeIn 0.8s ease; }
.navbar { background: rgba(255,255,255,0.1); backdrop-filter: blur(12px); border-bottom:1px solid rgba(255,255,255,0.1); }
.navbar img { width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; }
.card { background: rgba(199,199,199,0.85); border:1px solid rgba(255,255,255,0.05); border-radius:15px; box-shadow:0 0 20px rgba(0,0,0,0.3); }
.table { --bs-table-bg: transparent !important; --bs-table-striped-bg: rgba(255,255,255,0.05); --bs-table-hover-bg: rgba(255,255,255,0.1); color: #e2e8f0 !important; border-color: rgba(255,255,255,0.1) !important; vertical-align: middle; }
.table thead { background-color: rgba(255,255,255,0.1) !important; color:#fff; }
.table tbody tr:hover { background-color: rgba(255,255,255,0.1) !important; }
.badge { font-size:0.85rem; }
@keyframes fadeIn { from {opacity:0; transform:translateY(10px);} to {opacity:1; transform:translateY(0);} }
.btn-export { background-color:#22c55e; color:white; }
.btn-export:hover { background-color:#16a34a; color:white; }
.btn-filter { background-color:#0ea5e9; color:white; }
.btn-filter:hover { background-color:#0284c7; color:white; }
</style>
</head>


<body>
<div class="d-flex">
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <div class="content flex-grow-1">
    <nav class="navbar navbar-expand-lg px-3 mb-4">
      <div class="container-fluid">
        <span class="navbar-brand text-light fw-semibold"><i class="bi bi-graph-up"></i> LAPORAN ABSENSI</span>
        <div class="d-flex align-items-center">
          <img src="<?= !empty($admin['foto']) ? $admin['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
          <span><?= htmlspecialchars($admin['nama_lengkap']) ?> (<?= htmlspecialchars($role) ?>)</span>
        </div>
      </div>
    </nav>

    <!-- Filter -->
    <form method="GET" class="row g-3 mb-4 align-items-end">
      <div class="col-md-3">
        <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
        <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="<?= htmlspecialchars($tanggal_awal) ?>">
      </div>
      <div class="col-md-3">
        <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir) ?>">
      </div>
      <div class="col-md-3 text-center">
        <label for="kelas" class="form-label">Kelas</label>
        <select name="kelas" id="kelas" class="form-select">
          <option value="" class="text-center">Semua Kelas</option>
          <?php foreach($kelas_list as $k): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= ($filter_kelas==$k)?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-filter"><i class="bi bi-funnel"></i> Tampilkan</button>
      </div>
    </form>

    <!-- Tabel per kelas -->
    <?php foreach($data_perkelas as $kelas => $siswa_list): ?>

    <div class="card p-4 mb-4" id="kelas-<?= str_replace(' ','_', $kelas) ?>">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold mb-0"><i class="bi bi-buildings"></i> Kelas <?= htmlspecialchars($kelas) ?></h5>
        <a href="export_pdf.php?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>&kelas=<?= urlencode($kelas) ?>" class="btn btn-export btn-sm">
          <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
      </div>
          <div class="table-responsive card p-4">
              <table class="table table-borderless table-striped table-hover align-middle "></table>
      <table class="table table-borderless table-hover align-middle">
                   
              
        <thead>
          <tr class="text-center">
            <th>No</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Hadir</th>
            <th>Izin</th>
            <th>Alfa</th>
            <th>Persentase Kehadiran</th>
            <th>Keterangan</th>

<td class="text-start"><?= htmlspecialchars($s['keterangan'] ?? '-') ?></td>

          </tr>
        </thead>
        <tbody>
          <?php $no=1; foreach($siswa_list as $s): ?>
          <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= htmlspecialchars($s['nama_siswa']) ?></td>
            <td class="text-center"><?= htmlspecialchars($s['kelas']) ?></td>
            <td class="text-center"><span class="badge bg-success"><?= $s['hadir'] ?></span></td>
            <td class="text-center"><span class="badge bg-warning text-dark"><?= $s['izin'] ?></span></td>
            <td class="text-center"><span class="badge bg-danger"><?= $s['alfa'] ?></span></td>
            <td class="text-center"><?= $s['persentase'] ?>%</td>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script>
function exportPDF(id) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p','mm','a4');

    // Header
    doc.setFontSize(16);
    doc.setFont('helvetica','bold');
    doc.text("Laporan Absensi Siswa", 105, 20, {align: "center"});
    doc.setFontSize(12);
    doc.setFont('helvetica','normal');

    // Tanggal & kelas
    const kelasTitle = document.querySelector("#"+id+" h5").innerText;
    const tanggalAwal = document.querySelector('#tanggal_awal').value;
    const tanggalAkhir = document.querySelector('#tanggal_akhir').value;
    doc.text("Periode: " + tanggalAwal + " s/d " + tanggalAkhir, 105, 28, {align: "center"});
    doc.text(kelasTitle, 14, 36);

    // Ambil tabel
    const table = document.querySelector("#"+id+" table");

    // Styles profesional
    doc.autoTable({
        html: table,
        startY: 40,
        theme: 'grid',
        headStyles: {fillColor: [34,197,94], textColor: 255, halign: 'center'},
        bodyStyles: {textColor: 0, halign: 'center'},
        alternateRowStyles: {fillColor: [240,240,240]},
        columnStyles: {
            1: {halign: 'left'}, // Nama Siswa
            2: {halign: 'center'}, // Kelas
            6: {halign: 'center'}  // Persentase
        },
        styles: {fontSize: 10}
    });

    // Footer
    const finalY = doc.lastAutoTable.finalY || 50;
    doc.setFontSize(11);
    doc.text("Catatan: Kehadiran dihitung sesuai periode yang dipilih", 14, finalY + 10);
    doc.text("Mengetahui, Kepala Sekolah", 160, finalY + 25);

    // Simpan PDF
    doc.save("laporan_absensi_"+id+".pdf");
}
</script>

</body>
</html>
