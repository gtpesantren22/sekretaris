<?php

$conn = mysqli_connect("localhost", "root", "", "db_sekretaris");
$conn_santri = mysqli_connect("localhost", "root", "", "db_santri");
$conn_sentral = mysqli_connect("localhost", "root", "", "db_sentral");

// $conn = mysqli_connect("localhost", "u9048253_dwk", "PesantrenDWKIT2021", "u9048253_sekretaris");
// $conn_santri = mysqli_connect("localhost", "u9048253_dwk", "PesantrenDWKIT2021", "u9048253_santri");
// $conn_sentral = mysqli_connect("localhost", "u9048253_dwk", "PesantrenDWKIT2021", "u9048253_sentral");

function rupiah($angka)
{
    if ($angka != '') {
        return $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
    } else {
        return $hasil_rupiah = "Rp. " . number_format(0, 0, ',', '.');
    }
}
function rupiah2($angka)
{
    if ($angka != '') {
        return $hasil_rupiah =  number_format($angka, 0, ',', '.');
    } else {
        return $hasil_rupiah =  number_format(0, 0, ',', '.');
    }
}