<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['qwertyuioplkjhgfdsa']) && $_SESSION['qwertyuioplkjhgfdsa'] === true) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

$error = '';
$success = '';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftar'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = "Token keamanan tidak valid. Silakan coba lagi.";
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $user = trim($_POST['user'] ?? '');
        $pass = $_POST['pass'] ?? '';

        if (empty($nama) || empty($user) || empty($pass)) {
            $error = "Semua kolom wajib diisi!";
        } elseif (strlen($pass) < 6) {
            $error = "Password minimal terdiri dari 6 karakter.";
        } else {
            // Check if username already exists using Prepared Statement
            $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE username = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $user);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $error = "Username sudah terpakai, silakan gunakan username lain.";
                } else {
                    mysqli_stmt_close($stmt);

                    // Hash password properly
                    $passOk = password_hash($pass, PASSWORD_DEFAULT);
                    $level = 'admin';
                    $aktif = 'T';

                    $insStmt = mysqli_prepare($conn, "INSERT INTO user VALUES ('', ?, ?, ?, ?, ?)");
                    if ($insStmt) {
                        mysqli_stmt_bind_param($insStmt, "sssss", $nama, $user, $passOk, $level, $aktif);
                        if (mysqli_stmt_execute($insStmt)) {
                            $success = "Pendaftaran berhasil! Silakan hubungi admin untuk aktivasi akun Anda.";
                        } else {
                            $error = "Gagal mendaftarkan akun. Silakan coba lagi.";
                        }
                        mysqli_stmt_close($insStmt);
                    } else {
                        $error = "Terjadi kesalahan sistem saat mendaftar.";
                    }
                }
            } else {
                $error = "Terjadi kesalahan koneksi database.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>SiPasTren | Registration Page</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.5 -->
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/iCheck/square/blue.css">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body class="hold-transition register-page">
  <div class="register-box">
    <div class="register-logo">
      <a href="#"><img src="dist/img/SipasTren.png" alt="SiPasTren" width="300px"></a>
    </div>

    <div class="register-box-body">
      <p class="login-box-msg">Register a new membership</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <i class="icon fa fa-ban"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <i class="icon fa fa-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form action="" method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group has-feedback">
          <input type="text" class="form-control" placeholder="Full name" name="nama" required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama'], ENT_QUOTES, 'UTF-8') : ''; ?>">
          <span class="glyphicon glyphicon-user form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
          <input type="text" class="form-control" placeholder="Username" name="user" required value="<?= isset($_POST['user']) ? htmlspecialchars($_POST['user'], ENT_QUOTES, 'UTF-8') : ''; ?>">
          <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
          <input type="password" class="form-control" placeholder="Password" name="pass" required minlength="6">
          <span class="glyphicon glyphicon-lock form-control-feedback"></span>
        </div>
        <div class="row">
          <div class="col-xs-8">
            <div class="checkbox icheck">
              <label>
                <input type="checkbox" required> I agree to the <a href="#">terms</a>
              </label>
            </div>
          </div><!-- /.col -->
          <div class="col-xs-4">
            <button type="submit" name="daftar" class="btn btn-primary btn-block btn-flat">Register</button>
          </div><!-- /.col -->
        </div>
      </form>

      <a href="login.php" class="text-center">I already have a membership</a>
    </div><!-- /.form-box -->
  </div><!-- /.register-box -->

  <!-- jQuery 2.1.4 -->
  <script src="plugins/jQuery/jQuery-2.1.4.min.js"></script>
  <!-- Bootstrap 3.3.5 -->
  <script src="bootstrap/js/bootstrap.min.js"></script>
  <!-- iCheck -->
  <script src="plugins/iCheck/icheck.min.js"></script>
  <script>
    $(function() {
      $('input').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%' // optional
      });
    });
  </script>
</body>

</html>