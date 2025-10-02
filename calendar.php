<?php
include_once("config.php");
$pageName = "Calendar & Întâlniri";
$pageId = 4;
include_once("WEB-INF/menu.php"); 
?>

<link rel="stylesheet" href="plugins/fullcalendar/main.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Acțiuni</h3>
            </div>
            <div class="card-body">
                <button class="btn btn-primary btn-block mb-2" id="btnNewEvent">
                    <i class="fas fa-plus"></i> Eveniment Nou
                </button>
                <button class="btn btn-info btn-block mb-2" id="btnSyncEmails">
                    <i class="fas fa-sync"></i> Sincronizează Emailuri
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filtre</h3>
            </div>
            <div class="card-body">
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
                        <i class="fas fa-envelope text-info"></i> Emailuri
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Eveniment Nou</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="eventForm">
                <div class="modal-body">
                    <input type="hidden" id="event_id" name="id">
                    
                    <div class="form-group">
                        <label>Tip Eveniment</label>
                        <select class="form-control" id="event_type" name="type" required>
                            <option value="todo">TODO</option>
                            <option value="meeting">Întâlnire</option>
                            <option value="deadline">Deadline</option>
                            <option value="reminder">Reminder</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Titlu *</label>
                        <input type="text" class="form-control" id="event_title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label>Descriere</label>
                        <textarea class="form-control" id="event_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Data Început *</label>
                            <input type="datetime-local" class="form-control" id="event_start" name="start" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Data Sfârșit</label>
                            <input type="datetime-local" class="form-control" id="event_end" name="end">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Legătură cu Email (opțional)</label>
                        <input type="number" class="form-control" id="event_email_id" name="email_id" placeholder="ID Email">
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="event_all_day" name="all_day">
                        <label class="custom-control-label" for="event_all_day">Toată ziua</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="btnDeleteEvent" style="display:none;">Șterge</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Închide</button>
                    <button type="submit" class="btn btn-primary">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteEventModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger py-2">
                <h6 class="modal-title text-white">Confirmare Ștergere</h6>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                Sigur doriți să ștergeți acest eveniment?
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Nu</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteEvent">Da, Șterge</button>
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

const typeColors = {
    todo: '#ffc107',
    meeting: '#007bff',
    deadline: '#dc3545',
    reminder: '#17a2b8',
    email: '#6c757d'
};

$(function() {
    initCalendar();
    loadEvents();
});

function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        initialView: 'dayGridMonth',
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        select: function(info) {
            $('#event_start').val(moment(info.start).format('YYYY-MM-DDTHH:mm'));
            $('#event_end').val(moment(info.end).format('YYYY-MM-DDTHH:mm'));
            $('#btnNewEvent').click();
        },
        eventClick: function(info) {
            if (info.event.extendedProps.source === 'email') {
                window.location.href = 'read_mail?id=' + info.event.extendedProps.email_id;
                return;
            }
            editEvent(info.event);
        },
        eventDrop: function(info) {
            updateEventDates(info.event);
        },
        eventResize: function(info) {
            updateEventDates(info.event);
        }
    });
    calendar.render();
}

function loadEvents() {
    $.post('api.php?f=get_calendar_events&user_id=<?= $user_id ?>', {}, function(resp) {
        if (resp.success) {
            allEvents = resp.data || [];
            filterAndDisplay();
        }
    }, 'json');
}

function filterAndDisplay() {
    const showTodo = $('#showTodo').is(':checked');
    const showMeetings = $('#showMeetings').is(':checked');
    const showEmails = $('#showEmails').is(':checked');

    const filtered = allEvents.filter(e => {
        if (e.type === 'todo' && !showTodo) return false;
        if (e.type === 'meeting' && !showMeetings) return false;
        if (e.source === 'email' && !showEmails) return false;
        return true;
    });

    calendar.removeAllEvents();
    calendar.addEventSource(filtered.map(e => ({
        id: e.id,
        title: e.title,
        start: e.start,
        end: e.end,
        allDay: e.all_day == 1,
        backgroundColor: typeColors[e.type] || typeColors.todo,
        extendedProps: e
    })));
}

$('.event-filter').on('change', filterAndDisplay);

$('#btnNewEvent').click(function() {
    $('#eventForm')[0].reset();
    $('#event_id').val('');
    $('#eventModalTitle').text('Eveniment Nou');
    $('#btnDeleteEvent').hide();
    $('#eventModal').modal('show');
});

function editEvent(event) {
    $('#event_id').val(event.id);
    $('#event_type').val(event.extendedProps.type);
    $('#event_title').val(event.title);
    $('#event_description').val(event.extendedProps.description || '');
    $('#event_start').val(moment(event.start).format('YYYY-MM-DDTHH:mm'));
    $('#event_end').val(event.end ? moment(event.end).format('YYYY-MM-DDTHH:mm') : '');
    $('#event_all_day').prop('checked', event.allDay);
    $('#event_email_id').val(event.extendedProps.email_id || '');
    $('#eventModalTitle').text('Editează Eveniment');
    $('#btnDeleteEvent').show();
    $('#eventModal').modal('show');
}

$('#eventForm').submit(function(e) {
    e.preventDefault();
    $.post('api.php?f=save_calendar_event&user_id=<?= $user_id ?>', $(this).serialize(), function(resp) {
        if (resp.success) {
            toastr.success('Eveniment salvat!');
            $('#eventModal').modal('hide');
            loadEvents();
        } else {
            toastr.error(resp.error || 'Eroare!');
        }
    }, 'json');
});

$('#btnDeleteEvent').click(function() {
    deleteEventId = $('#event_id').val();
    $('#eventModal').modal('hide');
    $('#deleteEventModal').modal('show');
});

$('#confirmDeleteEvent').click(function() {
    if (!deleteEventId) return;
    
    $.post('api.php?f=delete_calendar_event&user_id=<?= $user_id ?>', {id: deleteEventId}, function(resp) {
        if (resp.success) {
            toastr.success('Eveniment șters!');
            $('#deleteEventModal').modal('hide');
            loadEvents();
            deleteEventId = null;
        } else {
            toastr.error(resp.error || 'Eroare!');
        }
    }, 'json');
});

function updateEventDates(event) {
    $.post('api.php?f=update_event_dates&user_id=<?= $user_id ?>', {
        id: event.id,
        start: moment(event.start).format('YYYY-MM-DD HH:mm:ss'),
        end: event.end ? moment(event.end).format('YYYY-MM-DD HH:mm:ss') : null
    }, function(resp) {
        if (resp.success) {
            toastr.success('Eveniment actualizat!');
        }
    }, 'json');
}

$('#btnSyncEmails').click(function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizare...');
    
    $.post('api.php?f=sync_emails_to_calendar&user_id=<?= $user_id ?>', {}, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sincronizează Emailuri');
        if (resp.success) {
            toastr.success('Emailuri sincronizate!');
            loadEvents();
        } else {
            toastr.error(resp.error || 'Eroare!');
        }
    }, 'json');
});
</script>

<style>
#deleteEventModal.modal { z-index: 1065; }
</style>

<?php include_once("WEB-INF/footer.php"); ?>