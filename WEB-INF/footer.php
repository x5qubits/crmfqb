
</div>
      <!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  <aside class="control-sidebar control-sidebar-dark">
<div class="p-3">
  <h5>Personalizare</h5>
 <button id="reset-theme" class="btn btn-danger btn-block btn-xs">Resetare</button>
 <hr class="mb-2"/>
 <div class="mb-1"><input id="swapModes" type="checkbox" class="mr-1" checked="checked">Dark-mode</div>
 <div class="mb-1"><input id="swapText" type="checkbox" class="mr-1" checked="checked">Text Mic</div>
<div class="form-group">
  <label>Selectați o temă</label>

		<div class="row" id="theme-presets-container"></div>

</div>
  <hr class="mb-2"/>

  <div id="layout-options" class="mb-4">
    <!-- Checkboxes se adaugă automat de script -->
  </div>
  <div id="navbar-options" class="mb-4">
    <!-- Dropdown Navbar -->
  </div>
  <div id="accent-options" class="mb-4">
    <!-- Dropdown Accent -->
  </div>
  <div id="sidebar-options" class="mb-4">
    <!-- Dropdown Sidebar -->
  </div>
  <div id="sidebar-bg-options" class="mb-2">
    <!-- Dropdown Sidebar BG -->
  </div>
</div>
  </aside>
  <script src="dist/js/demo.js"></script>
  <!-- Main Footer -->
  <footer class="main-footer">
    <strong>&copy; 2025 <?=$AppName?>.</strong>
     Toate drepturile rezervate.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0.5 LTS
    </div>
  </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<style>
.dark-mode .sidebar-dark-dark .nav-sidebar > .nav-item > .nav-link.active, .dark-mode .sidebar-light-dark .nav-sidebar > .nav-item > .nav-link.active {
  background-color: #6b42a7;
}
</style>
<script>
function loadNotifications() {
fetch('notifications_api.php')
.then(response => response.json())
.then(data => {
if (!data.success) return;
let count = data.total_count;
let menu = document.getElementById('notifications-menu');
let header = document.getElementById('notification-header');
let countSpan = document.getElementById('notification-count');


countSpan.textContent = count > 0 ? count : '';
header.textContent = count > 0 ? `Ai ${count} notificări` : 'No notifications';


let divider = menu.querySelector('.dropdown-divider');
menu.querySelectorAll('.dropdown-item.notification').forEach(e => e.remove());


data.notifications.forEach(n => {
let item = document.createElement('a');
item.className = 'dropdown-item notification';
item.href = n.link;
item.innerHTML = `<i class="${n.icon} text-${n.color} mr-2"></i> ${n.title} <span class="float-right text-muted text-sm">${n.time}</span>`;
menu.insertBefore(item, divider);
});
});
}


setInterval(loadNotifications, 60000);
loadNotifications();
</script>
</body>
</html>

<!-- 
<script>window.gtranslateSettings = {"default_language":"ro","native_language_names":true,"detect_browser_language":false,"languages":["ro","en","it","de","fr","bg","hu"],"wrapper_selector":".xlanguage","flag_size":16}</script>

<script src="https://cdn.gtranslate.net/widgets/latest/fn.js" defer></script>-->