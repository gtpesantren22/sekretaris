<?php
session_start();
$_SESSION = [];
session_destroy();
session_unset();

// hapus cookie
// setcookie('id', '', time() - 3600);
// setcookie('key', '', time() - 3600);

echo "<script>
window.location.href='login.php';
</script>";
?>
<!-- SAD -->