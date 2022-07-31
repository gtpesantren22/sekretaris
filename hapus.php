<script src="plugins/sw/sweetalert2.all.min.js"></script>
<?php
include 'koneksi.php';
include 'foot.php';

$kd = $_GET['kd'];
$id = $_GET['id'];

if ($kd === 'mts') {
    $sql = mysqli_query($conn, "DELETE FROM mutasi WHERE id_mutasi = '$id' ");
    $sql2 = mysqli_query($conn_santri, "DELETE FROM mutasi WHERE id_mutasi = '$id' ");
    // $dts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mutasi WHERE id_mutasi = '$id' "));
    // $nis = $dts['nis'];
    // $sql3 = mysqli_query($conn_santri, "UPDATE tb_santri SET aktif = 'Y' WHERE nis = '$nis' ");

    if ($sql && $sql2) {
        echo "
        <script>
            Swal.fire({
            icon: 'success',
            title: 'Sukses',
            text: 'Data berhasil terhapus!'
            })
            window.location = 'mutasi.php';
        </script>
        ";
    }
}
