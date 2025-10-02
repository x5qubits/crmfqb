$(function () {
  const navbar_dark_skins = ['navbar-dark', 'navbar-primary', 'navbar-secondary', 'navbar-info', 'navbar-success', 'navbar-danger', 'navbar-indigo', 'navbar-purple', 'navbar-pink', 'navbar-navy', 'navbar-lightblue', 'navbar-teal', 'navbar-cyan', 'navbar-dark'];
  const navbar_light_skins = ['navbar-light', 'navbar-white', 'navbar-orange', 'navbar-warning', 'navbar-light'];
  const accent_colors = ['accent-primary', 'accent-warning', 'accent-info', 'accent-danger', 'accent-success', 'accent-indigo', 'accent-lightblue', 'accent-navy', 'accent-purple', 'accent-fuchsia', 'accent-pink', 'accent-maroon', 'accent-orange', 'accent-lime', 'accent-teal', 'accent-olive'];
  const sidebar_colors = ['sidebar-dark-dark','sidebar-dark-primary', 'sidebar-dark-warning', 'sidebar-dark-info', 'sidebar-dark-danger', 'sidebar-dark-success', 'sidebar-dark-indigo', 'sidebar-dark-lightblue', 'sidebar-dark-navy', 'sidebar-dark-purple', 'sidebar-dark-fuchsia', 'sidebar-dark-pink', 'sidebar-dark-maroon', 'sidebar-dark-orange', 'sidebar-dark-lime', 'sidebar-dark-teal', 'sidebar-dark-olive', 'sidebar-light-primary', 'sidebar-light-warning', 'sidebar-light-info', 'sidebar-light-danger', 'sidebar-light-success', 'sidebar-light-indigo', 'sidebar-light-lightblue', 'sidebar-light-navy', 'sidebar-light-purple', 'sidebar-light-fuchsia', 'sidebar-light-pink', 'sidebar-light-maroon', 'sidebar-light-orange', 'sidebar-light-lime', 'sidebar-light-teal', 'sidebar-light-olive'];
  const bg_colors = ['bg-dark','bg-primary', 'bg-warning', 'bg-info', 'bg-danger', 'bg-success', 'bg-indigo', 'bg-lightblue', 'bg-navy', 'bg-purple', 'bg-fuchsia', 'bg-pink', 'bg-maroon', 'bg-orange', 'bg-lime', 'bg-teal', 'bg-olive'];

  const settings = [
   
    { key: 'navbar-fixed', selector: 'body', class: 'layout-navbar-fixed', textName: 'Menu fix' },
    { key: 'sidebar-collapse', selector: 'body', class: 'sidebar-collapse', textName: 'Ascunde Menu' },
    { key: 'nav-flat', selector: '.nav-sidebar', class: 'nav-flat', textName: 'Menu 1' },
    { key: 'nav-legacy', selector: '.nav-sidebar', class: 'nav-legacy', textName: 'Menu 2' },
    { key: 'nav-compact', selector: '.nav-sidebar', class: 'nav-compact', textName: 'Menu compact' }
  ];

  function updateClassSetting(key, selector, className, enable) {
    const $el = $(selector);
    if (enable) {
      $el.addClass(className);
      localStorage.setItem(key, '1');
    } else {
      $el.removeClass(className);
      localStorage.setItem(key, '0');
    }
  }

function restoreSettings() {
    settings.forEach(s => {
      if (localStorage.getItem(s.key) === '1') {
        $(s.selector).addClass(s.class);
      }else{
		  $(s.selector).removeClass(s.class);
	  }
    });

    const $main_header = $('.main-header');
    const navbar_skin = localStorage.getItem('navbar-skin');
    if (navbar_skin) {
      $main_header.removeClass(navbar_dark_skins.concat(navbar_light_skins).join(' ')).addClass(navbar_skin);
      $main_header.removeClass('navbar-dark navbar-light');
      $main_header.addClass(navbar_dark_skins.includes(navbar_skin) ? 'navbar-dark' : 'navbar-light');
    }
    const navbar_skin1 = localStorage.getItem('dark-mode') == 1;
    if (navbar_skin1) {
		$('body').addClass('dark-mode');
    }else{
		$('body').removeClass('dark-mode');
	}
	
	if(localStorage.getItem('dark-mode') == null){
		console.log(localStorage.getItem('dark-mode'));
		ResetDefault();
		return;
	}
 
    const accent = localStorage.getItem('accent');
    if (accent) $('body').removeClass(accent_colors.join(' ')).addClass(accent);

    const sidebar_skin = localStorage.getItem('sidebar-skin');
    if (sidebar_skin) $('.main-sidebar').removeClass(sidebar_colors.join(' ')).addClass(sidebar_skin);

    const sidebar_bg = localStorage.getItem('sidebar-bg');
    if (sidebar_bg) $('.main-sidebar').removeClass(bg_colors.join(' ')).addClass(sidebar_bg);
	$('.main-header').addClass(localStorage.getItem('navbar-skin') || 'navbar-dark');
	$('body').addClass(localStorage.getItem('accent') || 'accent-while');
	$('.main-sidebar').addClass(localStorage.getItem('sidebar-skin') || 'sidebar-dark-dark');
	$('.main-sidebar').addClass(localStorage.getItem('sidebar-bg') || 'bg-dark');
	$("#swapModes").prop('checked', localStorage.getItem('dark-mode') == 1);
	$("#swapText").prop('checked', localStorage.getItem('text-sm-body') == 1);
	
	
let navbarSkin = localStorage.getItem('navbar-skin') || 'navbar-dark';
$('.main-header')
  .removeClass(navbar_dark_skins.concat(navbar_light_skins).join(' '))
  .addClass(navbarSkin)
  .removeClass('navbar-dark navbar-light')
  .addClass(navbar_dark_skins.includes(navbarSkin) ? 'navbar-dark' : 'navbar-light');

// Apply accent color
let accentx = localStorage.getItem('accent') || 'accent-primary';
$('body')
  .removeClass(accent_colors.join(' '))
  .addClass(accentx);

// Apply sidebar skin
let sidebarSkin = localStorage.getItem('sidebar-skin') || 'sidebar-dark-primary';
$('.main-sidebar')
  .removeClass(sidebar_colors.join(' '))
  .addClass(sidebarSkin);

// Apply sidebar background
let sidebarBg = localStorage.getItem('sidebar-bg') || 'bg-primary';
$('.main-sidebar')
  .removeClass(bg_colors.join(' '))
  .addClass(sidebarBg);
	
if (localStorage.getItem('text-sm-body') === '1') {
  $('body').addClass('text-sm');
}else{  $('body').removeClass('text-sm'); }

if (localStorage.getItem('text-sm-header') === '1') {
  $('.main-header').addClass('text-sm');
}else{  $('.main-header').removeClass('text-sm'); }

if (localStorage.getItem('text-sm-brand') === '1') {
  $('.brand-link').addClass('text-sm');
}else{  $('.brand-link').removeClass('text-sm'); }

if (localStorage.getItem('text-sm-sidebar') === '1') {
  $('.nav-sidebar').addClass('text-sm');
}else{  $('.nav-sidebar').removeClass('text-sm'); }

if (localStorage.getItem('text-sm-footer') === '1') {
  $('.main-footer').addClass('text-sm');
}else{  $('.main-footer').removeClass('text-sm'); }
	
}

  restoreSettings();

 settings.forEach(s => {
    const $checkbox = $('<input />', {
      type: 'checkbox',
      class: 'mr-1',
      checked: $(s.selector).hasClass(s.class),
    }).on('change', function () {
      updateClassSetting(s.key, s.selector, s.class, $(this).is(':checked'));
    });
    const $container = $('<div />', { class: 'mb-1' }).append($checkbox).append(s.textName);
    $('#layout-options').append($container);
  });

function buildDropdown(id, label, options, selected, onChange) {
  const select = $('<select class="form-control mb-2"></select>').attr('id', id);
  options.forEach(option => {
    select.append($('<option></option>').attr('value', option).text(option).prop('selected', option === selected));
  });
  select.on('change', function () {
    const value = $(this).val();
    onChange(value);
  });
  return $('<div class="form-group"></div>').append($('<label></label>').attr('for', id).text(label), select);
}
 

$('#reset-theme').on('click', function () {
ResetDefault()
});

function ResetDefault(){
	localStorage.setItem("accent", 'accent-secondary');
	localStorage.setItem("dark-mode", '1');
	localStorage.setItem("nav-compact", '0');
	localStorage.setItem("nav-flat", '1');
	localStorage.setItem("nav-legacy", '0');
	localStorage.setItem("navbar-fixed", '0');
	localStorage.setItem("navbar-skin", 'navbar-dark');
	localStorage.setItem("sidebar-bg", 'bg-dark');
	localStorage.setItem("sidebar-collapse", '0');
	localStorage.setItem("sidebar-skin", 'sidebar-dark-dark');
	localStorage.setItem("text-sm-body", '0');
	localStorage.setItem("text-sm-brand", '0');
	localStorage.setItem("text-sm-footer", '0');
	localStorage.setItem("text-sm-header", '0');
	localStorage.setItem("text-sm-sidebar", '0');
   restoreSettings();	
}


$('#swapModes').on('change', function () {
const swapMode = localStorage.getItem('dark-mode') == 1;
if(!swapMode){
	localStorage.setItem("dark-mode", '1');
} else {
	localStorage.setItem("dark-mode", '0');
}
 restoreSettings();
});

$('#swapText').on('change', function () {
const swapMode = localStorage.getItem('text-sm-body') == 1;
if(!swapMode){
	localStorage.setItem("text-sm-body", '1');
	localStorage.setItem("text-sm-brand", '1');
	localStorage.setItem("text-sm-footer", '1');
	localStorage.setItem("text-sm-header", '1');
	localStorage.setItem("text-sm-sidebar", '1');
} else {
	localStorage.setItem("text-sm-body", '0');
	localStorage.setItem("text-sm-brand", '0');
	localStorage.setItem("text-sm-footer", '0');
	localStorage.setItem("text-sm-header", '0');
	localStorage.setItem("text-sm-sidebar", '0');
}
restoreSettings();
});

function applyPreset(preset) {
  // presupunem că ai undeva în cod această variabilă ce indică modul întunecat
  const darkMode = localStorage.getItem("dark-mode") === "0";

  // adaptăm sidebarSkin în funcție de darkMode
  let sidebarSkinClass = preset.sidebarSkin;
  
  if (!darkMode) {
    // dacă nu e dark mode, înlocuim "sidebar-dark-" cu "sidebar-white-"
    sidebarSkinClass = sidebarSkinClass.replace("sidebar-dark-", "sidebar-white-");
  }

  localStorage.setItem("navbar-skin", preset.navbar);
  localStorage.setItem("sidebar-skin", sidebarSkinClass);
  localStorage.setItem("sidebar-bg", preset.sidebarBg);
  localStorage.setItem("accent", preset.accent);
  
  restoreSettings();
}
const themePresets = [
  { name: "Dark Blue", navbar: "navbar-primary", sidebarSkin: "sidebar-dark-primary", sidebarBg: "bg-primary", accent: "accent-light", primaryColor: "#007bff" },
   { name: "Dark Cyan", navbar: "navbar-cyan", sidebarSkin: "sidebar-dark-cyan", sidebarBg: "bg-cyan", accent: "accent-cyan", primaryColor: "#17a2b8" },
  { name: "Dark Red", navbar: "navbar-danger", sidebarSkin: "sidebar-dark-danger", sidebarBg: "bg-danger", accent: "accent-danger", primaryColor: "#dc3545" },
  { name: "Light Pink", navbar: "navbar-pink", sidebarSkin: "sidebar-dark-pink", sidebarBg: "bg-pink", accent: "accent-pink", primaryColor: "#e83e8c" },
  { name: "Dark Purple", navbar: "navbar-purple", sidebarSkin: "sidebar-dark-purple", sidebarBg: "bg-purple", accent: "accent-purple", primaryColor: "#6f42c1" },
  { name: "Light Indigo", navbar: "navbar-indigo", sidebarSkin: "sidebar-dark-indigo", sidebarBg: "bg-indigo", accent: "accent-indigo", primaryColor: "#6610f2" },
  
  { name: "Dark Orange", navbar: "navbar-orange", sidebarSkin: "sidebar-light-orange", sidebarBg: "bg-orange", accent: "accent-dark", primaryColor: "#fd7e14" },
  { name: "Light Yellow", navbar: "navbar-warning", sidebarSkin: "sidebar-dark-warning", sidebarBg: "bg-warning", accent: "accent-warning", primaryColor: "#ffc107" },
  { name: "Light Gray", navbar: "navbar-light", sidebarSkin: "sidebar-dark-light", sidebarBg: "bg-light", accent: "accent-light", primaryColor: "#f8f9fa" },
  { name: "Dark Gray", navbar: "navbar-gray", sidebarSkin: "sidebar-dark-gray-dark", sidebarBg: "bg-gray-dark", accent: "accent-gray-dark", primaryColor: "#343a40" },
 

];

window.restoreSettings = restoreSettings;
window.applyPreset = applyPreset;



const container = document.getElementById("theme-presets-container");
themePresets.forEach(preset => {
  const div = document.createElement("div");
  div.style.width = "24px";
  div.style.height = "24px";
  div.style.cursor = "pointer";
  div.style.backgroundColor = preset.primaryColor;  // folosește culoarea nouă
  div.title = preset.name;  // ca să vezi numele la hover
  div.onclick = () => applyPreset(preset);
  div.style.margin = "5px";
  container.appendChild(div);
});
});

