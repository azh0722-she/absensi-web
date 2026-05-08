<?php
include 'koneksi.php';
session_start();

// Cek apakah ID kelas dikirim
if (!isset($_GET['id'])) {
    echo "<script>alert('Kelas tidak ditemukan, Yang Mulia.'); window.location='data_kelas.php';</script>";
    exit;
}

$id = $_GET['id'];

// Cek apakah kelas ada
$cek = mysqli_query($conn, "SELECT * FROM kelas WHERE id = '$id'");
if (mysqli_num_rows($cek) == 0) {
    echo "<script>alert('Data kelas tidak ditemukan, Yang Mulia.'); window.location='data_kelas.php';</script>";
    exit;
}

// Hapus siswa yang ada di kelas tersebut terlebih dahulu
mysqli_query($conn, "DELETE FROM siswa_kelas WHERE kelas_id = '$id'");

// Hapus data kelas
$hapus = mysqli_query($conn, "DELETE FROM kelas WHERE id = '$id'");

if ($hapus) {
    echo "<script>alert('Data kelas berhasil dihapus, Yang Mulia.'); window.location='data_kelas.php';</script>";
} else {
    echo "<script>alert('Terjadi kesalahan saat menghapus data kelas, Yang Mulia.'); window.location='data_kelas.php';</script>";
}
?>
