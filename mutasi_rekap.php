<?php
include 'head.php';

// Filter default: awal bulan ini s/d hari ini
$default_awal = date('Y-m-01');
$default_akhir = date('Y-m-d');

$tgl_awal = $_GET['tgl_awal'] ?? $default_awal;
$tgl_akhir = $_GET['tgl_akhir'] ?? $default_akhir;
$status_filter = $_GET['status'] ?? 'all';
$jkl_filter = $_GET['jkl'] ?? 'all';

$conditions = [];
$params = [];
$types = '';

if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $conditions[] = "a.tgl_mutasi BETWEEN ? AND ?";
    $params[] = $tgl_awal;
    $params[] = $tgl_akhir;
    $types .= 'ss';
} elseif (!empty($tgl_awal)) {
    $conditions[] = "a.tgl_mutasi >= ?";
    $params[] = $tgl_awal;
    $types .= 's';
} elseif (!empty($tgl_akhir)) {
    $conditions[] = "a.tgl_mutasi <= ?";
    $params[] = $tgl_akhir;
    $types .= 's';
}

if ($status_filter !== 'all' && $status_filter !== '') {
    $conditions[] = "a.status = ?";
    $params[] = (int)$status_filter;
    $types .= 'i';
}

if ($jkl_filter !== 'all' && $jkl_filter !== '') {
    $conditions[] = "b.jkl = ?";
    $params[] = $jkl_filter;
    $types .= 's';
}

$where_sql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "SELECT a.*, b.nama, b.jkl, b.tempat, b.tanggal, b.desa, b.kec, b.kab, b.k_formal, b.t_formal, b.k_madin, b.r_madin, b.hp 
          FROM mutasi a 
          LEFT JOIN tb_santri b ON a.nis = b.nis 
          $where_sql 
          ORDER BY a.tgl_mutasi DESC, a.id_mutasi DESC";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

// Data Array & Statistik
$data_mutasi = [];
$stat_total = 0;
$stat_pa = 0;
$stat_pi = 0;
$stat_selesai = 0;
$stat_pending = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data_mutasi[] = $row;
        $stat_total++;
        if (($row['jkl'] ?? '') === 'Laki-laki') {
            $stat_pa++;
        } else {
            $stat_pi++;
        }

        if (($row['status'] ?? 0) == 2) {
            $stat_selesai++;
        } else {
            $stat_pending++;
        }
    }
}

// URL Export Excel dengan parameter saat ini
$export_url = "mutasi_export.php?" . http_build_query([
    'tgl_awal' => $tgl_awal,
    'tgl_akhir' => $tgl_akhir,
    'status' => $status_filter,
    'jkl' => $jkl_filter
]);
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Rekap Mutasi Santri
            <small>Laporan & Export Excel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="mutasi.php">Mutasi Santri</a></li>
            <li class="active">Rekap Mutasi</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">

        <!-- Form Filter Tanggal & Kriteria -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-filter"></i> Filter Periode Tanggal & Kriteria</h3>
            </div>
            <div class="box-body">
                <form action="" method="get" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group" style="margin: 0 5px 15px 5px;">
                                <label>Tanggal Awal</label>
                                <input type="date" name="tgl_awal" class="form-control input-sm" value="<?= htmlspecialchars($tgl_awal, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group" style="margin: 0 5px 15px 5px;">
                                <label>Tanggal Akhir</label>
                                <input type="date" name="tgl_akhir" class="form-control input-sm" value="<?= htmlspecialchars($tgl_akhir, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group" style="margin: 0 5px 15px 5px;">
                                <label>Jenis Kelamin</label>
                                <select name="jkl" class="form-control input-sm">
                                    <option value="all" <?= ($jkl_filter === 'all') ? 'selected' : ''; ?>>-- Semua --</option>
                                    <option value="Laki-laki" <?= ($jkl_filter === 'Laki-laki') ? 'selected' : ''; ?>>Putra (Laki-laki)</option>
                                    <option value="Perempuan" <?= ($jkl_filter === 'Perempuan') ? 'selected' : ''; ?>>Putri (Perempuan)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group" style="margin: 0 5px 15px 5px;">
                                <label>Status Mutasi</label>
                                <select name="status" class="form-control input-sm">
                                    <option value="all" <?= ($status_filter === 'all') ? 'selected' : ''; ?>>-- Semua Status --</option>
                                    <option value="0" <?= ($status_filter === '0') ? 'selected' : ''; ?>>Belum Verval</option>
                                    <option value="1" <?= ($status_filter === '1') ? 'selected' : ''; ?>>Verval Bendahara</option>
                                    <option value="2" <?= ($status_filter === '2') ? 'selected' : ''; ?>>Selesai (Kirim Pendataan)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Tampilkan Data</button>
                            <a href="mutasi_rekap.php" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> Reset Filter</a>
                            <a href="<?= $export_url; ?>" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export to Excel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Stats Boxes -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-aqua">
                    <span class="info-box-icon"><i class="fa fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Mutasi</span>
                        <span class="info-box-number"><?= $stat_total; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-male"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Santri Putra</span>
                        <span class="info-box-number"><?= $stat_pa; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-female"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Santri Putri</span>
                        <span class="info-box-number"><?= $stat_pi; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-red">
                    <span class="info-box-icon"><i class="fa fa-check-square-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Selesai / Terkirim</span>
                        <span class="info-box-number"><?= $stat_selesai; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekap Data -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-table"></i> Data Rekap Mutasi Santri
                    <small>(Periode: <?= date('d/m/Y', strtotime($tgl_awal)); ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)); ?>)</small>
                </h3>
                <div class="box-tools pull-right">
                    <a href="<?= $export_url; ?>" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Download Excel</a>
                    <button onclick="window.print();" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Cetak</button>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table id="example1_bst" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr class="bg-gray">
                                <th style="width: 30px;">No</th>
                                <th>NIS</th>
                                <th>Nama Santri</th>
                                <th>JK</th>
                                <th>Formal</th>
                                <th>Madin</th>
                                <th>Alamat</th>
                                <th>Tgl Mutasi</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th class="no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($data_mutasi as $dt): 
                                if ($dt['status'] == 0) {
                                    $badge_status = "<span class='label label-danger'><i class='fa fa-clock-o'></i> Belum Verval</span>";
                                } elseif ($dt['status'] == 1) {
                                    $badge_status = "<span class='label label-warning'><i class='fa fa-check'></i> Verval Bendahara</span>";
                                } elseif ($dt['status'] == 2) {
                                    $badge_status = "<span class='label label-success'><i class='fa fa-check-circle'></i> Selesai (Terkirim)</span>";
                                } else {
                                    $badge_status = "<span class='label label-default'>-</span>";
                                }
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><strong><?= htmlspecialchars($dt['nis'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?= htmlspecialchars($dt['nama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= (($dt['jkl'] ?? '') === 'Laki-laki') ? '<span class="badge bg-blue">PA</span>' : '<span class="badge bg-purple">PI</span>'; ?></td>
                                    <td><?= htmlspecialchars(($dt['k_formal'] ?? '') . ' ' . ($dt['t_formal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars(($dt['k_madin'] ?? '') . ' ' . ($dt['r_madin'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars(($dt['desa'] ?? '') . ' - ' . ($dt['kec'] ?? '') . ' - ' . ($dt['kab'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= !empty($dt['tgl_mutasi']) ? date('d/m/Y', strtotime($dt['tgl_mutasi'])) : '-'; ?></td>
                                    <td><?= htmlspecialchars($dt['alasan'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= $badge_status; ?></td>
                                    <td class="no-print">
                                        <a href="tdlSantri.php?nis=<?= urlencode($dt['nis'] ?? ''); ?>" class="btn btn-xs btn-info" title="Detail Identitas"><i class="fa fa-eye"></i> Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
        $("#example1_bst").DataTable({
            "order": [[7, "desc"]] // Sort by Tanggal Mutasi descending
        });
    });
</script>

<style>
@media print {
    .no-print, .main-sidebar, .main-header, .box-tools, .btn, .breadcrumb, form {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
        padding-top: 0 !important;
    }
}
</style>

<?php include 'foot.php'; ?>
