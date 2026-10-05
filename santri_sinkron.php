<?php
include 'head.php';

// Tingkatkan batas waktu dan memori untuk sinkronisasi massal
@set_time_limit(600);
@ini_set('memory_limit', '512M');

// Pastikan CSRF token tersedia
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pesan = '';
$tipe_pesan = '';
$error_details = [];
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
if (isset($conn_sentral) && $conn_sentral instanceof mysqli && !mysqli_connect_errno()) {
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
    SUM(CASE WHEN aktif = 'Y' OR aktif = '1' THEN 1 ELSE 0 END) as total_aktif,
    SUM(CASE WHEN aktif != 'Y' AND aktif != '1' OR aktif IS NULL THEN 1 ELSE 0 END) as total_nonaktif
FROM tb_santri");
if ($q_count_sekretaris) {
    $row_sek = mysqli_fetch_assoc($q_count_sekretaris);
    $stat['total_sekretaris'] = (int)($row_sek['total'] ?? 0);
    $stat['total_sekretaris_aktif'] = (int)($row_sek['total_aktif'] ?? 0);
    $stat['total_sekretaris_nonaktif'] = (int)($row_sek['total_nonaktif'] ?? 0);
}

// Proses Sinkronisasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['proses_sinkron']) || isset($_POST['sinkron']))) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $pesan = "Token keamanan tidak valid. Silakan refresh halaman dan coba kembali.";
        $tipe_pesan = "danger";
    } elseif (!$sentral_connected) {
        $pesan = "Gagal terhubung ke database db_sentral! Pastikan konfigurasi database di koneksi.php sudah benar.";
        $tipe_pesan = "danger";
    } else {
        $start_time = microtime(true);

        // Set sql_mode kosong pada sesi saat ini agar tidak error saat ada data terpotong/tipe data fleksibel
        @mysqli_query($conn, "SET SESSION sql_mode = ''");
        if ($conn_sentral) {
            @mysqli_query($conn_sentral, "SET SESSION sql_mode = ''");
        }

        // 1. Ambil detail skema tabel tb_santri di db_sekretaris (target)
        $target_col_info = [];
        $res_target_cols = mysqli_query($conn, "SHOW FULL COLUMNS FROM tb_santri");
        if ($res_target_cols) {
            while ($col = mysqli_fetch_assoc($res_target_cols)) {
                $target_col_info[$col['Field']] = [
                    'type' => strtolower($col['Type']),
                    'null' => strtoupper($col['Null']) === 'YES',
                    'key'  => $col['Key'],
                    'default' => $col['Default'],
                    'extra' => strtolower($col['Extra'])
                ];
            }
        }

        // 2. Ambil skema tabel tb_santri di db_sentral (sumber)
        $source_cols = [];
        $res_source_cols = mysqli_query($conn_sentral, "SHOW COLUMNS FROM tb_santri");
        if ($res_source_cols) {
            while ($col = mysqli_fetch_assoc($res_source_cols)) {
                $source_cols[] = $col['Field'];
            }
        }

        if (empty($target_col_info)) {
            $pesan = "Tabel tb_santri pada db_sekretaris tidak ditemukan!";
            $tipe_pesan = "danger";
        } elseif (empty($source_cols)) {
            $pesan = "Tabel tb_santri pada db_sentral tidak ditemukan atau tidak memiliki kolom!";
            $tipe_pesan = "danger";
        } else {
            // Helper untuk membersihkan dan menyesuaikan panjang data sesuai kolom target
            $sanitize_val = function($val, $meta) {
                if ($val === null) return null;
                $val = (string)$val;
                $type = $meta['type'] ?? '';
                if (preg_match('/^(varchar|char)\((\d+)\)/i', $type, $matches)) {
                    $max_len = (int)$matches[2];
                    if (mb_strlen($val, 'UTF-8') > $max_len) {
                        $val = mb_substr($val, 0, $max_len, 'UTF-8');
                    }
                }
                return $val;
            };

            // Ambil peta NIS yang sudah ada di db_sekretaris
            $existing_nis_map = [];
            $res_existing = mysqli_query($conn, "SELECT nis FROM tb_santri");
            if ($res_existing) {
                while ($row_e = mysqli_fetch_assoc($res_existing)) {
                    $val_nis = trim((string)($row_e['nis'] ?? ''));
                    if ($val_nis !== '') {
                        $existing_nis_map[$val_nis] = true;
                    }
                }
            }

            // Tentukan kolom yang akan di-INSERT ke db_sekretaris
            // Semua kolom target selain auto_increment id
            $insert_target_cols = [];
            foreach ($target_col_info as $field_name => $meta) {
                if (strpos($meta['extra'], 'auto_increment') !== false) {
                    continue; // Skip auto_increment field
                }
                $insert_target_cols[] = $field_name;
            }

            // Tentukan kolom yang akan di-UPDATE di db_sekretaris
            // Semua kolom yang ada di db_sentral dan db_sekretaris kecuali nis dan auto_increment id
            $update_target_cols = [];
            foreach ($insert_target_cols as $field_name) {
                if ($field_name === 'nis') continue;
                if (in_array($field_name, $source_cols)) {
                    $update_target_cols[] = $field_name;
                }
            }

            // Siapkan statement INSERT
            $ins_cols_sql = implode(", ", array_map(function($c) { return "`$c`"; }, $insert_target_cols));
            $ins_placeholders = implode(", ", array_fill(0, count($insert_target_cols), "?"));
            $ins_types = str_repeat("s", count($insert_target_cols));
            $ins_sql = "INSERT INTO tb_santri ($ins_cols_sql) VALUES ($ins_placeholders)";
            $ins_stmt = mysqli_prepare($conn, $ins_sql);

            // Siapkan statement UPDATE
            $up_stmt = null;
            $up_types = '';
            if (!empty($update_target_cols)) {
                $up_cols_sql = implode(", ", array_map(function($c) { return "`$c` = ?"; }, $update_target_cols));
                $up_types = str_repeat("s", count($update_target_cols)) . "s"; // +1 untuk WHERE nis = ?
                $up_sql = "UPDATE tb_santri SET $up_cols_sql WHERE `nis` = ?";
                $up_stmt = mysqli_prepare($conn, $up_sql);
            }

            if (!$ins_stmt) {
                $pesan = "Gagal mempersiapkan query INSERT: " . mysqli_error($conn);
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
                            $nis = isset($santri['nis']) ? trim((string)$santri['nis']) : '';
                            if ($nis === '') {
                                $failed++;
                                if (count($error_details) < 10) {
                                    $nama_err = $santri['nama'] ?? 'Tanpa Nama';
                                    $error_details[] = "Dilewati: Santri '$nama_err' tidak memiliki NIS.";
                                }
                                continue;
                            }

                            // Normalisasi status aktif:
                            // Jika bernilai Y/1/Aktif/kosong pada santri baru -> default 'Y'
                            // Jika bernilai T/0/Nonaktif/Mutasi/Keluar -> 'T'
                            $raw_aktif = isset($santri['aktif']) ? strtoupper(trim((string)$santri['aktif'])) : '';
                            if ($raw_aktif === 'T' || $raw_aktif === '0' || $raw_aktif === 'NONAKTIF' || $raw_aktif === 'MUTASI' || $raw_aktif === 'KELUAR') {
                                $aktif = 'T';
                            } else {
                                $aktif = 'Y';
                            }
                            $santri['aktif'] = $aktif;

                            // Normalisasi Jenis Kelamin (jkl):
                            // Pastikan bernilai 'Laki-laki' atau 'Perempuan'
                            if (isset($santri['jkl'])) {
                                $raw_jkl = strtoupper(trim((string)$santri['jkl']));
                                if (strpos($raw_jkl, 'L') === 0 || strpos($raw_jkl, 'PUTRA') !== false) {
                                    $santri['jkl'] = 'Laki-laki';
                                } elseif (strpos($raw_jkl, 'P') === 0 || strpos($raw_jkl, 'PUTRI') !== false) {
                                    $santri['jkl'] = 'Perempuan';
                                }
                            }

                            if (isset($existing_nis_map[$nis])) {
                                // UPDATE data santri lama
                                if ($up_stmt) {
                                    $up_params = [];
                                    foreach ($update_target_cols as $c) {
                                        $val = $santri[$c] ?? '';
                                        $meta = $target_col_info[$c] ?? [];
                                        $up_params[] = $sanitize_val($val, $meta);
                                    }
                                    $up_params[] = $nis; // Parameter WHERE nis = ?

                                    mysqli_stmt_bind_param($up_stmt, $up_types, ...$up_params);
                                    if (mysqli_stmt_execute($up_stmt)) {
                                        $updated++;
                                    } else {
                                        $failed++;
                                        if (count($error_details) < 10) {
                                            $error_details[] = "Gagal Update NIS {$nis}: " . mysqli_stmt_error($up_stmt);
                                        }
                                    }
                                }
                            } else {
                                // INSERT santri baru
                                $ins_params = [];
                                foreach ($insert_target_cols as $c) {
                                    $meta = $target_col_info[$c] ?? [];
                                    if (isset($santri[$c]) && $santri[$c] !== null) {
                                        $ins_params[] = $sanitize_val($santri[$c], $meta);
                                    } else {
                                        // Berikan nilai default aman jika kolom target tidak ada di db_sentral
                                        if (!empty($meta['null'])) {
                                            $ins_params[] = null;
                                        } elseif (isset($meta['default']) && $meta['default'] !== null) {
                                            $ins_params[] = (string)$meta['default'];
                                        } else {
                                            $col_type = $meta['type'] ?? '';
                                            if (strpos($col_type, 'int') !== false || strpos($col_type, 'decimal') !== false || strpos($col_type, 'float') !== false) {
                                                $ins_params[] = '0';
                                            } elseif (strpos($col_type, 'date') !== false) {
                                                $ins_params[] = '1970-01-01';
                                            } else {
                                                $ins_params[] = '';
                                            }
                                        }
                                    }
                                }

                                mysqli_stmt_bind_param($ins_stmt, $ins_types, ...$ins_params);
                                if (mysqli_stmt_execute($ins_stmt)) {
                                    $inserted++;
                                    $existing_nis_map[$nis] = true;
                                } else {
                                    $failed++;
                                    if (count($error_details) < 10) {
                                        $error_details[] = "Gagal Tambah Santri Baru (NIS {$nis}): " . mysqli_stmt_error($ins_stmt);
                                    }
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

                        // Refresh total statistik di db_sekretaris setelah sinkron
                        $q_count_sekretaris = mysqli_query($conn, "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN aktif = 'Y' OR aktif = '1' THEN 1 ELSE 0 END) as total_aktif,
                            SUM(CASE WHEN aktif != 'Y' AND aktif != '1' OR aktif IS NULL THEN 1 ELSE 0 END) as total_nonaktif
                        FROM tb_santri");
                        if ($q_count_sekretaris) {
                            $row_sek = mysqli_fetch_assoc($q_count_sekretaris);
                            $stat['total_sekretaris'] = (int)($row_sek['total'] ?? 0);
                            $stat['total_sekretaris_aktif'] = (int)($row_sek['total_aktif'] ?? 0);
                            $stat['total_sekretaris_nonaktif'] = (int)($row_sek['total_nonaktif'] ?? 0);
                        }

                        $pesan = "Sinkronisasi selesai dalam {$duration} detik. Santri Baru Ditambahkan: {$inserted}, Data Diperbarui: {$updated}" . ($failed > 0 ? ", Gagal/Dilewati: {$failed}" : "");
                        $tipe_pesan = ($failed > 0 && $inserted === 0 && $updated === 0) ? "danger" : "success";

                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        $pesan = "Terjadi kesalahan sistem saat sinkronisasi: " . $e->getMessage();
                        $tipe_pesan = "danger";
                    }

                    mysqli_stmt_close($ins_stmt);
                    if ($up_stmt) mysqli_stmt_close($up_stmt);
                } else {
                    $pesan = "Gagal membaca data santri dari db_sentral: " . mysqli_error($conn_sentral);
                    $tipe_pesan = "danger";
                }
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
                <?php if (!empty($error_details)): ?>
                    <ul style="margin-top: 8px; margin-bottom: 0;">
                        <?php foreach ($error_details as $err): ?>
                            <li><code><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Info Status Koneksi Database Sentral -->
            <div class="col-md-6 col-sm-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-database"></i> Status Database & Rincian Data</h3>
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
                            <h4><i class="fa fa-info-circle"></i> Mekanisme Sinkronisasi:</h4>
                            <ul style="padding-left: 20px;">
                                <li>Status <strong>Aktif (Y)</strong> dan <strong>Non-Aktif (T)</strong> disesuaikan persis dari DB Sentral.</li>
                                <li>Santri yang mutasi/keluar di DB Sentral akan otomatis ter-update statusnya di DB Sekretaris.</li>
                                <li>Data santri baru (NIS belum terdaftar di Sekretaris) otomatis ditambahkan.</li>
                            </ul>
                        </div>

                        <form action="" method="post" id="formSinkron">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="proses_sinkron" value="1">
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
                                            <span class="info-box-number"><?= number_format($stat['inserted'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="info-box bg-yellow">
                                        <span class="info-box-icon"><i class="fa fa-pencil-square-o"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Data Diperbarui</span>
                                            <span class="info-box-number"><?= number_format($stat['updated'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="info-box bg-red">
                                        <span class="info-box-icon"><i class="fa fa-exclamation-triangle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Gagal / Dilewati</span>
                                            <span class="info-box-number"><?= number_format($stat['failed'], 0, ',', '.'); ?></span>
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
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formSinkron');
    if (form) {
        form.addEventListener('submit', function(e) {
            var konfirmasi = confirm("Apakah Anda yakin ingin menyinkronkan seluruh data santri dari DB Sentral ke DB Sekretaris?");
            if (!konfirmasi) {
                e.preventDefault();
                return false;
            }
            var btn = document.getElementById('btnSinkron');
            if (btn) {
                setTimeout(function() {
                    btn.setAttribute('disabled', 'disabled');
                    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sedang Memproses Sinkronisasi...';
                }, 10);
            }
            return true;
        });
    }
});
</script>

<?php include 'foot.php'; ?>

