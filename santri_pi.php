<?php 
include 'head.php'; 

// Filter status: 'Y' (Aktif), 'T' (Non-Aktif), 'all' (Semua)
$status_filter = $_GET['status'] ?? 'Y';

// Hitung total masing-masing status untuk santri putri
$q_count_pi = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN aktif = 'Y' THEN 1 ELSE 0 END) as total_aktif,
    SUM(CASE WHEN aktif != 'Y' OR aktif IS NULL THEN 1 ELSE 0 END) as total_nonaktif
FROM tb_santri WHERE jkl = 'Perempuan'");
$c_pi = mysqli_fetch_assoc($q_count_pi);
$c_total = (int)($c_pi['total'] ?? 0);
$c_aktif = (int)($c_pi['total_aktif'] ?? 0);
$c_nonaktif = (int)($c_pi['total_nonaktif'] ?? 0);

if ($status_filter === 'T') {
    $where_sql = "WHERE jkl = 'Perempuan' AND (aktif != 'Y' OR aktif IS NULL)";
} elseif ($status_filter === 'all') {
    $where_sql = "WHERE jkl = 'Perempuan'";
} else {
    $status_filter = 'Y';
    $where_sql = "WHERE jkl = 'Perempuan' AND aktif = 'Y'";
}
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data Santri Putri
            <small><?= ($status_filter === 'Y') ? 'Santri Aktif' : (($status_filter === 'T') ? 'Santri Non-Aktif' : 'Semua Santri'); ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="#">Data</a></li>
            <li class="active">Data Santri Putri</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <div class="btn-group">
                            <a href="santri_pi.php?status=Y" class="btn btn-sm <?= ($status_filter === 'Y') ? 'btn-success' : 'btn-default'; ?>">
                                <i class="fa fa-check-circle"></i> Aktif <span class="badge"><?= $c_aktif; ?></span>
                            </a>
                            <a href="santri_pi.php?status=T" class="btn btn-sm <?= ($status_filter === 'T') ? 'btn-danger' : 'btn-default'; ?>">
                                <i class="fa fa-times-circle"></i> Non-Aktif <span class="badge"><?= $c_nonaktif; ?></span>
                            </a>
                            <a href="santri_pi.php?status=all" class="btn btn-sm <?= ($status_filter === 'all') ? 'btn-info' : 'btn-default'; ?>">
                                <i class="fa fa-users"></i> Semua <span class="badge"><?= $c_total; ?></span>
                            </a>
                        </div>
                        <a href="santri_sinkron.php" class="btn btn-sm btn-primary pull-right"><i class="fa fa-refresh"></i> Sinkron Data Santri</a>
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
                                        <th>Status</th>
                                        <th>#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $sql = mysqli_query($conn, "SELECT * FROM tb_santri $where_sql ORDER BY nama ASC");
                                    while ($dt = mysqli_fetch_assoc($sql)) { 
                                        $is_aktif = ($dt['aktif'] === 'Y');
                                    ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($dt['nis'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($dt['nama'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($dt['tempat'] . ', ' . $dt['tanggal'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($dt['desa'] . ' - ' . $dt['kec'] . ' - ' . $dt['kab'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($dt['k_formal'] . ' - ' . $dt['t_formal'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($dt['k_madin'] . ' - ' . $dt['r_madin'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if ($is_aktif): ?>
                                                    <span class="label label-success"><i class="fa fa-check"></i> Aktif</span>
                                                <?php else: ?>
                                                    <span class="label label-danger"><i class="fa fa-times"></i> Non-Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><a href="tdlSantri.php?nis=<?= urlencode($dt['nis']) ?>" class="btn btn-xs btn-success"><i class="fa fa-eye"></i> Detail</a></td>
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