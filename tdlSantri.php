<?php
include 'head.php';
$nis = $_GET['nis'] ?? '';
$stmt = mysqli_prepare($conn, "SELECT * FROM tb_santri WHERE nis = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $nis);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<div class='content-wrapper'><section class='content'><div class='alert alert-danger'>Data santri tidak ditemukan! <a href='javascript:history.back()'>Kembali</a></div></section></div>";
    include 'foot.php';
    exit;
}
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Detail Identitas Santri
            <small><?= htmlspecialchars($data['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="#">Data Santri</a></li>
            <li class="active">Detail</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Detail Identitas Santri</h3>
                        <a href="javascript:history.back()" class="btn btn-default btn-sm pull-right"><i class="fa fa-arrow-left"></i> Kembali</a>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th style="width: 200px;">Status Keaktifan</th>
                                <th>
                                    <?php if (($data['aktif'] ?? '') === 'Y'): ?>
                                        <span class="label label-success"><i class="fa fa-check"></i> Santri Aktif</span>
                                    <?php else: ?>
                                        <span class="label label-danger"><i class="fa fa-times"></i> Non-Aktif / Mutasi</span>
                                    <?php endif; ?>
                                </th>
                            </tr>
                            <tr>
                                <th>NIS</th>
                                <td><?= htmlspecialchars($data['nis'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Nama</th>
                                <td><?= htmlspecialchars($data['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Tetala</th>
                                <td><?= htmlspecialchars($data['tempat'] ?? '', ENT_QUOTES, 'UTF-8'); ?>, <?= !empty($data['tanggal']) ? date('d F Y', strtotime($data['tanggal'])) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin</th>
                                <td><?= htmlspecialchars($data['jkl'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td><?= htmlspecialchars(($data['jln'] ?? '') . ' RT ' . ($data['rt'] ?? '') . '/RW ' . ($data['rw'] ?? '') . ', Desa ' . ($data['desa'] ?? '') . ' - ' . ($data['kec'] ?? '') . ' - ' . ($data['kab'] ?? '') . ' - ' . ($data['prov'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Nama Bapak</th>
                                <td><?= htmlspecialchars($data['bapak'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Nama Ibu</th>
                                <td><?= htmlspecialchars($data['ibu'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Kelas Formal</th>
                                <td><?= htmlspecialchars(($data['k_formal'] ?? '') . ' ' . ($data['t_formal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Kelas Madin</th>
                                <td><?= htmlspecialchars(($data['k_madin'] ?? '') . ' ' . ($data['r_madin'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>No. HP</th>
                                <td><?= htmlspecialchars($data['hp'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
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