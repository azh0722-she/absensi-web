<?php
session_start();
include 'koneksi.php';
require_once('tcpdf/tcpdf.php'); // TCPDF

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Ambil data admin
$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Filter tanggal & kelas
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-01-01'); 
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');  
$filter_kelas = $_GET['kelas'] ?? '';

// Ambil data absensi sesuai filter
$data_perkelas = [];
$query = "SELECT s.nama_lengkap, k.tingkat, k.jurusan,
                 SUM(a.status='Hadir') as hadir,
                 SUM(a.status='Izin') as izin,
                 SUM(a.status='Alpha' OR a.status='Alfa') as alfa,
                 COUNT(*) as total
          FROM absensi a
          JOIN siswa s ON a.siswa_id = s.id
          JOIN siswa_kelas sk ON sk.siswa_id = s.id
          JOIN kelas k ON sk.kelas_id = k.id
          WHERE a.tanggal BETWEEN ? AND ?";

$types = 'ss';
$params = [$tanggal_awal, $tanggal_akhir];
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
        'persentase' => $persentase
    ];
}

// Buat PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

// Header Sekolah
$pdf->SetFont('helvetica','B',14);
$pdf->Cell(0,6,"SMA NEGERI 1 KOTA AGUNG",0,1,'C');
$pdf->SetFont('helvetica','',11);
$pdf->Cell(0,6,"Jl. Letjen Harun Sohar Kec. Kota Agung Kab LAHAT",0,1,'C');
$pdf->Cell(0,6,"Telp: 0721-1234-5678 | Email: sman1kotaagung@gmail.co.id",0,1,'C');

// Garis horizontal di bawah Telp
$y_after_tel = $pdf->GetY() + 2;
$pdf->SetLineWidth(0.5);
$pdf->Line(20, $y_after_tel, 190, $y_after_tel);
$pdf->Ln(8);

// Judul
$pdf->SetFont('helvetica','B',12);
$pdf->Cell(0,6,"LAPORAN ABSENSI SISWA",0,1,'C');
if($filter_kelas) $pdf->Cell(0,6,"KELAS: $filter_kelas",0,1,'C');
$pdf->Cell(0,6,"Periode: $tanggal_awal s/d $tanggal_akhir",0,1,'C');
$pdf->Ln(5);

// Table per kelas
foreach($data_perkelas as $kelas => $siswa_list){
    $pdf->SetFont('helvetica','B',11);
    $pdf->Cell(0,6,"Kelas: $kelas",0,1,'L');
    $pdf->Ln(2);

    $pdf->SetFont('helvetica','',10);
    $html = '<table border="1" cellpadding="5" cellspacing="0" width="85%">
        <tr style="background-color:#d1e7dd; text-align:center; font-weight:bold;">
            <th width="5%">No</th>
            <th width="55%">Nama Siswa</th>
            <th width="15%">Hadir</th>
            <th width="15%">Izin</th>
            <th width="15%">Alfa</th>
            <th width="15%">Persentase</th>
        </tr>';
    $no=1;
    foreach($siswa_list as $s){
        $html .= '<tr>
            <td align="center">'.$no++.'</td>
            <td>'.$s['nama_siswa'].'</td>
            <td align="center">'.$s['hadir'].'</td>
            <td align="center">'.$s['izin'].'</td>
            <td align="center">'.$s['alfa'].'</td>
            <td align="center">'.$s['persentase'].'%</td>
        </tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html,true,false,true,false,'');
    $pdf->Ln(5);
}

// Footer
$pdf->Cell(0,6,"Kota Agung, ".date('d F Y'),0,1,'R');
$pdf->Cell(0,6,"Mengetahui, Kepala Sekolah",0,1,'R');
$pdf->Ln(15);
$pdf->Cell(0,6,"GUSTI MANDALA",0,1,'R');

$pdf->Output("laporan_absensi_admin.pdf",'I');
?>
