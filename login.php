<?php include_once("config.php"); session_start(); ?>
<?php
$username = $_COOKIE['username'] ?? null;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Autentificare - <?=$AppName?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
  <link rel="shortcut icon" href="/assets/imgs/favs/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/imgs/favs/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/imgs/favs/favicon-32x32.png">
  <link rel="manifest" href="/assets/imgs/favs/site.webmanifest">
  <style>
    body {
      background: linear-gradient(135deg, #343a40, #6c757d);
    }
    .lockscreen-wrapper {
      width: 100%;
      max-width: 420px;
      margin: auto;
      top: 50%;
      transform: translateY(50%);
      position: relative;
      text-align: center;
    }
    .lockscreen-logo img {
      width: 120px;
      margin-bottom: 10px;
    }
  </style>
</head>


<?php if ($username): ?>
<body class="hold-transition lockscreen">
  <div class="lockscreen-wrapper">
    <div class="lockscreen-logo">
      <a href="./"><img src="/assets/imgs/favs/favicon.svg" style="width: 100px;" alt="Logo"></a>
      <h4 class="text-light font-weight-bold">FQB ERP</h4>
    </div>
    <div class="lockscreen-name text-white"><?=htmlspecialchars($username)?></div>
    <div class="lockscreen-item">
      <form class="lockscreen-credentials" id="quick-login-form" method="POST">
        <div class="input-group">
          <input type="password" name="password" class="form-control" placeholder="Parolă" required>
          <div class="input-group-append">
            <button type="submit" class="btn"><i class="fas fa-arrow-right text-muted"></i></button>
          </div>
        </div>
      </form>
    </div>
    <div id="quick-login-feedback" class="alert d-none mt-2 px-2 py-1"></div>
    <div class="text-center mt-3">
      <a href="logout" class="text-light">Nu ești tu? Autentifică-te din nou</a>
    </div>
  </div>
<?php else: ?>
<body class="hold-transition login-page">
  <!-- formularul complet dacă nu avem cookie -->
  <div class="register-box">
    <div class="register-logo">
      <a href="./"><img src="/assets/imgs/favs/favicon.svg" style="width: 100px;" alt="logo not found"></a>
    </div>
    <div class="card shadow">
      <div class="card-body register-card-body">
        <p class="login-box-msg">Autentificare</p>

        <form id="register-form" method="POST">
          <div class="input-group mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
            <div class="input-group-append">
              <div class="input-group-text"><i class="fas fa-envelope"></i></div>
            </div>
          </div>
          <div class="input-group mb-3">
            <input type="password" name="password" class="form-control" placeholder="Parolă" required>
            <div class="input-group-append">
              <div class="input-group-text"><i class="fas fa-lock"></i></div>
            </div>
          </div>
          <div id="register-feedback" class="alert d-none"></div>
          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-info btn-block"><i class="fas fa-user-plus mr-1"></i> Autentifică-te</button>
            </div>
          </div>
        </form>

        <p class="mt-3 mb-1 text-center">
          Ai uitat parola? <a href="#">Resetează parola</a><br>
          Nu ai cont? <a href="/register">Înregistrare</a>
        </p>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  $('#quick-login-form').on('submit', function (e) {
    e.preventDefault();
    const form = $(this);
    const feedback = $('#quick-login-feedback');
    feedback.removeClass('alert-success alert-danger d-none').empty();
    const data = {
      email: "<?=htmlspecialchars($username)?>",
      password: form.find('input[name=password]').val()
    };

    $.post('login_process.php', data, function (res) {
      if (res.success) {
        feedback.addClass('alert-success').text('Autentificare reușită!');
        setTimeout(() => window.location.href = 'dashboard', 1500);
      } else {
        feedback.addClass('alert-danger').text(res.error || 'Eroare necunoscută.');
      }
    }, 'json').fail(() => {
      feedback.addClass('alert-danger').text('Eroare la conexiune cu serverul.');
    });
  });

  $('#register-form').on('submit', function (e) {
    e.preventDefault();
    const form = $(this);
    const feedback = $('#register-feedback');
    feedback.removeClass('alert-success alert-danger d-none').empty();

    $.post('login_process.php', form.serialize(), function (data) {
      if (data.success) {
        feedback.addClass('alert-success').html('Autentificare reușită!<br><small>Redirecționare în curs...</small>');
        setTimeout(() => window.location.href = 'dashboard', 2000);
      } else {
        feedback.addClass('alert-danger').html(data.error || 'Eroare necunoscută.');
      }
    }, 'json').fail(function () {
      feedback.addClass('alert-danger').html('Eroare la conexiunea cu serverul.');
    });
  });
</script>

</body>
</html>
