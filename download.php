<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['qwertyuioplkjhgfdsa']) || $_SESSION['qwertyuioplkjhgfdsa'] !== true) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['filename'])) {
    $filename = basename($_GET['filename']);
    $back_dir = "upload/QR-Code/";
    $file = $back_dir . $filename;
     
    if (file_exists($file) && is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private');
        header('Pragma: private');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    } else {
        $_SESSION['pesan'] = "Oops! File - " . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . " - not found ...";
        header("Location: index.php");
        exit;
    }
}

