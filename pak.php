<?php include 'head.php'; ?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data PAK Lembaga
            <small>Data</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Data</a></li>
            <li class="active">Data PAK</li>
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
                                        <th>Kode PAK</th>
                                        <th>Lembaga</th>
                                        <th>Tanggal PAK</th>
                                        <th>Status</th>
                                        <th>Tahun</th>
                                        <th>#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $no = 1;
                                        $dt_bos = mysqli_query($conn_sentral, "SELECT a.*, b.nama FROM pak a JOIN lembaga b ON a.lembaga=b.kode WHERE a.status = 'ajukan' AND b.tahun = '2022/2023' ");
                                        while ($a = mysqli_fetch_assoc($dt_bos)) {
                                        ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= $a['kode_pak']; ?></td>
                                        <td><?= $a['nama']; ?></td>
                                        <td><?= $a['tgl_pak']; ?></td>
                                        <td><?= $a['status']; ?></td>
                                        <td><?= $a['tahun']; ?></td>
                                        <td><a
                                                href="<?= 'pak_detail.php?kode=' . $a['kode_pak'] . '&lm=' . $a['lembaga'] ?>"><button
                                                    class="btn btn-info btn-sm"><i class="fa fa-search"></i>
                                                    Cek PAK</button></a></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
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

if (isset($_POST['send'])) {
    $id_mutasi = $_POST['id_mutasi'];
    $sql = mysqli_query($conn, "UPDATE mutasi SET status = 2 WHERE id_mutasi = '$id_mutasi' ");
    $sql2 = mysqli_query($conn_santri, "UPDATE mutasi SET status = 2 WHERE id_mutasi = '$id_mutasi' ");

    $dts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT a.tgl_mutasi, b.* FROM mutasi a JOIN tb_santri b ON a.nis=b.nis WHERE a.id_mutasi = $id_mutasi "));
    $psn = '*INFORMASI MUTASI*

*PERMOHONAN PENGELUARAN DATA SANTRI*
    
Nama : ' . $dts['nama'] . '
Alamat : ' . $dts['desa'] . '-' . $dts['kec'] . '-' . $dts['kab'] . '
Sekolah : ' . $dts['t_formal'] . '
Tgl Mutasi : ' .  $dts['tgl_mutasi'] . '

*_Surat mutasi sudah diterbitkan oleh SEKRETARIAT. Santri sudah resmi mutasi. Untuk selanjutnya kepada admin DPontren untuk mengeluarkan data santri diatas_*
Terimakasih';

    if ($sql2 && $sql) {

        $curl2 = curl_init();
        curl_setopt_array(
            $curl2,
            array(
                CURLOPT_URL => 'http://8.215.26.187:3000/api/sendMessageGroup',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'apiKey=f4064efa9d05f66f9be6151ec91ad846&id_group=120363028015516743@g.us&message=' . $psn,
            )
        );
        $response = curl_exec($curl2);
        curl_close($curl2);

        echo "
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses',
                    text: 'Data mutasi sudah terkirim',
                    showConfirmButton: false
                });
                var millisecondsToWait = 1000;
                setTimeout(function() {
                    document.location.href = 'mutasi.php' 
                }, millisecondsToWait);
            </script>
        ";
    }
}
?>