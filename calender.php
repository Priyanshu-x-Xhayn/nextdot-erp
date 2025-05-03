<?php 
include 'calender_api.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event & Task Calendar</title>

    <!-- CSS Dependencies - Cleaned up -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="./src/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css">
    
    <style>
        /* Calendar Container */
        .calendar-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        /* Event Modal */
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #072e63 0%, #051e42 100%);
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 15px 20px;
        }
        
        .modal-header .btn-close {
            color: white;
            opacity: 0.8;
        }
        
        .form-label {
            font-weight: 600;
            color: #072e63;
            margin-bottom: 5px;
        }
        
        /* Custom button styling */
        .add-event-btn {
            background: linear-gradient(135deg, #072e63 0%, #051e42 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .add-event-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, #051e42 0%, #072e63 100%);
            color: white;
        }
        
        /* Event Options Tab Styling */
        .nav-tabs .nav-link {
            color: #072e63;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 15px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link.active {
            color: #072e63;
            border-bottom: 3px solid #072e63;
            background-color: transparent;
        }
        
        .nav-tabs .nav-link:hover:not(.active) {
            border-bottom: 3px solid rgba(7, 46, 99, 0.3);
        }
        
        /* Color picker container */
        .color-picker-container {
            margin-top: 10px;
            display: flex;
            align-items: center;
        }
        
        .color-picker-wrapper {
            margin-left: 10px;
        }
        
        /* Tooltip styling */
        .fc-event {
            cursor: pointer;
            position: relative;
        }
        
        /* Legend styling */
        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
            margin-right: 5px;
        }
        
        .legend-text {
            font-size: 0.85rem;
            color: #555;
        }
        
        /* Filters styling */
        .calendar-filters {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            margin-bottom: 10px;
        }
        
        .filter-label {
            font-weight: 600;
            color: #072e63;
            margin-bottom: 5px;
            display: block;
        }
        
        /* Priority badge styling */
        .priority-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-right: 5px;
        }
        
        .priority-critical {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .priority-high {
            background-color: rgba(253, 126, 20, 0.1);
            color: #fd7e14;
        }
        
        .priority-medium {
            background-color: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }
        
        .priority-low {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        /* View buttons styling */
        .fc-button-group {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .fc .fc-button-primary {
            background-color: #fff;
            color: #072e63;
            border-color: #e0e0e0;
        }
        
        .fc .fc-button-primary:not(:disabled).fc-button-active, 
        .fc .fc-button-primary:not(:disabled):active {
            background-color: #072e63;
            color: #fff;
            border-color: #072e63;
        }
        
        .fc .fc-button-primary:hover {
            background-color: #f8f9fa;
            color: #072e63;
            border-color: #072e63;
        }
        
        /* Task styling */
        .task-event {
            border-left: 4px solid #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
            color: #333;
            font-weight: 500;
        }
        
        .task-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-right: 5px;
            background-color: #6c757d;
            color: white;
        }
        
        /* Task details in modal */
        .task-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .task-details-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .task-details-label {
            font-weight: 600;
            width: 120px;
            color: #555;
        }
        
        .task-details-value {
            flex: 1;
        }
        
        .task-action-btn {
            margin-top: 15px;
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 10px;
            }
            
            .fc .fc-toolbar-title {
                font-size: 1.2rem;
                text-align: center;
            }
            
            .calendar-filters .row > div {
                margin-bottom: 10px;
            }
            
            .fc-header-toolbar .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
                margin-bottom: 10px;
            }
            
            .fc-view-harness {
                height: auto !important;
                min-height: 500px;
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include './header.php'; ?>
        <div class="content-wrapper p-3">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <h2 class="page-title">Event & Task Calendar</h2>
                    <div>
                        <button class="btn add-event-btn me-2" onclick="window.location.href='daily_tasks.php'">
                            <i class="fas fa-tasks me-2"></i>Manage Tasks
                        </button>
                        <button class="btn add-event-btn" data-bs-toggle="modal" data-bs-target="#eventModal">
                            <i class="fas fa-plus me-2"></i>Add New Event
                        </button>
                    </div>
                </div>
                
                <!-- Filters Section - Only visible for Admin and Manager roles -->
                <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Team Lead'): ?>
                <div class="calendar-filters mb-4">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="filter-group">
                                <label class="filter-label">Event Type</label>
                                <select id="type-filter" class="form-select">
                                    <option value="all">All Types</option>
                                    <option value="event">Events Only</option>
                                    <option value="task">Tasks Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="filter-group">
                                <label class="filter-label">Category</label>
                                <select id="category-filter" class="form-select">
                                    <option value="all">All Categories</option>
                                    <option value="meeting">Meetings</option>
                                    <option value="deadline">Deadlines</option>
                                    <option value="task">Tasks</option>
                                    <option value="holiday">Holidays</option>
                                    <option value="general">  <option value="general">General</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="filter-group">
                                <label class="filter-label">Priority</label>
                                <select id="priority-filter" class="form-select">
                                    <option value="all">All Priorities</option>
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="filter-group">
                                <label class="filter-label">Status</label>
                                <select id="status-filter" class="form-select">
                                    <option value="all">All Statuses</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="canceled">Canceled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="filter-group">
                                <label class="filter-label">Department</label>
                                <select id="department-filter" class="form-select">
                                    <option value="all">All Departments</option>
                                    <?php
                                    // Fetch departments from the database
                                    $departments = $conn->query("SELECT * FROM departments ORDER BY department_name");
                                    while ($row = $departments->fetch_assoc()) {
                                        echo "<option value='{$row['department_name']}'>{$row['department_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Legend -->
                <div class="calendar-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #dc3545;"></div>
                        <span class="legend-text">Critical/High Priority</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #fd7e14;"></div>
                        <span class="legend-text">Medium Priority</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #28a745;"></div>
                        <span class="legend-text">Low Priority</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-text">🔒 Private Event</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-text">👥 Department Event</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-text">🌐 Public Event</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-text">📋 Task</span>
                    </div>
                </div>
                
                <!-- Calendar Container -->
                <div class="calendar-container">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Event Modal -->
        <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i><span id="modalTitle">Add New Event</span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs mb-4" id="eventTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true">Basic Info</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-info" type="button" role="tab" aria-controls="details-info" aria-selected="false">Details</button>
                            </li>
                        </ul>
                        
                        <form id="eventForm">
                            <input type="hidden" id="event_id"> 
                            <input type="hidden" id="action">
                            
                            <div class="tab-content" id="eventTabsContent">
                                <!-- Basic Info Tab -->
                                <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-tab">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Event Title</label>
                                        <input type="text" class="form-control" id="title" placeholder="Enter event title" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date & Time</label>
                                        <input type="datetime-local" class="form-control" id="start_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date & Time</label>
                                        <input type="datetime-local" class="form-control" id="end_date">
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" rows="3" placeholder="Enter event details"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Details Tab -->
                                <div class="tab-pane fade" id="details-info" role="tabpanel" aria-labelledby="details-tab">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="visibility" class="form-label">Visibility</label>
                                                <select class="form-select" id="visibility">
                                                    <option value="private">Private (Only You)</option>
                                                    <option value="department">Department</option>
                                                    <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Team Lead'): ?>
                                                    <option value="public">Public (Everyone)</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="category" class="form-label">Category</label>
                                                <select class="form-select" id="category">
                                                    <option value="general">General</option>
                                                    <option value="meeting">Meeting</option>
                                                    <option value="deadline">Deadline</option>
                                                    <option value="task">Task</option>
                                                    <option value="holiday">Holiday</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="priority" class="form-label">Priority</label>
                                                <select class="form-select" id="priority">
                                                    <option value="low">Low</option>
                                                    <option value="medium" selected>Medium</option>
                                                    <option value="high">High</option>
                                                    <option value="critical">Critical</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status">
                                                    <option value="upcoming" selected>Upcoming</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="completed">Completed</option>
                                                    <option value="canceled">Canceled</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" placeholder="Enter event location">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Event Color</label>
                                        <div class="color-picker-container">
                                            <input type="text" id="color" class="form-control" value="#3788d8" readonly>
                                            <div class="color-picker-wrapper">
                                                <div id="color-picker"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-danger" id="deleteEvent">
                                    <i class="fas fa-trash-alt me-2"></i>Delete
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Save Event
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Event Details Modal -->
        <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="event-detail-title">Event Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="event-detail-content">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div id="event-detail-priority" class="mb-2"></div>
                                    <h5 id="event-detail-time" class="text-muted"></h5>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Category:</strong> <span id="event-detail-category"></span></p>
                                    <p><strong>Visibility:</strong> <span id="event-detail-visibility"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> <span id="event-detail-status"></span></p>
                                    <p><strong>Location:</strong> <span id="event-detail-location"></span></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <p><strong>Department:</strong> <span id="event-detail-department"></span></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <h6>Description:</h6>
                                    <p id="event-detail-description" class="border-top pt-2"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="editEventButton">
                            <i class="fas fa-edit me-2"></i>Edit Event
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Task Details Modal -->
        <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="task-detail-title">Task Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="task-detail-content">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div id="task-detail-priority" class="mb-2"></div>
                                </div>
                            </div>
                            
                            <div class="task-details">
                                <div class="task-details-row">
                                    <div class="task-details-label">Project:</div>
                                    <div class="task-details-value" id="task-detail-project"></div>
                                </div>
                                <div class="task-details-row">
                                    <div class="task-details-label">Assignee:</div>
                                    <div class="task-details-value" id="task-detail-assignee"></div>
                                </div>
                                <div class="task-details-row">
                                    <div class="task-details-label">Department:</div>
                                    <div class="task-details-value" id="task-detail-department"></div>
                                </div>
                                <div class="task-details-row">
                                    <div class="task-details-label">Start Date:</div>
                                    <div class="task-details-value" id="task-detail-start-date"></div>
                                </div>
                                <div class="task-details-row">
                                    <div class="task-details-label">Due Date:</div>
                                    <div class="task-details-value" id="task-detail-due-date"></div>
                                </div>
                                <div class="task-details-row">
                                    <div class="task-details-label">Status:</div>
                                    <div class="task-details-value" id="task-detail-status"></div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <h6>Description:</h6>
                                <p id="task-detail-description" class="border-top pt-2"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" id="completeTaskButton">
                            <i class="fas fa-check me-2"></i>Mark as Completed
                        </button>
                        <button type="button" class="btn btn-primary" id="editTaskButton">
                            <i class="fas fa-edit me-2"></i>Edit Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JS Dependencies - Cleaned up and properly ordered -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./src/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function () {
        // Initialize color picker
        const pickr = Pickr.create({
            el: '#color-picker',
            theme: 'classic',
            default: '#3788d8',
            swatches: [
                '#3788d8', // Default blue
                '#dc3545', // Critical (red)
                '#fd7e14', // High (orange)
                '#007bff', // Medium (blue)
                '#28a745', // Low (green)
                '#6f42c1', // Purple
                '#fd7e14', // Orange
                '#20c997', // Teal
                '#6c757d'  // Gray
            ],
            components: {
                preview: true,
                opacity: true,
                hue: true,
                interaction: {
                    hex: true,
                    input: true,
                    save: true
                }
            }
        });

        // Add the missing event handler
        pickr.on('save', (color) => {
            const hexColor = color.toHEXA().toString();
            $('#color').val(hexColor);
            pickr.hide();
        });

        // Initialize the calendar
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            events: 'calender_api.php?fetch=true',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: true
            },
            selectable: true,
            editable: true,
            height: 'auto',
            themeSystem: 'bootstrap',
            
            // Responsive settings
            windowResize: function(view) {
                if (window.innerWidth < 768) {
                    calendar.changeView('listWeek');
                } else {
                    calendar.changeView('dayGridMonth');
                }
            },
            
            dateClick: function(info) {
                resetForm();
                $('#event_id').val("");
                
                // Format the date correctly
                let clickedDate = info.date;
                let formattedDate = clickedDate.toISOString().slice(0, 16);
                
                $('#start_date').val(formattedDate);
                
                // Set end date 1 hour later by default
                let endDate = new Date(clickedDate);
                endDate.setHours(endDate.getHours() + 1);
                $('#end_date').val(endDate.toISOString().slice(0, 16));
                
                $('#action').val("add");
                $('#modalTitle').text("Add New Event");
                $('#eventModal').modal('show');
                $('#deleteEvent').hide(); // Hide delete button for new events
            },
            eventClick: function(info) {
                // Check if the event is a task
                if (info.event.extendedProps.type === 'task') {
                    // Show task details in a modal
                    showTaskDetails(info.event);
                } else {
                    // Show event details in a modal
                    showEventDetails(info.event);
                }
            },
            eventDrop: function(info) {
                // Check if the event is a task
                if (info.event.extendedProps.type === 'task') {
                    // Prevent dragging tasks
                    Swal.fire({
                        title: 'Cannot Move Task',
                        text: 'Tasks can only be edited from the task management section.',
                        icon: 'warning'
                    });
                    info.revert(); // Revert the drag
                } else {
                    // Update event dates when dragged
                    updateEventDates(info.event);
                }
            },
            eventResize: function(info) {
                // Check if the event is a task
                if (info.event.extendedProps.type === 'task') {
                    // Prevent resizing tasks
                    Swal.fire({
                        title: 'Cannot Resize Task',
                        text: 'Tasks can only be edited from the task management section.',
                        icon: 'warning'
                    });
                    info.revert(); // Revert the resize
                } else {
                    // Update event dates when resized
                    updateEventDates(info.event);
                }
            },
            eventDidMount: function(info) {
                // Make tasks non-editable
                if (info.event.extendedProps.type === 'task') {
                    info.el.style.cursor = 'pointer';
                    info.el.classList.add('non-editable-event');
                }
            }
        });
        
        calendar.render();
        
        // Apply filters when changed
        $('#type-filter, #category-filter, #priority-filter, #status-filter, #department-filter').change(function() {
            applyFilters();
        });
        
        // Function to apply filters
        function applyFilters() {
            const typeFilter = $('#type-filter').val();
            const categoryFilter = $('#category-filter').val();
            const priorityFilter = $('#priority-filter').val();
            const statusFilter = $('#status-filter').val();
            const departmentFilter = $('#department-filter').val(); // Get department filter value
            
            calendar.getEvents().forEach(event => {
                let visible = true;
                
                // Check type filter
                if (typeFilter !== 'all' && event.extendedProps.type !== typeFilter) {
                    visible = false;
                }
                
                // Check category filter
                if (categoryFilter !== 'all' && event.extendedProps.category !== categoryFilter) {
                    visible = false;
                }
                
                // Check priority filter
                if (priorityFilter !== 'all' && event.extendedProps.priority !== priorityFilter) {
                    visible = false;
                }
                
                // Check status filter
                if (statusFilter !== 'all' && event.extendedProps.status !== statusFilter) {
                    visible = false;
                }
                
                // Check department filter
                if (departmentFilter !== 'all' && event.extendedProps.department !== departmentFilter) {
                    visible = false;
                }
                
                // Set visibility
                event.setProp('display', visible ? 'auto' : 'none');
            });
        }
        
        // Function to show event details
        function showEventDetails(event) {
            const startDate = new Date(event.start);
            const endDate = event.end ? new Date(event.end) : null;
            
            // Format date and time
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const timeOptions = { hour: '2-digit', minute: '2-digit' };
            
            let dateTimeStr = startDate.toLocaleDateString('en-US', options);
            
            if (endDate) {
                // Check if same day
                if (startDate.toDateString() === endDate.toDateString()) {
                    dateTimeStr += ' from ' + startDate.toLocaleTimeString('en-US', timeOptions) + 
                                  ' to ' + endDate.toLocaleTimeString('en-US', timeOptions);
                } else {
                    dateTimeStr += ' ' + startDate.toLocaleTimeString('en-US', timeOptions) + 
                                  ' to ' + endDate.toLocaleDateString('en-US', options) + 
                                  ' ' + endDate.toLocaleTimeString('en-US', timeOptions);
                }
            } else {
                dateTimeStr += ' at ' + startDate.toLocaleTimeString('en-US', timeOptions);
            }
            
            // Set values in modal
            $('#event-detail-title').text(event.title.replace(/[🔒👥🌐]\s/, '')); // Remove icons
            $('#event-detail-time').text(dateTimeStr);
            $('#event-detail-description').text(event.extendedProps.description || 'No description provided');
            $('#event-detail-category').text(capitalizeFirstLetter(event.extendedProps.category || 'General'));
            $('#event-detail-visibility').text(capitalizeFirstLetter(event.extendedProps.visibility || 'Private'));
            $('#event-detail-status').text(capitalizeFirstLetter(event.extendedProps.status || 'Upcoming').replace('_', ' '));
            $('#event-detail-location').text(event.extendedProps.location || 'Not specified');
            $('#event-detail-department').text(capitalizeFirstLetter(event.extendedProps.department || 'General')); // Show department
            
            // Set priority badge
            const priorityClass = `priority-${event.extendedProps.priority || 'medium'}`;
            const priorityText = capitalizeFirstLetter(event.extendedProps.priority || 'Medium');
            $('#event-detail-priority').html(`<span class="priority-badge ${priorityClass}">${priorityText}</span>`);
            
            // Store event ID for edit button
            $('#editEventButton').data('event-id', event.id);
            
            // Show modal
            $('#eventDetailsModal').modal('show');
        }
        
        // Function to show task details
        function showTaskDetails(event) {
            // Set values in modal
            $('#task-detail-title').text(event.title.replace(/📋\s/, '')); // Remove task icon
            
            // Set priority badge
            const priorityClass = `priority-${event.extendedProps.priority || 'medium'}`;
            const priorityText = capitalizeFirstLetter(event.extendedProps.priority || 'Medium');
            $('#task-detail-priority').html(`<span class="priority-badge ${priorityClass}">${priorityText}</span>`);
            
            // Set task details
            $('#task-detail-project').text(event.extendedProps.project || 'No Project');
            $('#task-detail-assignee').text(event.extendedProps.assignee || 'Unassigned');
            $('#task-detail-department').text(event.extendedProps.department || 'General');
            $('#task-detail-start-date').text(formatDate(event.extendedProps.startDate) || 'Not set');
            $('#task-detail-due-date').text(formatDate(event.extendedProps.dueDate) || 'Not set');
            $('#task-detail-status').text(event.extendedProps.status || 'Pending');
            $('#task-detail-description').text(event.extendedProps.description || 'No description provided');
            
            // Store task ID for buttons
            $('#editTaskButton').data('task-id', event.extendedProps.taskId);
            $('#completeTaskButton').data('task-id', event.extendedProps.taskId);
            
            // Show or hide complete button based on status
            if (event.extendedProps.status === 'Completed') {
                $('#completeTaskButton').hide();
            } else {
                $('#completeTaskButton').show();
            }
            
            // Show modal
            $('#taskDetailsModal').modal('show');
        }
        
        // Format date for display
        function formatDate(dateStr) {
            if (!dateStr || dateStr === '0000-00-00') return 'Not set';
            
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr; // Return original if invalid
            
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        // Edit button on details modal
        $('#editEventButton').click(function() {
            const eventId = $(this).data('event-id');
            const event = calendar.getEventById(eventId);
            
            if (event) {
                // Populate the form
                $('#event_id').val(event.id.replace('event_', '')); // Remove prefix
                $('#title').val(event.title.replace(/[🔒👥🌐]\s/, '')); // Remove icons
                $('#start_date').val(event.start ? event.start.toISOString().slice(0, 16) : "");
                $('#end_date').val(event.end ? event.end.toISOString().slice(0, 16) : "");
                $('#description').val(event.extendedProps.description || "");
                $('#visibility').val(event.extendedProps.visibility || "private");
                $('#category').val(event.extendedProps.category || "general");
                $('#priority').val(event.extendedProps.priority || "medium");
                $('#status').val(event.extendedProps.status || "upcoming");
                $('#location').val(event.extendedProps.location || "");
                $('#color').val(event.backgroundColor || "#3788d8");
                
                // Update color picker
                pickr.setColor(event.backgroundColor || "#3788d8");
                
                // Set form mode
                $('#action').val("update");
                $('#modalTitle').text("Edit Event");
                $('#deleteEvent').show(); // Show delete button for existing events
                
                // Close details modal and show edit modal
                $('#eventDetailsModal').modal('hide');
                setTimeout(() => {
                    $('#eventModal').modal('show');
                }, 500);
            }
        });
        
    
       // Edit task button
$('#editTaskButton').click(function() {
    const taskId = $(this).data('task-id');
    
    // Redirect to task management page with the task ID
    window.location.href = 'daily_tasks.php?edit=' + taskId;
});
        
        // Complete task button
      // Complete task button
$('#completeTaskButton').click(function() {
    const taskId = $(this).data('task-id');
    
    Swal.fire({
        title: 'Mark as Completed?',
        text: "Are you sure you want to mark this task as completed?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, complete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to mark task as completed
            $.ajax({
                url: 'update_taskk_status.php',
                type: 'POST',
                data: {
                    task_id: taskId,
                    status: '2' // 2 = Completed
                },
                success: function(response) {
                    try {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Task marked as completed',
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                            
                            // Close modal and refresh calendar
                            $('#taskDetailsModal').modal('hide');
                            calendar.refetchEvents();
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.error || 'An error occurred',
                                icon: 'error'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Invalid server response',
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'There was an error completing the task',
                        icon: 'error'
                    });
                }
            });
        }
    });
});
        // Function to reset form
        function resetForm() {
            $('#eventForm')[0].reset();
            $('#event_id').val("");
            $('#visibility').val("private");
            $('#category').val("general");
            $('#priority').val("medium");
            $('#status').val("upcoming");
            $('#color').val("#3788d8");
            pickr.setColor("#3788d8");
            $('#action').val("add");
            
            // Reset tabs
            $('#basic-tab').tab('show');
        }
        
        // Function to update event dates when dragged or resized
        function updateEventDates(event) {
            // Extract the actual event ID by removing the 'event_' prefix
            const eventId = event.id.replace('event_', '');
            
            $.ajax({
                url: 'calender_api.php',
                type: 'POST',
                data: {
                    action: 'update',
                    event_id: eventId,
                    title: event.title.replace(/[🔒👥🌐]\s/, ''), // Remove icons
                    start_date: event.start.toISOString().slice(0, 16),
                    end_date: event.end ? event.end.toISOString().slice(0, 16) : '',
                    description: event.extendedProps.description,
                    visibility: event.extendedProps.visibility,
                    category: event.extendedProps.category,
                    priority: event.extendedProps.priority,
                    status: event.extendedProps.status,
                    color: event.backgroundColor,
                    location: event.extendedProps.location
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Event updated successfully',
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error'
                            });
                            // Revert the drag if there was an error
                            calendar.refetchEvents();
                        }
                    } catch (e) {
                        // If success message is in the response
                        if (response.includes('success') || response.includes('updated')) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Event updated successfully',
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Invalid server response',
                                icon: 'error'
                            });
                            // Revert the drag if there was an error
                            calendar.refetchEvents();
                        }
                    }
                },
                error: function() {
                    console.error("AJAX error:", status, error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'There was an error updating the event',
                        icon: 'error'
                    });
                    // Revert the drag if there was an error
                    calendar.refetchEvents();
                }
            });
        }
        
        // Event form submission
        $('#eventForm').submit(function(e) {
            e.preventDefault();
            
            // Get form values
            const event_id = $('#event_id').val();
            const action = $('#action').val() || (event_id ? 'update' : 'add');
            const title = $('#title').val();
            const start_date = $('#start_date').val();
            const end_date = $('#end_date').val() || start_date; // Default end date to start date if empty
            const description = $('#description').val();
            const visibility = $('#visibility').val();
            const category = $('#category').val();
            const priority = $('#priority').val();
            const status = $('#status').val();
            const color = $('#color').val();
            const location = $('#location').val();
            
            // Validate form
            if (!title || !start_date) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please fill in all required fields',
                    icon: 'error'
                });
                return;
            }
            
            console.log("Submitting event:", {
                action,
                event_id,
                title,
                start_date,
                end_date,
                description,
                visibility,
                category,
                priority,
                status,
                color,
                location
            });
            
            // Send data to server
            $.ajax({
                url: 'calender_api.php',
                type: 'POST',
                dataType: 'json', // Expect JSON response
                data: {
                    action,
                    event_id,
                    title,
                    start_date,
                    end_date,
                    description,
                    visibility,
                    category,
                    priority,
                    status,
                    color,
                    location
                },
                success: function(response) {
                    console.log("Server response:", response); // Debug line
                    
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        
                        // Close modal and refresh calendar
                        $('#eventModal').modal('hide');
                        calendar.refetchEvents();
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'An unknown error occurred',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                    console.error("Response text:", xhr.responseText);
                    
                    Swal.fire({
                        title: 'Error!',
                        text: 'There was an error saving the event. See console for details.',
                        icon: 'error'
                    });
                }
            });
        });
        
        // Delete event
        $('#deleteEvent').click(function() {
            const event_id = $('#event_id').val();
            
            if (!event_id) {
                return;
            }
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'calender_api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            event_id
                        },
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: data.message,
                                        icon: 'success',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                    
                                    // Close modal and refresh calendar
                                    $('#eventModal').modal('hide');
                                    calendar.refetchEvents();
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: data.message,
                                        icon: 'error'
                                    });
                                }
                            } catch (e) {
                                // Handle non-JSON responses
                                if (response.includes('deleted') || response.includes('success')) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: 'Event deleted successfully',
                                        icon: 'success',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                    
                                    // Close modal and refresh calendar
                                    $('#eventModal').modal('hide');
                                    calendar.refetchEvents();
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Invalid server response',
                                        icon: 'error'
                                    });
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX error:", status, error, xhr.responseText);
                            Swal.fire({
                                title: 'Error!',
                                text: 'There was an error deleting the event',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
        
        // Helper function to capitalize first letter
        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        
        
        // Ensure modal is properly initialized for Bootstrap 5
        var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        var eventDetailsModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        var taskDetailsModal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
        // Add this at the end of your $(document).ready function
// Initialize all Bootstrap modals, including the logout modal
var logoutModal = document.getElementById('logoutModal');
if (logoutModal) {
    new bootstrap.Modal(logoutModal);
    
    // Add manual trigger for logout modal
    document.querySelectorAll('[data-target="#logoutModal"]').forEach(function(element) {
        element.addEventListener('click', function() {
            var modal = new bootstrap.Modal(logoutModal);
            modal.show();
        });
    });
}
    });
    
</script>
    <?php include './footer.php'; ?>
</body>
</html>