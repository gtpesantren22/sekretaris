<?php
include 'koneksi.php';

$query 	= "SELECT * FROM surat_keluar WHERE id_keluar='$_GET[id]'";
$sql	= mysqli_query($conn, $query);
$data 	= mysqli_fetch_array($sql);
$file	= $data['file'];
//jika filenya ada,  hapus filenya
if (file_exists("upload/surat_keluar/$file")) {
	unlink("upload/surat_keluar/$file");
}

$query2	= "DELETE FROM surat_keluar WHERE id_keluar='$_GET[id]'";
$sql2	= mysqli_query($conn, $query2);
if ($sql2) {
	echo '<script> 
				window.alert("Data surat keluar berhasil di hapus");
				window.location.href="keluar.php";
			 </script>';
} else {
	echo '<script> 
				window.alert("Data gagal di hapus");
			 </script>';
}