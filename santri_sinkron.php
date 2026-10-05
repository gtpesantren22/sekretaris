<?php
include 'head.php';

// Pastikan CSRF token tersedia
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pesan = '';
$tipe_pesan = '';
$stat = [
    'total_sentral' => 0,
    'total_sentral_aktif' => 0,
    'total_sentral_nonaktif' => 0,
    'total_sekretaris' => 0,
    'total_sekretaris_aktif' => 0,
    'total_sekretaris_nonaktif' => 0,
    'inserted' => 0,
    'updated' => 0,
    'failed' => 0,
    'waktu' => null
];

// Cek koneksi db_sentral
$sentral_connected = false;
if ($conn_sentral && !mysqli_connect_errno()) {
    $sentral_connected = true;
    
    // Hitung jumlah data santri di db_sentral
    $q_count_sentral = mysqli_query($conn_sentral, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN aktif = 'Y' OR aktif = '1' THEN 1 ELSE 0 END) as total_aktif,
        SUM(CASE WHEN aktif != 'Y' AND aktif != '1' OR aktif IS NULL THEN 1 ELSE 0 END) as total_nonaktif
    FROM tb_santri");
    if ($q_count_sentral) {
        $row_s = mysqli_fetch_assoc($q_count_sentral);
        $stat['total_sentral'] = (int)($row_s['total'] ?? 0);
        $stat['total_sentral_aktif'] = (int)($row_s['total_aktif'] ?? 0);
        $stat['total_sentral_nonaktif'] = (int)($row_s['total_nonaktif'] ?? 0);
    }
}

// Hitung jumlah data santri di db_sekretaris
$q_count_sekretaris = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN aktif = 'Y' THEN 1 ELSE 0 END) as total_aktif,
    SUM(CASE WHEN aktif != 'Y' OR aktif IS NULL THEN 1 ELSE 0 END) as total_nonaktif
FROM tb_santri");
if ($q_count_sekretaris) {
    $row_sek = mysqli_fetch_assoc($q_count_sekretaris);
    $stat['total_sekretaris'] = (int)($row_sek['total'] ?? 0);
    $stat['total_sekretaris_aktif'] = (int)($row_sek['total_aktif'] ?? 0);
    $stat['total_sekretaris_nonaktif'] = (int)($row_sek['total_nonaktif'] ?? 0);
}

// Proses Sinkronisasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sinkron'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $pesan = "Token keamanan tidak valid. Silakan refresh halaman.";
        $tipe_pesan = "danger";
    } elseif (!$sentral_connected) {
        $pesan = "Gagal terhubung ke database db_sentral! Pastikan konfigurasi database di koneksi.php sudah benar.";
        $tipe_pesan = "danger";
    } else {
        $start_time = microtime(true);

        // Ambil daftar kolom tabel tb_santri di db_sekretaris (target)
        $target_cols = [];
        $res_cols = mysqli_query($conn, "SHOW COLUMNS FROM tb_santri");
        if ($res_cols) {
            while ($col = mysqli_fetch_assoc($res_cols)) {
                $target_cols[] = $col['Field'];
            }
        }

        if (empty($target_cols)) {
            $pesan = "Tabel tb_santri pada db_sekretaris tidak ditemukan!";
            $tipe_pesan = "danger";
        } else {
            // Ambil semua data santri dari db_sentral
            $res_sentral = mysqli_query($conn_sentral, "SELECT * FROM tb_santri");

            if ($res_sentral) {
                $inserted = 0;
                $updated = 0;
                $failed = 0;

                mysqli_begin_transaction($conn);

                try {
                    while ($santri = mysqli_fetch_assoc($res_sentral)) {
                        $nis = $santri['nis'] ?? null;
                        if (!$nis) {
                            $failed++;
                            continue;
                        }

                        // Filter dan normalisasi kolom yang sesuai dengan tabel target db_sekretaris
                        $matched_data = [];
                        foreach ($santri as $key => $val) {
                            if (in_array($key, $target_cols)) {
                                // Normalisasi nilai aktif agar konsisten 'Y' atau 'T'
                                if ($key === 'aktif') {
                                    $val = (strtoupper((string)$val) === 'Y' || (string)$val === '1') ? 'Y' : 'T';
                                }
                                $matched_data[$key] = $val;
                            }
                        }

                        // Cek apakah NIS sudah ada di db_sekretaris
                        $check_stmt = mysqli_prepare($conn, "SELECT nis FROM tb_santri WHERE nis = ? LIMIT 1");
                        mysqli_stmt_bind_param($check_stmt, "s", $nis);
                        mysqli_stmt_execute($check_stmt);
                        mysqli_stmt_store_result($check_stmt);
                        $exists = (mysqli_stmt_num_rows($check_stmt) > 0);
                        mysqli_stmt_close($check_stmt);

                        if ($exists) {
                            // UPDATE data
                            $update_parts = [];
                            $update_vals = [];
                            $types = '';

                            foreach ($matched_data as $col_name => $col_val) {
                                if ($col_name === 'nis') continue;
                                $update_parts[] = "`$col_name` = ?";
                                $update_vals[] = $col_val;
                                $types .= 's';
                            }

                            if (!empty($update_parts)) {
                                $update_sql = "UPDATE tb_santri SET " . implode(", ", $update_parts) . " WHERE nis = ?";
                                $update_vals[] = $nis;
                                $types .= 's';

                                $up_stmt = mysqli_prepare($conn, $update_sql);
                                if ($up_stmt) {
                                    mysqli_stmt_bind_param($up_stmt, $types, ...$update_vals);
                                    if (mysqli_stmt_execute($up_stmt)) {
                                        $updated++;
                                    } else {
                                        $failed++;
                                    }
                                    mysqli_stmt_close($up_stmt);
                                } else {
                                    $failed++;
                                }
                            }
                        } else {
                            // INSERT data baru
                            $cols_insert = [];
                            $placeholders = [];
                            $insert_vals = [];
                            $types = '';

                            foreach ($matched_data as $col_name => $col_val) {
                                $cols_insert[] = "`$col_name`";
                                $placeholders[] = "?";
                                $insert_vals[] = $col_val;
                                $types .= 's';
                            }

                            $ins_sql = "INSERT INTO tb_santri (" . implode(", ", $cols_insert) . ") VALUES (" . implode(", ", $placeholders) . ")";
                            $ins_stmt = mysqli_prepare($conn, $ins_sql);
                            if ($ins_stmt) {
                                mysqli_stmt_bind_param($ins_stmt, $types, ...$insert_vals);
                                if (mysqli_stmt_execute($ins_stmt)) {
                                    $inserted++;
                                } else {
                                    $failed++;
                                }
                                mysqli_stmt_close($ins_stmt);
                            } else {
                                $failed++;
                            }
                        }
                    }

                    mysqli_commit($conn);

                    $end_time = microtime(true);
                    $duration = round($end_time - $start_time, 2);

                    $stat['inserted'] = $inserted;
                    $stat['updated'] = $updated;
                    $stat['failed'] = $failed;
                    $stat['waktu'] = $duration;

                    // Refresh total sekretaris
                    $q_count_sekretaris = mysqli_query($conn, "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN aktif = 'Y' THEN 1 ELSE 0 END) as total_aktif,
                        SUM(CASE WHEN aktif != 'Y' OR aktif IS NULL THEN 1 ELSE 0 END) as total_nonaktif
                    FROM tb_santri");
                    if ($q_count_sekretaris) {
                        $row_sek = mysqli_fetch_assoc($q_count_sekretaris);
                        $stat['total_sekretaris'] = (int)($row_sek['total'] ?? 0);
                        $stat['total_sekretaris_aktif'] = (int)($row_sek['total_aktif'] ?? 0);
                        $stat['total_sekretaris_nonaktif'] = (int)($row_sek['total_nonaktif'] ?? 0);
                    }

                    $pesan = "Sinkronisasi berhasil diselesaikan dalam {$duration} detik! Data Ditambahkan: {$inserted}, Data Diperbarui: {$updated}" . ($failed > 0 ? ", Gagal: {$failed}" : "");
                    $tipe_pesan = "success";

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $pesan = "Terjadi kesalahan saat sinkronisasi: " . $e->getMessage();
                    $tipe_pesan = "danger";
                }
            } else {
                $pesan = "Gagal membaca data dari tabel tb_santri pada db_sentral!";
                $tipe_pesan = "danger";
            }
        }
    }
}
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Sinkronisasi Data Santri
            <small>Integrasi Database Sentral</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="#">Data Santri</a></li>
            <li class="active">Sinkronisasi</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan; ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-<?= ($tipe_pesan === 'success') ? 'check' : 'ban'; ?>"></i> <?= ($tipe_pesan === 'success') ? 'Berhasil!' : 'Perhatian!'; ?></h4>
                <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Info Status Koneksi Database Sentral -->
            <div class="col-md-6 col-sm-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-database"></i> Status Database & Rincian Status</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th style="width: 220px;">Database Sentral (Sumber)</th>
                                <td>
                                    <?php if ($sentral_connected): ?>
                                        <span class="label label-success"><i class="fa fa-check"></i> Terhubung</span> <code>db_sentral</code>
                                    <?php else: ?>
                                        <span class="label label-danger"><i class="fa fa-times"></i> Terputus</span> <code>db_sentral</code>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Total Santri di DB Sentral</th>
                                <td>
                                    <strong><?= number_format($stat['total_sentral'], 0, ',', '.'); ?> Santri</strong><br>
                                    <small class="text-success"><i class="fa fa-circle"></i> Aktif: <strong><?= number_format($stat['total_sentral_aktif'], 0, ',', '.'); ?></strong></small> | 
                                    <small class="text-danger"><i class="fa fa-circle"></i> Non-Aktif: <strong><?= number_format($stat['total_sentral_nonaktif'], 0, ',', '.'); ?></strong></small>
                                </td>
                            </tr>
                            <tr>
                                <th>Database Sekretaris (Lokal)</th>
                                <td><span class="label label-success"><i class="fa fa-check"></i> Terhubung</span> <code>db_sekretaris</code></td>
                            </tr>
                            <tr>
                                <th>Total Santri di DB Sekretaris</th>
                                <td>
                                    <strong><?= number_format($stat['total_sekretaris'], 0, ',', '.'); ?> Santri</strong><br>
                                    <small class="text-success"><i class="fa fa-circle"></i> Aktif: <strong><?= number_format($stat['total_sekretaris_aktif'], 0, ',', '.'); ?></strong></small> | 
                                    <small class="text-danger"><i class="fa fa-circle"></i> Non-Aktif: <strong><?= number_format($stat['total_sekretaris_nonaktif'], 0, ',', '.'); ?></strong></small>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Panel Aksi Sinkronisasi -->
            <div class="col-md-6 col-sm-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-refresh"></i> Eksekusi Sinkronisasi</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">
                            Proses ini akan menyinkronkan seluruh data santri (baik <strong>Aktif</strong> maupun <strong>Non-Aktif</strong>) dari tabel <code>tb_santri</code> <strong>db_sentral</strong> ke <strong>db_sekretaris</strong>.
                        </p>

                        <div class="callout callout-info" style="margin-bottom: 20px;">
                            <h4><i class="fa fa-info-circle"></i> Mekanisme Status:</h4>
                            <ul style="padding-left: 20px;">
                                <li>Status <strong>Aktif (Y)</strong> dan <strong>Non-Aktif (T)</strong> dari DB Sentral akan otomatis disinkronkan.</li>
                                <li>Santri yang mutasi/keluar di DB Sentral akan otomatis ter-update menjadi non-aktif di DB Sekretaris.</li>
                                <li>Data baru (NIS belum ada) otomatis ditambahkan.</li>
                            </ul>
                        </div>

                        <form action="" method="post" id="formSinkron" onsubmit="return konfirmasiSinkron();">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" name="sinkron" id="btnSinkron" class="btn btn-primary btn-lg btn-block" <?= !$sentral_connected ? 'disabled' : ''; ?>>
                                <i class="fa fa-refresh" id="iconSync"></i> Mulai Sinkronisasi Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($stat['waktu'] !== null): ?>
            <!-- Ringkasan Hasil Terakhir -->
            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-pie-chart"></i> Ringkasan Hasil Sinkronisasi Terakhir</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="info-box bg-green">
                                        <span class="info-box-icon"><i class="fa fa-user-plus"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Data Baru Ditambahkan</span>
                                            <span class="info-box-number"><?= $stat['inserted']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="info-box bg-yellow">
                                        <span class="info-box-icon"><i class="fa fa-pencil-square-o"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Data Diperbarui</span>
                                            <span class="info-box-number"><?= $stat['updated']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="info-box bg-red">
                                        <span class="info-box-icon"><i class="fa fa-exclamation-triangle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Gagal / Dilewati</span>
                                            <span class="info-box-number"><?= $stat['failed']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="info-box bg-aqua">
                                        <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Waktu Proses</span>
                                            <span class="info-box-number"><?= $stat['waktu']; ?> dtk</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </section><!-- /.content -->
</div><!-- /.content-wrapper -->

<script>
function konfirmasiSinkron() {
    var yakin = confirm("Apakah Anda yakin ingin menyinkronkan data santri dari DB Sentral ke DB Sekretaris?");
    if (yakin) {
        var btn = document.getElementById('btnSinkron');
        var icon = document.getElementById('iconSync');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sedang Memproses Sinkronisasi...';
        // Submit form programmatically
        document.getElementById('formSinkron').submit();
        return false;
    }
    return false;
}
</script>

<?php include 'foot.php'; ?>
