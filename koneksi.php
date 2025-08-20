<?php

$conn = mysqli_connect("localhost", "root", "", "db_sekretaris");
$conn_santri = mysqli_connect("localhost", "root", "", "db_santri");
$conn_sentral = mysqli_connect("localhost", "root", "", "db_sentral");

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
