<?php
include_once("config.php");
$pageName = "Calendar & Întâlniri";
$pageId = 4;
include_once("WEB-INF/menu.php"); 
?>

<link rel="stylesheet" href="plugins/fullcalendar/main.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cogs"></i> Acțiuni
                </h3>
            </div>
            <div class="card-body">
                <button class="btn btn-primary btn-block mb-2" id="btnNewEvent">
                    <i class="fas fa-plus"></i> Eveniment Nou
                </button>
                

                
                <div class="dropdown mb-2">
                    <button class="btn btn-success btn-block dropdown-toggle" type="button" id="dropdownImport" data-toggle="dropdown">
                        <i class="fas fa-file-import"></i> Import Calendar
                    </button>
                    <div class="dropdown-menu w-100">
                        <a class="dropdown-item" href="#" id="btnImportICS">
                            <i class="fas fa-calendar"></i> Import ICS File
                        </a>
                        <a class="dropdown-item" href="#" id="btnImportCSV">
                            <i class="fas fa-file-csv"></i> Import CSV File
                        </a>
                        <a class="dropdown-item" href="#" id="btnImportJSON">
                            <i class="fas fa-file-code"></i> Import JSON File
                        </a>
                    </div>
                </div>
                
                <div class="dropdown mb-2">
                    <button class="btn btn-warning btn-block dropdown-toggle" type="button" id="dropdownExport" data-toggle="dropdown">
                        <i class="fas fa-file-export"></i> Export Calendar
                    </button>
                    <div class="dropdown-menu w-100">
                        <a class="dropdown-item" href="#" id="btnExportICS">
                            <i class="fas fa-calendar"></i> Export to ICS
                        </a>
                        <a class="dropdown-item" href="#" id="btnExportCSV">
                            <i class="fas fa-file-csv"></i> Export to CSV
                        </a>
                        <a class="dropdown-item" href="#" id="btnExportJSON">
                            <i class="fas fa-file-code"></i> Export to JSON
                        </a>
                        <a class="dropdown-item" href="#" id="btnExportPDF">
                            <i class="fas fa-file-pdf"></i> Export to PDF
                        </a>
                    </div>
                </div>
                
                <button class="btn btn-secondary btn-block mb-2" id="btnBulkActions">
                    <i class="fas fa-tasks"></i> Acțiuni în Bloc
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter"></i> Filtre
                </h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Perioada:</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="filterStartDate">
                        <div class="input-group-append input-group-prepend">
                            <span class="input-group-text">-</span>
                        </div>
                        <input type="date" class="form-control" id="filterEndDate">
                    </div>
                </div>
                
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input event-filter" id="showTodo" checked>
                    <label class="custom-control-label" for="showTodo">
                        <i class="fas fa-tasks text-warning"></i> TODO
                    </label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input event-filter" id="showMeetings" checked>
                    <label class="custom-control-label" for="showMeetings">
                        <i class="fas fa-users text-primary"></i> Întâlniri
                    </label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input event-filter" id="showEmails" checked>
                    <label class="custom-control-label" for="showEmails">
                        <i class="fas fa-envelope text-secondary"></i> Emailuri
                    </label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input event-filter" id="showDeadlines" checked>
                    <label class="custom-control-label" for="showDeadlines">
                        <i class="fas fa-exclamation-triangle text-danger"></i> Deadline-uri
                    </label>
                </div>
                
                <button class="btn btn-sm btn-outline-secondary btn-block mt-2" id="btnClearFilters">
                    <i class="fas fa-times"></i> Șterge Filtre
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i> Statistici
                </h3>
            </div>
            <div class="card-body">
                <div class="info-box mb-2">
                    <span class="info-box-icon bg-info"><i class="fas fa-calendar-day"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Astăzi</span>
                        <span class="info-box-number" id="todayEvents">0</span>
                    </div>
                </div>
                <div class="info-box mb-2">
                    <span class="info-box-icon bg-warning"><i class="fas fa-calendar-week"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Săptămâna</span>
                        <span class="info-box-number" id="weekEvents">0</span>
                    </div>
                </div>
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-calendar-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total</span>
                        <span class="info-box-number" id="totalEvents">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar"></i> Calendar
                </h3>
                <div class="card-tools">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary" id="btnMonthView">Month</button>
                        <button class="btn btn-sm btn-outline-secondary" id="btnWeekView">Week</button>
                        <button class="btn btn-sm btn-outline-secondary" id="btnDayView">Day</button>
                    </div>
                    <button class="btn btn-sm btn-outline-primary ml-2" id="btnToday">
                        <i class="fas fa-calendar-day"></i> Astăzi
                    </button>
                    <div class="btn-group ml-2">
                        <button class="btn btn-sm btn-outline-secondary" id="btnPrev">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="btnNext">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="eventForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Eveniment Nou</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="event_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_type">Tip Eveniment</label>
                                <select class="form-control" id="event_type" name="type" required>
                                    <option value="">Selectează tipul</option>
                                    <option value="todo">TODO</option>
                                    <option value="meeting">Întâlnire</option>
                                    <option value="deadline">Deadline</option>
                                    <option value="reminder">Reminder</option>
                                    <option value="personal">Personal</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_priority">Prioritate</label>
                                <select class="form-control" id="event_priority" name="priority">
                                    <option value="low">Scăzută</option>
                                    <option value="medium" selected>Medie</option>
                                    <option value="high">Înaltă</option>
                                    <option value="urgent">Urgentă</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_title">Titlu</label>
                        <input type="text" class="form-control" id="event_title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_description">Descriere</label>
                        <textarea class="form-control" id="event_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_start_date">Data Început</label>
                                <input type="date" class="form-control" id="event_start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_start_time">Ora Început</label>
                                <input type="time" class="form-control" id="event_start_time" name="start_time">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_end_date">Data Sfârșit</label>
                                <input type="date" class="form-control" id="event_end_date" name="end_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_end_time">Ora Sfârșit</label>
                                <input type="time" class="form-control" id="event_end_time" name="end_time">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_location">Locație</label>
                                <input type="text" class="form-control" id="event_location" name="location">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_attendees">Participanți (email-uri separate prin virgulă)</label>
                                <input type="text" class="form-control" id="event_attendees" name="attendees">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="event_all_day" name="all_day">
                                <label class="custom-control-label" for="event_all_day">Toată ziua</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="event_recurring" name="recurring">
                                <label class="custom-control-label" for="event_recurring">Recurent</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" id="recurringOptions" style="display:none;">
                        <label for="event_recurrence">Tip Recurență</label>
                        <select class="form-control" id="event_recurrence" name="recurrence">
                            <option value="daily">Zilnic</option>
                            <option value="weekly">Săptămânal</option>
                            <option value="monthly">Lunar</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_email_id">Legătură cu Email (opțional)</label>
                        <input type="number" class="form-control" id="event_email_id" name="email_id" placeholder="ID Email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="btnDeleteEvent" style="display:none;">
                        <i class="fas fa-trash"></i> Șterge
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Închide
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvează
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalTitle">Import Calendar</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="importFile">Selectează fișierul</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="importFile" name="file">
                            <label class="custom-file-label" for="importFile">Alege fișierul...</label>
                        </div>
                        <small class="form-text text-muted">Formate acceptate: .ics, .csv, .json</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="importCalendar">Calendar destinație</label>
                        <select class="form-control" id="importCalendar" name="calendar">
                            <option value="main">Calendar Principal</option>
                            <option value="work">Calendar Serviciu</option>
                            <option value="personal">Calendar Personal</option>
                        </select>
                    </div>
                    
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="importOverwrite" name="overwrite">
                        <label class="custom-control-label" for="importOverwrite">
                            Suprascrie evenimente existente cu același ID
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Anulează
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmImport">
                    <i class="fas fa-file-import"></i> Importă
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalTitle">Export Calendar</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="form-group">
                        <label for="exportFormat">Format export</label>
                        <select class="form-control" id="exportFormat" name="format">
                            <option value="ics">ICS (iCalendar)</option>
                            <option value="csv">CSV (Excel)</option>
                            <option value="json">JSON</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exportPeriod">Perioada</label>
                        <select class="form-control" id="exportPeriod" name="period">
                            <option value="all">Toate evenimentele</option>
                            <option value="month">Luna curentă</option>
                            <option value="year">Anul curent</option>
                            <option value="custom">Perioadă personalizată</option>
                        </select>
                    </div>
                    
                    <div id="customPeriod" style="display:none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exportStartDate">Data început</label>
                                    <input type="date" class="form-control" id="exportStartDate" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exportEndDate">Data sfârșit</label>
                                    <input type="date" class="form-control" id="exportEndDate" name="end_date">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipuri evenimente de inclus:</label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input export-type" id="exportTodo" name="types[]" value="todo" checked>
                            <label class="custom-control-label" for="exportTodo">TODO</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input export-type" id="exportMeetings" name="types[]" value="meeting" checked>
                            <label class="custom-control-label" for="exportMeetings">Întâlniri</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input export-type" id="exportEmails" name="types[]" value="email" checked>
                            <label class="custom-control-label" for="exportEmails">Emailuri</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input export-type" id="exportDeadlines" name="types[]" value="deadline" checked>
                            <label class="custom-control-label" for="exportDeadlines">Deadline-uri</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Anulează
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmExport">
                    <i class="fas fa-file-export"></i> Exportă
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acțiuni în Bloc</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Selectează acțiunea:</label>
                    <select class="form-control" id="bulkAction">
                        <option value="">Selectează acțiunea</option>
                        <option value="delete">Șterge evenimente selectate</option>
                        <option value="change_type">Schimbă tipul evenimentelor</option>
                        <option value="change_priority">Schimbă prioritatea</option>
                        <option value="mark_completed">Marchează ca finalizate</option>
                        <option value="duplicate">Duplică evenimente</option>
                    </select>
                </div>
                
                <div id="bulkOptions" style="display:none;">
                    <!-- Options will be populated based on selected action -->
                </div>
                
                <div class="form-group">
                    <label>Perioada pentru selecție:</label>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="date" class="form-control" id="bulkStartDate">
                        </div>
                        <div class="col-md-6">
                            <input type="date" class="form-control" id="bulkEndDate">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Anulează
                </button>
                <button type="button" class="btn btn-warning" id="btnConfirmBulk">
                    <i class="fas fa-tasks"></i> Execută
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteEventModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger py-2">
                <h6 class="modal-title text-white">
                    <i class="fas fa-exclamation-triangle"></i> Confirmare Ștergere
                </h6>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Sigur doriți să ștergeți acest eveniment?</p>
                <div id="deleteEventDetails"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times"></i> Nu
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteEvent">
                    <i class="fas fa-trash"></i> Da, Șterge
                </button>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/fullcalendar/main.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
let calendar;
let allEvents = [];
let deleteEventId = null;
let currentImportType = '';
let currentExportType = '';

const typeColors = {
    todo: '#ffc107',
    meeting: '#007bff',
    deadline: '#dc3545',
    reminder: '#17a2b8',
    email: '#6c757d',
    personal: '#28a745'
};

const priorityColors = {
    low: '#6c757d',
    medium: '#17a2b8',
    high: '#ffc107',
    urgent: '#dc3545'
};

$(function() {
    initCalendar();
    loadEvents();
    setupEventHandlers();
    updateStatistics();
    updateViewButtons('month');
});

function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: '',
            center: 'title',
            right: ''
        },
        initialView: 'dayGridMonth',
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        height: 'auto',
        
        select: function(info) {
            const startDate = moment(info.start).format('YYYY-MM-DD');
            const startTime = moment(info.start).format('HH:mm');
            const endDate = moment(info.end).format('YYYY-MM-DD');
            const endTime = moment(info.end).format('HH:mm');
            
            $('#event_start_date').val(startDate);
            $('#event_start_time').val(startTime);
            $('#event_end_date').val(endDate);
            $('#event_end_time').val(endTime);
            $('#btnNewEvent').click();
        },
        
        eventClick: function(info) {
            editEvent(info.event);
        },
        
        eventDrop: function(info) {
            updateEventDates(info.event);
        },
        
        eventResize: function(info) {
            updateEventDates(info.event);
        },
        
        eventMouseEnter: function(info) {
            showEventTooltip(info);
        }
    });
    
    calendar.render();
}

function setupEventHandlers() {
    // Navigation buttons
    $('#btnToday').click(() => calendar.today());
    $('#btnPrev').click(() => calendar.prev());
    $('#btnNext').click(() => calendar.next());
    
    // View buttons
    $('#btnMonthView').click(() => {
        calendar.changeView('dayGridMonth');
        updateViewButtons('month');
    });
    $('#btnWeekView').click(() => {
        calendar.changeView('timeGridWeek');
        updateViewButtons('week');
    });
    $('#btnDayView').click(() => {
        calendar.changeView('timeGridDay');
        updateViewButtons('day');
    });
    
    // Event filters
    $('.event-filter').on('change', filterAndDisplay);
    $('#filterStartDate, #filterEndDate').on('change', filterAndDisplay);
    $('#btnClearFilters').click(clearFilters);
    
    // Import/Export handlers
    $('#btnImportICS').click(() => openImportModal('ics'));
    $('#btnImportCSV').click(() => openImportModal('csv'));
    $('#btnImportJSON').click(() => openImportModal('json'));
    
    $('#btnExportICS').click(() => openExportModal('ics'));
    $('#btnExportCSV').click(() => openExportModal('csv'));
    $('#btnExportJSON').click(() => openExportModal('json'));
    $('#btnExportPDF').click(() => openExportModal('pdf'));
    
    // Form handlers
    $('#eventForm').submit(saveEvent);
    $('#btnNewEvent').click(newEvent);
    $('#btnDeleteEvent').click(deleteEvent);
    $('#confirmDeleteEvent').click(confirmDeleteEvent);
    
    // Import/Export form handlers
    $('#btnConfirmImport').click(confirmImport);
    $('#btnConfirmExport').click(confirmExport);
    
    // Bulk actions
    $('#btnBulkActions').click(() => $('#bulkModal').modal('show'));
    $('#bulkAction').change(handleBulkActionChange);
    $('#btnConfirmBulk').click(confirmBulkAction);
    
    // Other handlers
    $('#event_recurring').change(function() {
        $('#recurringOptions').toggle(this.checked);
    });
    
    $('#exportPeriod').change(function() {
        $('#customPeriod').toggle(this.value === 'custom');
    });
    
    $('#importFile').change(function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
    
    $('#event_all_day').change(function() {
        const timeFields = $('#event_start_time, #event_end_time');
        if (this.checked) {
            timeFields.prop('disabled', true).val('');
        } else {
            timeFields.prop('disabled', false);
        }
    });
}
// Fixed JavaScript functions for calendar.php

function confirmImport() {
    const fileInput = document.getElementById('importFile');
    const file = fileInput.files[0];
    
    if (!file) {
        toastr.error('Vă rugăm să selectați un fișier!');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('calendar', $('#importCalendar').val());
    formData.append('overwrite', $('#importOverwrite').is(':checked') ? '1' : '0');
    formData.append('type', currentImportType);
    
    const btn = $('#btnConfirmImport');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Se importă...');
    
    $.ajax({
        url: 'api.php?f=import_calendar&user_id=<?= $user_id ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            btn.prop('disabled', false).html('<i class="fas fa-file-import"></i> Importă');
            if (resp.success) {
                toastr.success(`Import finalizat! ${resp.imported || 0} evenimente importate.`);
                $('#importModal').modal('hide');
                $('#importFile').val('');
                $('.custom-file-label').html('Alege fișier...');
                loadEvents();
            } else {
                toastr.error(resp.error || 'Eroare la importarea evenimentelor!');
            }
        },
        error: function(xhr, status, error) {
            btn.prop('disabled', false).html('<i class="fas fa-file-import"></i> Importă');
            toastr.error('Eroare de comunicare cu serverul: ' + error);
        }
    });
}

function confirmExport() {
    const format = $('#exportFormat').val();
    const period = $('#exportPeriod').val();
    const startDate = $('#exportStartDate').val();
    const endDate = $('#exportEndDate').val();
    
    // Validate custom period dates
    if (period === 'custom') {
        if (!startDate || !endDate) {
            toastr.error('Vă rugăm să selectați perioada pentru export!');
            return;
        }
        if (startDate > endDate) {
            toastr.error('Data de start trebuie să fie înainte de data de final!');
            return;
        }
    }
    
    const types = [];
    $('.export-type-filter:checked').each(function() {
        types.push($(this).val());
    });
    
    const btn = $('#btnConfirmExport');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Se exportă...');
    
    $.ajax({
        url: 'api.php?f=export_calendar&user_id=<?= $user_id ?>',
        type: 'POST',
        data: {
            format: format,
            period: period,
            start_date: startDate,
            end_date: endDate,
            types: types
        },
        dataType: 'json',
        success: function(resp) {
            btn.prop('disabled', false).html('<i class="fas fa-file-export"></i> Exportă');
            if (resp.success) {
                toastr.success(`Export finalizat! ${resp.event_count || 0} evenimente exportate.`);
                $('#exportModal').modal('hide');
                
                // Trigger download
                window.location.href = resp.file_url;
            } else {
                toastr.error(resp.error || 'Eroare la exportarea evenimentelor!');
            }
        },
        error: function(xhr, status, error) {
            btn.prop('disabled', false).html('<i class="fas fa-file-export"></i> Exportă');
            toastr.error('Eroare de comunicare cu serverul: ' + error);
        }
    });
}

function loadEvents() {
    $.post('api.php?f=get_calendar_events&user_id=<?= $user_id ?>', {}, function(resp) {
        if (resp.success) {
            allEvents = resp.data || [];
            filterAndDisplay();
            updateStatistics();
        } else {
            toastr.error(resp.error || 'Eroare la încărcarea evenimentelor!');
        }
    }, 'json').fail(function(xhr, status, error) {
        toastr.error('Eroare de comunicare cu serverul: ' + error);
    });
}

function saveEvent(e) {
    e.preventDefault();
    
    const startDate = $('#event_start_date').val();
    const startTime = $('#event_start_time').val();
    const endDate = $('#event_end_date').val();
    const endTime = $('#event_end_time').val();
    const allDay = $('#event_all_day').is(':checked');
    
    // Validate required fields
    if (!$('#event_title').val().trim()) {
        toastr.error('Titlul evenimentului este obligatoriu!');
        return;
    }
    
    if (!startDate) {
        toastr.error('Data de start este obligatorie!');
        return;
    }
    
    let start = startDate;
    let end = endDate;
    
    if (!allDay && startTime) {
        start += ' ' + startTime + ':00';
    } else {
        start += ' 00:00:00';
    }
    
    if (endDate) {
        if (!allDay && endTime) {
            end += ' ' + endTime + ':00';
        } else {
            end += ' 23:59:59';
        }
    }
    
    // Validate that end is after start
    if (end && new Date(end) < new Date(start)) {
        toastr.error('Data de final trebuie să fie după data de start!');
        return;
    }
    
    const formData = new FormData(this);
    formData.set('start', start);
    formData.set('end', end || '');
    
    // Disable submit button
    const submitBtn = $('#eventForm button[type="submit"]');
    submitBtn.prop('disabled', true);
    
    $.ajax({
        url: 'api.php?f=save_calendar_event&user_id=<?= $user_id ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            submitBtn.prop('disabled', false);
            if (resp.success) {
                toastr.success(resp.message || 'Eveniment salvat cu succes!');
                $('#eventModal').modal('hide');
                loadEvents();
            } else {
                toastr.error(resp.error || 'Eroare la salvarea evenimentului!');
            }
        },
        error: function(xhr, status, error) {
            submitBtn.prop('disabled', false);
            toastr.error('Eroare de comunicare cu serverul: ' + error);
        }
    });
}

function deleteEvent() {
    const eventId = $('#event_id').val();
    const event = calendar.getEventById(eventId);
    
    if (event) {
        $('#deleteEventDetails').html(`
            <strong>Eveniment:</strong> ${event.title}<br>
            <strong>Data:</strong> ${moment(event.start).format('DD.MM.YYYY HH:mm')}<br>
            <strong>Tip:</strong> ${event.extendedProps.type || 'N/A'}
        `);
    }
    
    deleteEventId = eventId;
    $('#eventModal').modal('hide');
    $('#deleteEventModal').modal('show');
}

function confirmDeleteEvent() {
    if (!deleteEventId) return;
    
    const btn = $('#confirmDeleteEvent');
    btn.prop('disabled', true);
    
    $.post('api.php?f=delete_calendar_event&user_id=<?= $user_id ?>', 
        {id: deleteEventId}, 
        function(resp) {
            btn.prop('disabled', false);
            if (resp.success) {
                toastr.success('Eveniment șters cu succes!');
                $('#deleteEventModal').modal('hide');
                loadEvents();
                deleteEventId = null;
            } else {
                toastr.error(resp.error || 'Eroare la ștergerea evenimentului!');
            }
        }, 'json'
    ).fail(function(xhr, status, error) {
        btn.prop('disabled', false);
        toastr.error('Eroare de comunicare cu serverul: ' + error);
    });
}

function updateEventDates(event) {
    $.post('api.php?f=update_event_dates&user_id=<?= $user_id ?>', {
        id: event.id,
        start: moment(event.start).format('YYYY-MM-DD HH:mm:ss'),
        end: event.end ? moment(event.end).format('YYYY-MM-DD HH:mm:ss') : null
    }, function(resp) {
        if (resp.success) {
            toastr.success('Eveniment actualizat!');
            loadEvents();
        } else {
            toastr.error(resp.error || 'Eroare la actualizarea evenimentului!');
            calendar.refetchEvents(); // Revert changes
        }
    }, 'json').fail(function(xhr, status, error) {
        toastr.error('Eroare de comunicare cu serverul: ' + error);
        calendar.refetchEvents(); // Revert changes
    });
}

function filterAndDisplay() {
    const showTodo = $('#showTodo').is(':checked');
    const showMeetings = $('#showMeetings').is(':checked');
    const showDeadlines = $('#showDeadlines').is(':checked');
    const startDate = $('#filterStartDate').val();
    const endDate = $('#filterEndDate').val();

    const filtered = allEvents.filter(e => {
        // Type filter
        if (e.type === 'todo' && !showTodo) return false;
        if (e.type === 'meeting' && !showMeetings) return false;
        if (e.type === 'deadline' && !showDeadlines) return false;
        
        // Date filter
        if (startDate && moment(e.start).isBefore(startDate, 'day')) return false;
        if (endDate && moment(e.start).isAfter(endDate, 'day')) return false;
        
        return true;
    });

    calendar.removeAllEvents();
    calendar.addEventSource(filtered.map(e => ({
        id: e.id,
        title: e.title,
        start: e.start,
        end: e.end,
        allDay: e.all_day == 1,
        backgroundColor: getEventColor(e),
        borderColor: getPriorityColor(e.priority),
        textColor: '#fff',
        extendedProps: e
    })));
}

function getEventColor(event) {
    return typeColors[event.type] || typeColors.todo;
}

function getPriorityColor(priority) {
    return priorityColors[priority] || priorityColors.medium;
}

function clearFilters() {
    $('.event-filter').prop('checked', true);
    $('#filterStartDate, #filterEndDate').val('');
    filterAndDisplay();
}

function updateStatistics() {
    const today = moment().format('YYYY-MM-DD');
    const weekStart = moment().startOf('week').format('YYYY-MM-DD');
    const weekEnd = moment().endOf('week').format('YYYY-MM-DD');
    
    const todayEvents = allEvents.filter(e => 
        moment(e.start).format('YYYY-MM-DD') === today
    ).length;
    
    const weekEvents = allEvents.filter(e => 
        moment(e.start).isBetween(weekStart, weekEnd, 'day', '[]')
    ).length;
    
    $('#todayEvents').text(todayEvents);
    $('#weekEvents').text(weekEvents);
    $('#totalEvents').text(allEvents.length);
}

function newEvent() {
    $('#eventForm')[0].reset();
    $('#event_id').val('');
    $('#eventModalTitle').text('Eveniment Nou');
    $('#btnDeleteEvent').hide();
    $('#recurringOptions').hide();
    $('#event_recurring').prop('checked', false);
    $('#eventModal').modal('show');
}

function editEvent(event) {
    $('#event_id').val(event.id);
    $('#event_type').val(event.extendedProps.type);
    $('#event_title').val(event.title);
    $('#event_description').val(event.extendedProps.description || '');
    
    const startDate = moment(event.start).format('YYYY-MM-DD');
    const startTime = moment(event.start).format('HH:mm');
    $('#event_start_date').val(startDate);
    $('#event_start_time').val(event.allDay ? '' : startTime);
    
    if (event.end) {
        const endDate = moment(event.end).format('YYYY-MM-DD');
        const endTime = moment(event.end).format('HH:mm');
        $('#event_end_date').val(endDate);
        $('#event_end_time').val(event.allDay ? '' : endTime);
    } else {
        $('#event_end_date').val('');
        $('#event_end_time').val('');
    }
    
    $('#event_all_day').prop('checked', event.allDay);
    $('#event_location').val(event.extendedProps.location || '');
    $('#event_attendees').val(event.extendedProps.attendees || '');
    $('#event_priority').val(event.extendedProps.priority || 'medium');
    $('#event_recurring').prop('checked', event.extendedProps.recurring || false);
    $('#event_recurrence').val(event.extendedProps.recurrence || 'weekly');
    $('#event_email_id').val(event.extendedProps.email_id || '');
    
    $('#eventModalTitle').text('Editează Eveniment');
    $('#btnDeleteEvent').show();
    
    if (event.extendedProps.recurring) {
        $('#recurringOptions').show();
    }
    
    if (event.allDay) {
        $('#event_start_time, #event_end_time').prop('disabled', true);
    }
    
    $('#eventModal').modal('show');
}





function updateViewButtons(activeView) {
    $('#btnMonthView, #btnWeekView, #btnDayView').removeClass('btn-primary').addClass('btn-outline-secondary');
    
    switch(activeView) {
        case 'month':
            $('#btnMonthView').removeClass('btn-outline-secondary').addClass('btn-primary');
            break;
        case 'week':
            $('#btnWeekView').removeClass('btn-outline-secondary').addClass('btn-primary');
            break;
        case 'day':
            $('#btnDayView').removeClass('btn-outline-secondary').addClass('btn-primary');
            break;
    }
}

// Import/Export Functions
function openImportModal(type) {
    currentImportType = type;
    $('#importModalTitle').text(`Import ${type.toUpperCase()}`);
    $('#importFile').attr('accept', getFileAccept(type));
    $('#importModal').modal('show');
}

function openExportModal(type) {
    currentExportType = type;
    $('#exportModalTitle').text(`Export ${type.toUpperCase()}`);
    $('#exportFormat').val(type);
    $('#exportModal').modal('show');
}

function getFileAccept(type) {
    switch(type) {
        case 'ics': return '.ics';
        case 'csv': return '.csv';
        case 'json': return '.json';
        default: return '*';
    }
}

// Bulk Actions
function handleBulkActionChange() {
    const action = $('#bulkAction').val();
    const optionsDiv = $('#bulkOptions');
    
    optionsDiv.empty().hide();
    
    if (action === 'change_type') {
        optionsDiv.html(`
            <div class="form-group">
                <label>Noul tip:</label>
                <select class="form-control" id="newType">
                    <option value="todo">TODO</option>
                    <option value="meeting">Întâlnire</option>
                    <option value="deadline">Deadline</option>
                    <option value="reminder">Reminder</option>
                    <option value="personal">Personal</option>
                </select>
            </div>
        `).show();
    } else if (action === 'change_priority') {
        optionsDiv.html(`
            <div class="form-group">
                <label>Noua prioritate:</label>
                <select class="form-control" id="newPriority">
                    <option value="low">Scăzută</option>
                    <option value="medium">Medie</option>
                    <option value="high">Înaltă</option>
                    <option value="urgent">Urgentă</option>
                </select>
            </div>
        `).show();
    }
}

function confirmBulkAction() {
    const action = $('#bulkAction').val();
    if (!action) {
        toastr.error('Vă rugăm să selectați o acțiune!');
        return;
    }
    
    const data = {
        action: action,
        start_date: $('#bulkStartDate').val(),
        end_date: $('#bulkEndDate').val()
    };
    
    if (action === 'change_type') {
        data.new_type = $('#newType').val();
    } else if (action === 'change_priority') {
        data.new_priority = $('#newPriority').val();
    }
    
    const btn = $('#btnConfirmBulk');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Se execută...');
    
    $.post('api.php?f=bulk_actions&user_id=<?= $user_id ?>', data, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-tasks"></i> Execută');
        if (resp.success) {
            toastr.success(`Acțiune executată cu succes! ${resp.affected || 0} evenimente modificate.`);
            $('#bulkModal').modal('hide');
            loadEvents();
        } else {
            toastr.error(resp.error || 'Eroare la executarea acțiunii!');
        }
    }, 'json');
}

function showEventTooltip(info) {
    const event = info.event;
    const tooltip = `
        <div class="event-tooltip">
            <strong>${event.title}</strong><br>
            <i class="fas fa-clock"></i> ${moment(event.start).format('DD.MM.YYYY HH:mm')}<br>
            <i class="fas fa-tag"></i> ${event.extendedProps.type}<br>
            ${event.extendedProps.location ? `<i class="fas fa-map-marker-alt"></i> ${event.extendedProps.location}<br>` : ''}
            ${event.extendedProps.description ? `<i class="fas fa-info"></i> ${event.extendedProps.description}` : ''}
        </div>
    `;
    
    // You can implement a proper tooltip library here
    // For now, we'll use the browser's default title
    info.el.setAttribute('title', event.title + '\n' + 
        moment(event.start).format('DD.MM.YYYY HH:mm') + 
        (event.extendedProps.description ? '\n' + event.extendedProps.description : ''));
}
</script>

<style>
.event-tooltip {
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 8px;
    border-radius: 4px;
    font-size: 12px;
    max-width: 200px;
}

.info-box {
    display: flex;
    align-items: center;
}

.info-box-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-right: 10px;
}

.info-box-content {
    flex: 1;
}

.info-box-text {
    font-size: 12px;
    text-transform: uppercase;
    font-weight: bold;
    color: #666;
}

.info-box-number {
    font-size: 18px;
    font-weight: bold;
}

.fc-event {
    border-width: 2px !important;
}

#deleteEventModal.modal { 
    z-index: 1065; 
}

.custom-file-label::after {
    content: "Browse";
}

.dropdown-menu {
    min-width: 100%;
}

.card-tools .btn-group {
    margin-left: 5px;
}
</style>

<?php include_once("WEB-INF/footer.php"); ?>