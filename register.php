<?php include_once("config.php"); session_start(); ?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Înregistrare - <?=$AppName?> Automatizare eMAG</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
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
    .register-box {
      max-width: 480px;
      margin: 5% auto;
    }
    .register-logo a {
      color: white;
      font-weight: bold;
    }
    .card {
      border-radius: 1rem;
    }
    label {
      font-weight: 500;
    }
  </style>
</head>
<body class="hold-transition register-page">
<div class="register-box">
  <div class="register-logo">
    <a href="./"><img src="/assets/imgs/logo/logo-5a.png" alt="Logo"></a>
  </div>
  <div class="card shadow">
    <div class="card-body register-card-body">
      <p class="login-box-msg">Creează un cont <strong>nou</strong> pentru a accesa panoul de control eMAG</p>

      <form id="register-form" method="POST">
        <div class="form-group">
          <label for="name">Nume firmă</label>
          <div class="input-group">
            <input type="text" name="name" id="name" class="form-control" placeholder="Ex: SC Firma SRL" required>
            <div class="input-group-append">
              <div class="input-group-text"><i class="fas fa-building"></i></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email utilizat pe eMAG</label>
          <div class="input-group">
            <input type="email" name="email" id="email" class="form-control" placeholder="email@firma.ro" required>
            <div class="input-group-append">
              <div class="input-group-text"><i class="fas fa-envelope"></i></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="password">Parolă pentru platforma noastră</label>
          <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" placeholder="Alege o parolă sigură" required>
            <div class="input-group-append">
              <div class="input-group-text"><i class="fas fa-lock"></i></div>
            </div>
          </div>
        </div>

        <div class="form-group form-check mb-3">
          <input type="checkbox" name="consent_cross_seller_data" id="consent" class="form-check-input" required>
          <label class="form-check-label small text-muted" for="consent">
            Sunt de acord cu <a href="/terms" target="_blank">Termenii și Condițiile</a> și <a href="/cookies" target="_blank">Politica de Confidențialitate</a>.
          </label>
        </div>

        <div id="register-feedback" class="alert d-none"></div>

        <div class="form-group">
          <button type="submit" class="btn btn-info btn-block"><i class="fas fa-user-plus mr-1"></i> Creează cont</button>
        </div>
      </form>

      <p class="mt-3 mb-1 text-center">
        Ai deja un cont? <a href="login">Autentifică-te aici</a>
      </p>
    </div>
  </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  $('#register-form').on('submit', function (e) {
    e.preventDefault();
    const form = $(this);
    const feedback = $('#register-feedback');
    feedback.removeClass('alert-success alert-danger d-none').empty();

    $.post('register_user.php', form.serialize(), function (data) {
      if (data.success) {
        feedback.addClass('alert-success').html('Înregistrare reușită! Redirecționare în curs...');
        setTimeout(() => window.location.href = 'info-0', 2000);
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
