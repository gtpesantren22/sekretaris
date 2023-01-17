<?php
include 'head.php';
include 'func_wa.php';

$kode = $_GET['kode'];
$lm = $_GET['lm'];

$lmdr = mysqli_fetch_assoc(mysqli_query($conn_sentral, "SELECT * FROM lembaga WHERE kode = '$lm' AND tahun = '2022/2023' "));
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data PAK Lembaga
            <small>Data</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Data</a></li>
            <li class="active">Data Mutasi Santri</li>
        </ol>
    </section>
    <?php if ($level_user === 'super') { ?>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Data PAK
                        </h3>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <div class="table-responsive">
                            <table id="example1_bst" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <!-- <th>Bagian</th> -->
                                        <th>Jenis</th>
                                        <th>Barang/Kegiatan</th>
                                        <th>Rencana</th>
                                        <th>QTY</th>
                                        <th>Satuan</th>
                                        <th>Harga Satuan</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $no = 1;
                                        $dt_bos = mysqli_query($conn_sentral, "SELECT * FROM rab_sm WHERE lembaga = '$lm' ");
                                        while ($r1 = mysqli_fetch_assoc($dt_bos)) {
                                        ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= $r1['kode'] ?></td>
                                        <!-- <td><?= $r1['bidang'] ?></td> -->
                                        <td><?= $r1['jenis'] ?></td>
                                        <td><?= $r1['nama'] ?></td>
                                        <td><?= $r1['rencana'] ?></td>
                                        <td><?= $r1['qty'] ?></td>
                                        <td><?= $r1['satuan'] ?></td>
                                        <td><?= rupiah($r1['harga_satuan']) ?></td>
                                        <td><?= rupiah($r1['total']) ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-3">
                            <h5>Keterangan :</h5>
                            <ul>
                                <li>A : Belanja Barang</li>
                                <li>B : Belanja Kegiatan</li>
                                <li>C : Langganan dan Jasa</li>
                                <li>D : Umum</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h5>Verifikasi</h5>
                            <small class="text-danger">* Tombol ini akan menyetujui PAK ini dan akan diakan dilanjutkan
                                ke accounting. Harap dipastikan dahulu sebelum melakukan verifikasi</small><br>
                            <form action="" method="post">
                                <button class="btn btn-success" type="submit" name="verval"><i class="fa fa-check"></i>
                                    Verifikasi</button>
                            </form>
                        </div>
                        <div class="col-md-5">
                            <h5>Penolakan</h5>
                            <small class="text-danger">* Jika ada penolakan harap isi pesan penolakannya</small><br>
                            <form action="" method="post">
                                <textarea name="isi" id="" class="form-control" required></textarea>
                                <button class="btn btn-danger btn-sm" type="submit" name="tolak"><i
                                        class="fa fa-times"></i>
                                    Tolak</button>
                            </form>
                        </div>
                    </div><!-- /.box-body -->
                </div><!-- /.box -->

            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
    <?php } ?>
</div><!-- /.content-wrapper -->
<!-- DataTables -->
<link rel="stylesheet" href="plugins/datatables/dataTables.bootstrap.css">
<!-- jQuery 2.1.4 -->
<script src="plugins/jQuery/jQuery-2.1.4.min.js"></script>
<!-- Bootstrap 3.3.5 -->
<script src="bootstrap/js/bootstrap.min.js"></script>
<!-- DataTables -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables/dataTables.bootstrap.min.js"></script>
<script src="plugins/sw/sweetalert2.all.min.js"></script>
<script>
$(function() {
    $("#example1_bst").DataTable();
    $('#example2').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false
    });
});
</script>
<?php
include 'foot.php';

if (isset($_POST['verval'])) {
    $sql = mysqli_query($conn_sentral, "UPDATE pak SET status = 'proses' WHERE kode_pak = '$kode' ");

    $psn = '
*INFORMASI VERIFIKASI PAK*

Ada pengajuan baru dari :
    
Lembaga : ' . $lmdr['nama'] . '
Kode PAK : ' . $kode . '

*_PAK Sudah disetujui Oleh Sekretariat. Selanjutnya akan diajukan kepada Bendahara_*

Terimakasih';


    if ($sql) {

        kirim_group('f4064efa9d05f66f9be6151ec91ad846', '120363042148360147@g.us', $psn);
        kirim_group('f4064efa9d05f66f9be6151ec91ad846', '120363042148360147@g.us', $psn);
        // kirim_person('f4064efa9d05f66f9be6151ec91ad846', '085236924510', $psn);
        echo "
            <script>
            alert('PAK telah disetujui');
                    document.location.href = 'pak.php';
            </script>
        ";
    }
}

if (isset($_POST['tolak'])) {
    $sql = mysqli_query($conn_sentral, "UPDATE pak SET status = 'ditolak' WHERE kode_pak = '$kode' ");

    $psn = '
*INFORMASI PENOLAKAN PAK*

Pengajuan PAK dari :
    
Lembaga : ' . $lmdr['nama'] . '
Kode PAK : ' . $kode . '

PAK ditolak oleh SEKRETARIAT dengan catatan :
*' . mysqli_real_escape_string($conn, $_POST['isi']) . '*

Dimohon kepada KPA terkait untuk memperbaiki nya kembali
Terimakasih';


    if ($sql) {

        kirim_group('f4064efa9d05f66f9be6151ec91ad846', '120363042148360147@g.us', $psn);
        kirim_group('f4064efa9d05f66f9be6151ec91ad846', '120363042148360147@g.us', $psn);
        // kirim_person('f4064efa9d05f66f9be6151ec91ad846', '085236924510', $psn);
        echo "
            <script>
            alert('PAK telah ditolak');
                    document.location.href = 'pak.php';
            </script>
        ";
    }
}
?>