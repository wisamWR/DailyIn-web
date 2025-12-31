<?php
//memulai session atau melanjutkan session yang sudah ada
session_start();

//menyertakan code dari file koneksi
include "koneksi.php";

//check jika sudah ada user yang login arahkan ke halaman admin
if (isset($_SESSION['username'])) { 
	header("location:admin.php"); 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['user'];
  
  //menggunakan fungsi enkripsi md5 supaya sama dengan password  yang tersimpan di database
  $password = md5($_POST['pass']);

	//prepared statement
  $stmt = $conn->prepare("SELECT username 
                          FROM user 
                          WHERE username=? AND password=?");

	//parameter binding 
  $stmt->bind_param("ss", $username, $password);//username string dan password string
  
  //database executes the statement
  $stmt->execute();
  
  //menampung hasil eksekusi
  $hasil = $stmt->get_result();
  
  //mengambil baris dari hasil sebagai array asosiatif
  $row = $hasil->fetch_array(MYSQLI_ASSOC);

  //check apakah ada baris hasil data user yang cocok
  if (!empty($row)) {
    //jika ada, simpan variable username pada session
    $_SESSION['username'] = $row['username'];

    //mengalihkan ke halaman admin
    header("location:admin.php");
  } else {
	  //jika tidak ada (gagal), alihkan kembali ke halaman login
    header("location:login.php");
  }

	//menutup koneksi database
  $stmt->close();
  $conn->close();
} else {
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | DailyIn</title>
    <link rel="icon" href="/img/logo.png" />
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
    <?php
$username = "admin";
$password = "123456";
?>
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
          <form action="" method="post">
            <input
              type="text"
              name="user"
              class="form-control my-4 py-2 rounded-4"
              placeholder="Username"
            />
            <input
              type="password"
              name="pass"
              class="form-control my-4 py-2 rounded-4"
              placeholder="Password"
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
<?php if ($_SERVER["REQUEST_METHOD"] == "POST") : ?>
<div class="d-flex justify-content-center mt-4">
    <div class="p-3 rounded-5 shadow border-0
    <?php 
        if($_POST['user'] == $username && $_POST['pass'] == $password){
            echo 'bg-success-subtle border border-success';
        } else {
            echo 'bg-warning-subtle border border-warning';
        }
    ?>
    "
        style="max-width: 350px; width: 100%;">
        
        <div class="text-center">
            <?php
                echo "user : " . $_POST['user'] . "<br>";
                echo "pass : " . $_POST['pass'] . "<br><br>";

                if($_POST['user'] == $username && $_POST['pass'] == $password){
                    echo "<b class='text-success'>Username dan Password Benar</b>";
                } else {
                    echo "<b class='text-primary'>Username dan Password Salah</b>";
                }
            ?>
        </div>

    </div>
</div>
<?php endif; ?>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
      crossorigin="anonymous"
    ></script>
    <script type="text/javascript">
      window.setTimeout("tampilWaktu()", 1000);

      function tampilWaktu() {
        var waktu = new Date();
        var bulan = waktu.getMonth() + 1;

        setTimeout("tampilWaktu()", 1000);
        document.getElementById("tanggal").innerHTML =
          waktu.getDate() + "/" + bulan + "/" + waktu.getFullYear();
        document.getElementById("jam").innerHTML =
          waktu.getHours() +
          ":" +
          waktu.getMinutes() +
          ":" +
          waktu.getSeconds();
      }
    </script>
    <script type="text/javascript">
      const html = document.documentElement;
      const lightIcon = document.getElementById("lightIcon");
      const darkIcon = document.getElementById("darkIcon");

      if (window.matchMedia("(prefers-color-scheme: light)").matches) {
        html.setAttribute("data-bs-theme", "light");
      }

      lightIcon.addEventListener("click", () => {
        html.setAttribute("data-bs-theme", "light");
      });

      darkIcon.addEventListener("click", () => {
        html.setAttribute("data-bs-theme", "dark");
      });
    </script>
  </body>
</html>

<?php
}
?>