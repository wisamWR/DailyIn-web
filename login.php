<?php
//memulai session atau melanjutkan session yang sudah ada (cookie httponly + samesite)
require_once "auth.php";


//check jika sudah ada user yang login arahkan ke halaman admin
if (is_logged_in()) {
  header("location:admin.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  verify_csrf();

  $username = trim($_POST['user'] ?? '');
  $password = $_POST['pass'] ?? '';

  //prepared statement, ambil hash password user
  $row = db_one("SELECT id, username, password
                  FROM " . USER_TABLE . "
                  WHERE username = ?", [$username]);

  $valid = false;
  if ($row) {
    if (password_verify($password, $row['password'])) {
      $valid = true;
      if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
        $upgrade = true;
      }
    } elseif (preg_match('/^[a-f0-9]{32}$/i', $row['password']) && hash_equals(strtolower($row['password']), md5($password))) {
      //password lama masih md5: terima sekali lalu ganti ke bcrypt
      $valid = true;
      $upgrade = true;
    }
  }

  if ($valid && !empty($upgrade)) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    db_run("UPDATE " . USER_TABLE . " SET password = ? WHERE id = ?", [$hash, $row['id']]);
  }

  if ($valid) {
    //ganti session id supaya tidak bisa session fixation
    session_regenerate_id(true);
    $_SESSION['username'] = $row['username'];
    unset($_SESSION['csrf_token']);

    //mengalihkan ke halaman admin
    header("location:admin.php");
  } else {
    //perlambat percobaan brute force
    sleep(1);

    //jika tidak ada (gagal), alihkan kembali ke halaman login
    header("location:login.php?error=1");
  }

  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | DailyIn</title>
    <link rel="icon" href="img/logo.png" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous"
    />
  </head>
  <body class="bg-primary-subtle">
    <div class="container mt-5 pt-5">
  <div class="row">
    <div class="col-12 col-sm-8 col-md-6 m-auto">
      <div class="card border-0 shadow rounded-5">
        <div class="card-body">
          <div class="text-center mb-3">
            <i class="bi bi-person-circle h1 display-4"></i>
            <p>DailyIn Account</p>
            <hr />
          </div>
          <?php if (isset($_GET['error'])) : ?>
            <div class="alert alert-warning rounded-4 text-center py-2">Username atau password salah</div>
          <?php endif; ?>
          <form action="" method="post">
            <?= csrf_field() ?>
            <input
              type="text"
              name="user"
              class="form-control my-4 py-2 rounded-4"
              placeholder="Username"
              autocomplete="username"
              required
            />
            <input
              type="password"
              name="pass"
              class="form-control my-4 py-2 rounded-4"
              placeholder="Password"
              autocomplete="current-password"
              required
            />
            <div class="text-center my-3 d-grid">
              <button class="btn btn-primary rounded-4">Login</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
      crossorigin="anonymous"
    ></script>
  </body>
</html>
