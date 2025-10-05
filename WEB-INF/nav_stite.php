  <nav class="main-header navbar navbar-expand-md navbar-dark navbar-dark bg-dark">
    <div class="container">
      <a href="./" class="navbar-brand">
        <i class="fas fa-robot mr-2"></i>
        <?=$AppName?> Automatizare eMAG
      </a>
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a href="/" class="nav-link">Acasă</a>
            </li>
          <?php if (!isset($_SESSION['user_id'])): ?>
            <li class="nav-item">
              <a href="./login" class="nav-link">Autentificare</a>
            </li>
            <li class="nav-item">
              <a href="./register" class="nav-link">Înregistrare</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a href="./dashboard" class="nav-link">Dashboard</a>
            </li>
            <li class="nav-item">
              <a href="./logout" class="nav-link">Ieșire</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>