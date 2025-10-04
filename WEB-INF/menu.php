<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}


?>
<?php
// assume $pdo (PDO). If you use mysqli, adapt prepare/execute/fetch accordingly.
$user_id = $_SESSION['user_id'] ?? 1;
if (!isset($conn)) {
    require_once __DIR__ . '/../db.php';
}
$unreadCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM emails WHERE user_id = ? AND folder = 'inbox' AND is_read = 0");
    $stmt->execute([$user_id]);
    $unreadCount = (int) $stmt->fetchColumn();
} catch (Throwable $e) { $unreadCount = 0; }

// Today window in Europe/Bucharest
$tz = new DateTimeZone('Europe/Bucharest');
$todayStart = (new DateTime('today', $tz))->format('Y-m-d H:i:s');
$tomorrowStart = (new DateTime('tomorrow', $tz))->format('Y-m-d H:i:s');

$todayEvents = [];
try {
    $q = "
        SELECT id, type, title, `start`, `all_day`
        FROM calendar_events
        WHERE user_id = ?
          AND `start` >= ?
          AND `start` < ?
        ORDER BY `start` ASC
    ";
    $stmt = $pdo->prepare($q);
    $stmt->execute([$user_id, $todayStart, $tomorrowStart]);
    $todayEvents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { $todayEvents = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=$pageName?> - <?=$AppName?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="plugins/ionicons-2.0.1/css/ionicons.min.css">
  <link rel="shortcut icon" type="image/x-icon" href="/assets/imgs/favs/favicon.ico">
	<link rel="apple-touch-icon" sizes="180x180" href="/assets/imgs/favs/apple-touch-icon.png">
	<link rel="manifest" href="/assets/imgs/favs/site.webmanifest">
	<style>
	.previous { display:none }
	.next { display:none }
	</style>
</head>
<body class="hold-transition sidebar-mini dark-mode">
<div class="wrapper">
   <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="dist/img/logo.png" alt="AdminLTELogo" height="60" width="60" style="display: none;">
  </div>
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
<?php
$docArticles = array();
?>
<li class="nav-item dropdown">
  <a class="nav-link" data-toggle="dropdown" href="#" title="Documentație">
    <i class="fas fa-book"></i>
  </a>
  <div class="dropdown-menu dropdown-menu-right">
    <span class="dropdown-header">Documentație</span>
    <div class="dropdown-divider"></div>
    <?php foreach($docArticles as $key => $value): ?>
      <?php
        $docTitle = GetServiceField2($value, 0);
        $docLink = 'info-' . $key;
      ?>
      <a href="<?=$docLink?>" class="dropdown-item">
        <i class="far fa-file-alt mr-2"></i> <?=$docTitle?>
      </a>
    <?php endforeach; ?>
  </div>
</li>
<!--  
<li class="nav-item dropdown">
  <a class="nav-link" data-toggle="dropdown" href="#" title="Setări cont">
	<i class="fas fa-cog"></i>
  </a>
  <div class="dropdown-menu dropdown-menu-right xlanguage">
  
  </div>
</li>
-->
<li class="nav-item dropdown">
  <a class="nav-link" data-toggle="dropdown" href="#" title="Setări cont">
	<i class="fas fa-cog"></i>
  </a>
  <div class="dropdown-menu dropdown-menu-right">
	<span class="dropdown-header">Setări</span>
	<div class="dropdown-divider"></div>
	<a href="./profile" class="dropdown-item">
	  <i class="fas fa-user-cog mr-2"></i> Cont
	</a>
	<a href="./contract_templates" class="dropdown-item">
	  <i class="fas fa-money-bill-wave mr-2"></i> Template Contracte
	</a>
	<a href="./offer_templates" class="dropdown-item">
	  <i class="fas fa-money-bill-wave mr-2"></i> Template Oferte
	</a>	
	<a href="./oblio" class="dropdown-item">
	  <i class="fas fa-file mr-2"></i> Setări Oblio
	</a>
	
	
	
	<?php if($isAdmin): ?>
	<div class="dropdown-divider"></div>
	<a href="./eadmin" class="dropdown-item">
	  <i class="fas fa-tools mr-2"></i> Admin Panel
	</a>
	<?php endif; ?>
  </div>
</li>
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">0</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
		  <span class="dropdown-item dropdown-header">0 Mesaje</span>
		  <div class="dropdown-divider"></div>
		<!--
        
          
          <a href="#" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> 4 new messages
            <span class="float-right text-muted text-sm">3 mins</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-users mr-2"></i> 8 friend requests
            <span class="float-right text-muted text-sm">12 hours</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-file mr-2"></i> 3 new reports
            <span class="float-right text-muted text-sm">2 days</span>
          </a>

		 -->
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">Vezi toate Mesajele</a>
		
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" title="Iesi din cont" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
          <i class="fas fa-th-large"></i>
        </a>
      </li>
	  <li class="nav-item"><a class="nav-link" href="./login" title="Iesi din cont" role="button"><i class="fas fa-share"></i></a></li>
	</ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar elevation-4 sidebar-dark-info">
    <!-- Brand Logo -->
    <a href="./dashboard" class="brand-link">
      <img src="/assets/imgs/favs/apple-touch-icon.png" alt="<?=$AppName?> Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light"><?=$AppName?></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <?php 
	  if(isset($_SESSION['user_name'])) {
		require 'db.php';
		$user_id =$_SESSION['user_id'];
		$membersip = GetMembership($_SESSION['membersip']);

         echo '<div class="user-panel mt-3 pb-3 mb-3 d-flex"><div class="info"><a href="./billing" class="d-block"> ';
		if($_SESSION['membersip_days'] > 0) {
		   echo '<span class="badge badge-'.GetBadgeColor($_SESSION['membersip']).' elevation-2">'.$membersip['badge'].' '.$_SESSION['membersip_days'].' '.($_SESSION['membersip_days'] == 1 ? "zi":"zile").' ramase</span>';
		}else{
		   echo '<span class="badge badge-danger elevation-2">'.$membersip['badge'].' <strong>expirat</strong></span>';
		}
			
         echo '</a> <a href="./profile" class="d-block">'.$_SESSION['user_name'].'</a>
        </div>
		
      </div>';
		  

	  }
	  ?>
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Cautare" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item <?=($pageId == 0 ? "nav-item menu-is-opening menu-open":"")?>">
            <a href="/dashboard" class="nav-link <?=($pageId == 0 ? "active":"")?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="./" class="nav-link <?=($pageIds == 0 ? "active":"")?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Financiar</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./dashboard" class="nav-link <?=($pageIds == 1 ? "active":"")?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Evenimente</p>
                </a>
              </li>

            </ul>
          </li>

		  <li class="nav-header">Gestionare Companii</li>
          <li class="nav-item ">
            <a href="./firms" class="nav-link <?=($pageId == 1 ? "active":"")?>">
              <i class="nav-icon fas fa-copy"></i>
              <p>
                Companii
              </p>
            </a>
          </li>
          <li class="nav-item ">
            <a href="./leads" class="nav-link <?=($pageId == 2 ? "active":"")?>">
              <i class="nav-icon fas fa-chart-pie"></i>
              <p>
                Oferte Lansate
              </p>
            </a>
          </li>	
          <li class="nav-item ">

		  
		  <li class="nav-header">Work</li>
			<li class="nav-item">
			  <a href="./mailbox" class="nav-link <?=($pageId == 3 ? "active":"")?>">
				<i class="nav-icon fas fa-envelope"></i>
				<p>
				  Email
				  <span class="badge badge-info right" id="unread-count"><?= $unreadCount ?></span>
				</p>
			  </a>
			</li>
	  
		  <li class="nav-item">
            <a href="calendar" class="nav-link active">
              <i class="nav-icon far fa-calendar-alt"></i>
              <p>
                Calendar
                <span class="badge badge-info right"><?= count($todayEvents) ?></span>
              </p>
            </a>
          </li>
		  <li class="nav-item">
            <a href="campaigns" class="nav-link">
              <i class="nav-icon fas fa-columns"></i>
              <p>
                Campanii
              </p>
            </a>
          </li>				  
		  	<!--
		  <li class="nav-item">
            <a href="kanban.html" class="nav-link">
              <i class="nav-icon fas fa-columns"></i>
              <p>
                Kanban Board
              </p>
            </a>
          </li>		  
		  -->
		  
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><?=$pageName?></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="./<?=$MainPageh?>"><?=$MainPage?></a></li>
			  <?php if(isset($pageName)) { ?>
              <li class="breadcrumb-item active"><?=$pageName?></li>
			  
			  <?php } ?>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
  