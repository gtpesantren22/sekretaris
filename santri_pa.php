<?php include 'head.php'; ?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data Santri
            <small>Data</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Data</a></li>
            <li class="active">Data Santri Putara</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Data Santri Putra</h3>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <div class="table-responsive">
                            <table id="example1_bst" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Tetala</th>
                                        <th>Alamat</th>
                                        <th>Formal</th>
                                        <th>Madin</th>
                                        <th>#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                  $no = 1;
                  $sql = mysqli_query($conn, "SELECT * FROM tb_santri WHERE jkl = 'Laki-laki' AND aktif = 'Y' ");
                  while ($dt = mysqli_fetch_assoc($sql)) { ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= $dt['nis']; ?></td>
                                        <td><?= $dt['nama']; ?></td>
                                        <td><?= $dt['tempat'] . ', ' . $dt['tanggal']; ?></td>
                                        <td><?= $dt['desa'] . ' - ' . $dt['kec'] . ' - ' . $dt['kab']; ?></td>
                                        <td><?= $dt['k_formal'] . ' - ' . $dt['t_formal']; ?></td>
                                        <td><?= $dt['k_madin'] . ' - ' . $dt['r_madin']; ?></td>
                                        <td>X</td>
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