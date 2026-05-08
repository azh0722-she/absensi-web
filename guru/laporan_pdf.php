<?php
session_start();
include '../koneksi.php';
require_once('../tcpdf/tcpdf.php'); // TCPDF

// Cek login role guru
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    header('Location: ../index.php');
    exit;
}

$guru_id = $_SESSION['user_id'];

// Ambil data guru
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $guru_id);
mysqli_stmt_execute($stmt);
$guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Filter tanggal & kelas
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$kelas_filter = $_GET['kelas'] ?? '';

// Ambil id kelas
$stmt = mysqli_prepare($conn, "SELECT id, CONCAT(tingkat,' ',jurusan) AS nama_kelas FROM kelas WHERE CONCAT(tingkat,' ',jurusan) = ?");
mysqli_stmt_bind_param($stmt, "s", $kelas_filter);
mysqli_stmt_execute($stmt);
$kelas_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if(!$kelas_data) die("Kelas tidak ditemukan.");

$kelas_id = $kelas_data['id'];
$kelas_nama = $kelas_data['nama_kelas'];

// Ambil data absensi siswa
$query = "
SELECT s.nama_lengkap,
       SUM(a.status='Hadir') as hadir,
       SUM(a.status='Izin') as izin,
       SUM(a.status='Alpha' OR a.status='Alfa') as alfa,
       COUNT(*) as total
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
$res_siswa = mysqli_stmt_get_result($stmt);
$siswa_list = mysqli_fetch_all($res_siswa, MYSQLI_ASSOC);

// Buat PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($guru['username']);
$pdf->SetTitle("Laporan Absensi $kelas_nama");

// Nonaktifkan header bawaan TCPDF agar garis atas hilang
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Margin lebih lebar agar rapi
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

// Header ala sekolah manual
$pdf->SetFont('helvetica','B',14);
$pdf->Cell(0, 6, "SMA NEGERI 1 KOTA AGUNG", 0, 1, 'C');
$pdf->SetFont('helvetica','',11);
$pdf->Cell(0, 6, "Jl. Letjen Harun Sohar Kec. Kota Agung Kab LAHAT", 0, 1, 'C');
$pdf->Cell(0, 6, "Telp: 0721-1234-5678 | Email: sman1kotaagung@gmail.co.id", 0, 1, 'C');

// Garis horizontal di bawah Telp
$y_after_tel = $pdf->GetY() + 2;
$pdf->SetLineWidth(0.5);
$pdf->Line(20, $y_after_tel, 190, $y_after_tel);

$pdf->Ln(8);

// Judul laporan
$pdf->SetFont('helvetica','B',12);
$pdf->Cell(0, 6, "LAPORAN ABSENSI SISWA", 0, 1, 'C');
$pdf->Cell(0, 6, "KELAS: $kelas_nama", 0, 1, 'C');
$pdf->Cell(0, 6, "Periode: $tanggal_awal s/d $tanggal_akhir", 0, 1, 'C');
$pdf->Ln(5);

// Info Guru
$pdf->SetFont('helvetica','',11);
$pdf->Cell(15, 6, "Guru:", 0, 0);
$pdf->Cell(0, 6, $guru['username'], 0, 1);
$pdf->Ln(3);

// Table absensi
$pdf->SetFont('helvetica','',10);
$html = '<table border="1" cellpadding="6" cellspacing="0" width="100%" align="center">
<tr style="background-color:#d1e7dd; text-align:center; font-weight:bold;">
    <th width="5%">No</th>
    <th width="50%">Nama Siswa</th>
    <th width="15%">Hadir</th>
    <th width="10%">Izin</th>
    <th width="10%">Alfa</th>
    <th width="10%">Persentase</th>
</tr>';

$no = 1;
foreach($siswa_list as $row){
    $persen = ($row['total']>0)?round(($row['hadir']/$row['total'])*100):0;
    $html .= '<tr>
        <td align="center">'.$no++.'</td>
        <td>'.$row['nama_lengkap'].'</td>
        <td align="center">'.$row['hadir'].'</td>
        <td align="center">'.$row['izin'].'</td>
        <td align="center">'.$row['alfa'].'</td>
        <td align="center">'.$persen.'%</td>
    </tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Footer
$pdf->Ln(8);
$pdf->Cell(0, 6, "Kota Agung, ".date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, "Mengetahui, Kepala Sekolah", 0, 1, 'R');
$pdf->Ln(15);
$pdf->Cell(0, 6, "GUSTI MANDALA", 0, 1, 'R');

$pdf->Output("laporan_absensi_$kelas_nama.pdf", 'I');
