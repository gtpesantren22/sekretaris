<?php
include 'head.php';
$nis = $_GET['nis'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_santri WHERE nis = $nis "));
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data Santri
            <small>Data</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Data</a></li>
            <li class="active">Data Santri Putri</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Detail Identitas Santri</h3>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <table class="table table-sm">
                            <tr>
                                <th>NIS</th>
                                <th><?= $data['nis'] ?></th>
                            </tr>
                            <tr>
                                <th>Nama</th>
                                <th><?= $data['nama'] ?></th>
                            </tr>
                            <tr>
                                <th>Tetala</th>
                                <th><?= $data['tempat'] ?>, <?= date('d F Y', strtotime($data['tanggal'])) ?></th>
                            </tr>
                            <tr>
                                <th>Jkl</th>
                                <th><?= $data['jkl'] ?></th>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <th><?= $data['jln'] ?> RT <?= $data['rt'] ?>/RW <?= $data['rw'] ?>, Desa <?= $data['desa'] ?> - <?= $data['kec'] ?> - <?= $data['kab'] ?> - <?= $data['prov'] ?></th>

                            </tr>
                            <tr>
                                <th>Nama Bapak</th>
                                <th><?= $data['bapak'] ?></th>
                            </tr>
                            <tr>
                                <th>Nama Ibu</th>
                                <th><?= $data['ibu'] ?></th>
                            </tr>
                            <tr>
                                <th>Kelas Formal</th>
                                <th><?= $data['k_formal'] . ' ' . $data['t_formal'] ?></th>
                            </tr>
                            <tr>
                                <th>Kelas Madin</th>
                                <th><?= $data['k_madin'] . ' ' . $data['r_madin'] ?></th>
                            </tr>
                            <tr>
                                <th>No. HP</th>
                                <th><?= $data['hp']  ?></th>
                            </tr>
                        </table>
                    </div><!-- /.box-body -->
                </div><!-- /.box -->

            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
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
<?php include 'foot.php'; ?>