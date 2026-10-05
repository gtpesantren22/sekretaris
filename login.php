<?php
// Set secure session cookie parameters
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['qwertyuioplkjhgfdsa']) && $_SESSION['qwertyuioplkjhgfdsa'] === true) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

$error = '';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting settings: max 5 attempts, cooldown 60 seconds
$max_attempts = 5;
$lockout_time = 60;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['last_attempt_time'])) {
    $_SESSION['last_attempt_time'] = time();
}

// Check rate limit lockout
$time_since_last = time() - $_SESSION['last_attempt_time'];
if ($_SESSION['login_attempts'] >= $max_attempts) {
    if ($time_since_last < $lockout_time) {
        $remaining = $lockout_time - $time_since_last;
        $error = "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$remaining} detik.";
    } else {
        // Reset after lockout expires
        $_SESSION['login_attempts'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['masuk'])) {
    // Check if locked out
    if ($_SESSION['login_attempts'] >= $max_attempts && $time_since_last < $lockout_time) {
        $remaining = $lockout_time - $time_since_last;
        $error = "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$remaining} detik.";
    } else {
        // 1. Verify CSRF Token
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            $error = "Token keamanan tidak valid (CSRF detected). Silakan refresh halaman.";
        } else {
            $user = trim($_POST['user'] ?? '');
            $pass = $_POST['pass'] ?? '';

            if (empty($user) || empty($pass)) {
                $error = "Username dan Password wajib diisi!";
            } else {
                // 2. Use Prepared Statement against SQL Injection
                $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE username = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $user);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($result && $dt = mysqli_fetch_assoc($result)) {
                        // Check if account is active
                        if (isset($dt['aktif']) && $dt['aktif'] !== 'Y') {
                            $error = "Maaf, akun Anda belum aktif. Silakan hubungi admin.";
                        } else {
                            // Check password with password_verify
                            $passwordValid = password_verify($pass, $dt['password']);

                            // Fallback for legacy hashes if any was lowercase-hashed
                            if (!$passwordValid && password_verify(strtolower($pass), $dt['password'])) {
                                $passwordValid = true;
                            }

                            // Fallback for plaintext (if legacy plain text exists, auto-upgrade hash)
                            if (!$passwordValid && $dt['password'] === $pass) {
                                $passwordValid = true;
                                $newHash = password_hash($pass, PASSWORD_DEFAULT);
                                $upStmt = mysqli_prepare($conn, "UPDATE user SET password = ? WHERE username = ?");
                                if ($upStmt) {
                                    mysqli_stmt_bind_param($upStmt, "ss", $newHash, $user);
                                    mysqli_stmt_execute($upStmt);
                                    mysqli_stmt_close($upStmt);
                                }
                            }

                            if ($passwordValid) {
                                // 3. Prevent Session Fixation
                                session_regenerate_id(true);

                                // Reset attempts on successful login
                                unset($_SESSION['login_attempts'], $_SESSION['last_attempt_time']);

                                // 4. Set Session variables
                                $_SESSION['qwertyuioplkjhgfdsa'] = true;
                                $_SESSION['nama'] = $dt['nama'] ?? '';
                                $_SESSION['username'] = $dt['username'] ?? $user;
                                $_SESSION['level'] = $dt['level'] ?? 'admin';
                                if (isset($dt['id'])) {
                                    $_SESSION['id_user'] = $dt['id'];
                                } elseif (isset($dt['id_user'])) {
                                    $_SESSION['id_user'] = $dt['id_user'];
                                } elseif (isset($dt['no'])) {
                                    $_SESSION['id_user'] = $dt['no'];
                                }

                                // Refresh CSRF token for next actions
                                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                                header("Location: index.php");
                                exit;
                            } else {
                                $_SESSION['login_attempts']++;
                                $_SESSION['last_attempt_time'] = time();
                                $error = "Username atau password salah.";
                            }
                        }
                    } else {
                        // User not found - generic message to prevent username enumeration
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt_time'] = time();
                        $error = "Username atau password salah.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = "Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.";
                }
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
  <title>SiPasTren | Log in</title>
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

<body class="hold-transition login-page">
  <div class="login-box">
    <div class="login-logo">
      <a href="#"><img src="dist/img/SipasTren.png" alt="SiPasTren" width="300px"></a>
    </div><!-- /.login-logo -->
    <div class="login-box-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <i class="icon fa fa-ban"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form action="" method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group has-feedback">
          <input type="text" class="form-control" placeholder="Username" name="user" required autofocus value="<?= isset($_POST['user']) ? htmlspecialchars($_POST['user'], ENT_QUOTES, 'UTF-8') : ''; ?>">
          <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
          <input type="password" class="form-control" placeholder="Password" name="pass" required>
          <span class="glyphicon glyphicon-lock form-control-feedback"></span>
        </div>
        <div class="row">
          <div class="col-xs-8">
            <div class="checkbox icheck">
              <label>
                <input type="checkbox"> Remember Me
              </label>
            </div>
          </div><!-- /.col -->
          <div class="col-xs-4">
            <button type="submit" name="masuk" class="btn btn-primary btn-block btn-flat">Sign In</button>
          </div><!-- /.col -->
        </div>
      </form>

      <a href="register.php" class="text-center">Register a new membership</a>

    </div><!-- /.login-box-body -->
  </div><!-- /.login-box -->

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