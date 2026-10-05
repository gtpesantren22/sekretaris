<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['qwertyuioplkjhgfdsa']) || $_SESSION['qwertyuioplkjhgfdsa'] !== true) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// Ambil parameter filter
$tgl_awal = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';
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

$query = "SELECT a.*, b.nama, b.jkl, b.tempat, b.tanggal, b.desa, b.kec, b.kab, b.prov, b.k_formal, b.t_formal, b.k_madin, b.r_madin, b.hp, b.bapak, b.ibu 
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

// Generate nama file
$filename_suffix = "Semua";
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $filename_suffix = $tgl_awal . "_sd_" . $tgl_akhir;
} elseif (!empty($tgl_awal)) {
    $filename_suffix = "dari_" . $tgl_awal;
} elseif (!empty($tgl_akhir)) {
    $filename_suffix = "sampai_" . $tgl_akhir;
}

$filename = "Rekap_Mutasi_Santri_" . $filename_suffix . ".xls";

// Set Header Excel
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8 BOM agar karakter khusus terbaca sempurna di Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Mutasi Santri</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }
        .sub-header {
            font-size: 12px;
            text-align: center;
            margin-bottom: 15px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #2e7d32;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #000000;
            padding: 8px 5px;
        }
        td {
            border: 1px solid #000000;
            padding: 6px 5px;
            vertical-align: middle;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .text-format {
            mso-number-format: "\@"; /* Format string/text di Excel agar leading zero tidak hilang */
        }
        .bg-total {
            background-color: #f1f8e9;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header-title">REKAPITULASI DATA MUTASI SANTRI</div>
    <div class="sub-header">
        Periode: <?= (!empty($tgl_awal) ? date('d/m/Y', strtotime($tgl_awal)) : 'Awal') ?> s/d <?= (!empty($tgl_akhir) ? date('d/m/Y', strtotime($tgl_akhir)) : 'Sekarang') ?> | Dicetak Pada: <?= date('d/m/Y H:i'); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 110px;">NIS</th>
                <th style="width: 200px;">Nama Santri</th>
                <th style="width: 90px;">Jenis Kelamin</th>
                <th style="width: 120px;">Formal</th>
                <th style="width: 120px;">Madin</th>
                <th style="width: 220px;">Alamat</th>
                <th style="width: 100px;">Tgl Mutasi</th>
                <th style="width: 220px;">Alasan Mutasi</th>
                <th style="width: 140px;">Status Mutasi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $count_pa = 0;
            $count_pi = 0;

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if (($row['jkl'] ?? '') === 'Laki-laki') {
                        $count_pa++;
                    } else {
                        $count_pi++;
                    }

                    // Keterangan status
                    if ($row['status'] == 0) {
                        $status_text = "Menunggu Verval";
                    } elseif ($row['status'] == 1) {
                        $status_text = "Verval Bendahara";
                    } elseif ($row['status'] == 2) {
                        $status_text = "Selesai (Kirim Pendataan)";
                    } else {
                        $status_text = "Status: " . $row['status'];
                    }

                    $alamat = trim(($row['desa'] ?? '') . ' - ' . ($row['kec'] ?? '') . ' - ' . ($row['kab'] ?? ''));
                    $formal = trim(($row['k_formal'] ?? '') . ' ' . ($row['t_formal'] ?? ''));
                    $madin = trim(($row['k_madin'] ?? '') . ' ' . ($row['r_madin'] ?? ''));
            ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td class="text-center text-format"><?= htmlspecialchars($row['nis'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['jkl'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-left"><?= htmlspecialchars($formal, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-left"><?= htmlspecialchars($madin, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-left"><?= htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-center"><?= !empty($row['tgl_mutasi']) ? date('d/m/Y', strtotime($row['tgl_mutasi'])) : '-'; ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['alasan'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-center"><?= $status_text; ?></td>
                    </tr>
            <?php
                }
            ?>
                <tr class="bg-total">
                    <td colspan="3" class="text-center">TOTAL DATA: <?= ($no - 1); ?> Santri</td>
                    <td class="text-center">Putra: <?= $count_pa; ?> | Putri: <?= $count_pi; ?></td>
                    <td colspan="6"></td>
                </tr>
            <?php
            } else {
            ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; font-style: italic;">Tidak ada data mutasi santri untuk periode tanggal yang dipilih.</td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>

</body>
</html>
