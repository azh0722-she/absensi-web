<?php
include 'koneksi.php'; // koneksi ke database

// --- EDIT DATA ---
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nis = $_POST['nis'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jurusan = $_POST['jurusan'];
    $kelas = $_POST['kelas'];
    $tahun_masuk = $_POST['tahun_masuk'];
    $no_telp = $_POST['no_telp'];
    $alamat = $_POST['alamat'];
    $status = $_POST['status'];

    // Cek apakah ada foto baru
    if (!empty($_FILES['foto']['name'])) {
        $foto = $_FILES['foto']['name'];
        $tmp = $_FILES['foto']['tmp_name'];
        $path = "uploads/" . $foto;

        // Hapus foto lama jika ada
        $query_old = mysqli_query($conn, "SELECT foto FROM siswa WHERE id='$id'");
        $old = mysqli_fetch_assoc($query_old);
        if ($old['foto'] && file_exists("uploads/" . $old['foto'])) {
            unlink("uploads/" . $old['foto']);
        }

        // Upload foto baru
        move_uploaded_file($tmp, $path);

        $query = "UPDATE siswa SET 
                    nis='$nis',
                    nama_lengkap='$nama_lengkap',
                    jenis_kelamin='$jenis_kelamin',
                    tempat_lahir='$tempat_lahir',
                    tanggal_lahir='$tanggal_lahir',
                    jurusan='$jurusan',
                    kelas='$kelas',
                    tahun_masuk='$tahun_masuk',
                    no_telp='$no_telp',
                    alamat='$alamat',
                    foto='$foto',
                    status='$status'
                  WHERE id='$id'";
    } else {
        $query = "UPDATE siswa SET 
                    nis='$nis',
                    nama_lengkap='$nama_lengkap',
                    jenis_kelamin='$jenis_kelamin',
                    tempat_lahir='$tempat_lahir',
                    tanggal_lahir='$tanggal_lahir',
                    jurusan='$jurusan',
                    kelas='$kelas',
                    tahun_masuk='$tahun_masuk',
                    no_telp='$no_telp',
                    alamat='$alamat',
                    status='$status'
                  WHERE id='$id'";
    }

    $update = mysqli_query($conn, $query);
    if ($update) {
        echo "<script>alert('Data siswa berhasil diperbarui!');window.location='data_siswa.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data siswa!');window.history.back();</script>";
    }
}

// --- HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    // Hapus foto dari folder jika ada
    $query_old = mysqli_query($conn, "SELECT foto FROM siswa WHERE id='$id'");
    $old = mysqli_fetch_assoc($query_old);
    if ($old['foto'] && file_exists("uploads/" . $old['foto'])) {
        unlink("uploads/" . $old['foto']);
    }

    // Hapus data dari database
    $query = mysqli_query($conn, "DELETE FROM siswa WHERE id='$id'");

    if ($query) {
        echo "<script>alert('Data siswa berhasil dihapus!');window.location='data_siswa.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data siswa!');window.history.back();</script>";
    }
}
?>
