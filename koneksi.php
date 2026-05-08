<?php
// Buat koneksi utama
$conn = mysqli_connect("localhost", "root", "", "absensi");

// Jika gagal, hentikan eksekusi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Buat alias agar file lain yang pakai $koneksi juga tetap bisa jalan
$koneksi = $conn;
?>
