<?php

/**
 * Memastikan tabel setting tersedia di database
 */
function init_table_setting()
{
    global $conn;
    if ($conn) {
        $create_sql = "CREATE TABLE IF NOT EXISTS setting (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nama_key VARCHAR(100) NOT NULL UNIQUE,
            isi_key TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($conn, $create_sql);
    }
}

// Jalankan inisialisasi tabel setting
init_table_setting();

/**
 * Kirim Pesan ke Grup WhatsApp
 */
function kirim_wa_group($pesan, $groupId = '120363028015516743@g.us')
{
    global $conn;

    // Ambil apiKey dari tabel setting
    $apiKey = '';
    if ($conn) {
        $q = mysqli_query($conn, "SELECT isi_key FROM setting WHERE nama_key = 'apiKey' LIMIT 1");
        if ($q && $row = mysqli_fetch_assoc($q)) {
            $apiKey = trim($row['isi_key']);
        }
    }

    $payload = [
        'apiKey' => $apiKey,
        'groupId' => $groupId,
        'message' => $pesan,
        'sessionId' => 'default'
    ];

    $json_payload = json_encode($payload);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://wadwk.ppdwk.site/send-group',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json_payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_payload)
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

/**
 * Format Notifikasi Mutasi Baru (Permohonan Pengecekan Tanggungan)
 */
function notif_mutasi_baru($nama, $alamat, $sekolah, $tgl_mutasi)
{
    $pesan = "*INFORMASI MUTASI BARU*\n\n"
           . "*PERMOHONAN PENGECEKAN TANGGUNGAN SANTRI*\n    \n"
           . "Nama : " . $nama . "\n"
           . "Alamat : " . $alamat . "\n"
           . "Sekolah : " . $sekolah . "\n"
           . "Tgl Mutasi : " . $tgl_mutasi . "\n\n"
           . "*_dimohon kepada BENDAHARA PESANTREN untuk segera mengecek tanggungan nya_*\n"
           . "Terimakasih";

    return kirim_wa_group($pesan);
}

/**
 * Format Notifikasi Mutasi Resmi (Permohonan Pengeluaran Data Santri)
 */
function notif_mutasi_resmi($nama, $alamat, $sekolah, $tgl_mutasi)
{
    $pesan = "*INFORMASI MUTASI*\n\n"
           . "*PERMOHONAN PENGELUARAN DATA SANTRI*\n    \n"
           . "Nama : " . $nama . "\n"
           . "Alamat : " . $alamat . "\n"
           . "Sekolah : " . $sekolah . "\n"
           . "Tgl Mutasi : " . $tgl_mutasi . "\n\n"
           . "*_Surat mutasi sudah diterbitkan oleh SEKRETARIAT. Santri sudah resmi mutasi. Untuk selanjutnya kepada admin DPontren untuk mengeluarkan data santri diatas_*\n"
           . "Terimakasih";

    return kirim_wa_group($pesan);
}
