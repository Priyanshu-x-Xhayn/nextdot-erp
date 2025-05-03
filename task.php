<?php 
include './backend/connect.php';

include './update_task.php';
include './delete_task.php';

// Fetch task data
$query = "SELECT * FROM tasks ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// Check for query failure
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="x-ua-compatible" content="ie=edge" />
  <title>Task Management Tracker</title>

  <!-- CSS Dependencies -->
  <link rel="stylesheet" href="./src/css/adminlte.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet" />
<style>
  /* Enhanced Futuristic Table Styling */
.table-responsive {
  margin-top: 30px;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(7, 46, 99, 0.15), 0 0 10px rgba(248, 195, 0, 0.1);
  overflow: hidden;
  position: relative;
  padding: 5px;
  border: 1px solid rgba(7, 46, 99, 0.08);
  overflow-x : auto ;
}

/* Add a subtle glow effect around the table */
.table-responsive::before {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: linear-gradient(45deg, var(--primary-light), transparent, var(--accent-color), transparent, var(--primary-light));
  z-index: -1;
  border-radius: 18px;
  opacity: 0.5;
  animation: border-glow 6s linear infinite;
}

@keyframes border-glow {
  0% { background-position: 0% 0%; }
  50% { background-position: 100% 100%; }
  100% { background-position: 0% 0%; }
}

#myTable {
  border-collapse: separate;
  border-spacing: 0 8px;
  width: 100%;
  background-color: transparent;
  border: none;
  margin-bottom: 0;
}

/* Remove default table borders */
#myTable td, #myTable th {
  border: none;
}

/* Stylish header styling */
#myTable thead {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
}

#myTable th {
  padding: 16px 12px;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 1px;
  color: var(--text-color);
  vertical-align: middle;
  position: relative;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  border-bottom: none;
}

/* Remove the after element and add pill-shaped headers */
#myTable th:after {
  display: none;
}

#myTable thead tr {
  border-radius: 10px;
  overflow: hidden;
}
#myTable tbody tr::before{
  display : none !important;
}
#myTable th:first-child {
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
}

#myTable th:last-child {
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
}

/* Card-like rows */
#myTable tbody tr {
  background: white;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.03);
  border-radius: 10px;
  margin-bottom: 8px;
  transform-origin: center;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

#myTable tbody tr:hover {
  background: linear-gradient(to right, #f9f9ff, #ffffff, #f9f9ff);
  transform: translateY(-3px) scale(1.005);
  box-shadow: 0 8px 16px rgba(7, 46, 99, 0.06), 0 0 0 1px rgba(7, 46, 99, 0.02);
  z-index: 10;
}

/* Round the corners of the first and last cells in each row */
#myTable tbody td:first-child {
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
  padding-left: 20px;
}

#myTable tbody td:last-child {
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
  padding-right: 20px;
}

#myTable td {
  padding: 16px 12px;
  vertical-align: middle;
  font-size: 0.9rem;
  color: var(--primary-dark);
  background-color: transparent;
  position: relative;
  transition: all 0.3s ease;
}

/* Status indicators styling */
.status-completed,
.status-progress,
.status-pending {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: 20px;
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  min-width: 100px;
}

.status-completed {
  background: linear-gradient(45deg, rgba(28, 200, 138, 0.1), rgba(28, 200, 138, 0.2));
  color: var(--success-color);
  border: 1px solid rgba(28, 200, 138, 0.3);
}

.status-progress {
  background: linear-gradient(45deg, rgba(246, 194, 62, 0.1), rgba(246, 194, 62, 0.2));
  color: var(--warning-color);
  border: 1px solid rgba(246, 194, 62, 0.3);
}

.status-pending {
  background: linear-gradient(45deg, rgba(231, 74, 59, 0.1), rgba(231, 74, 59, 0.2));
  color: var(--danger-color);
  border: 1px solid rgba(231, 74, 59, 0.3);
}

/* Status indicators with pulsing effect */
.status-completed::before,
.status-progress::before,
.status-pending::before {
  content: '';
  position: absolute;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  animation: pulse 2s infinite;
}

.status-completed::before {
  background-color: var(--success-color);
}

.status-progress::before {
  background-color: var(--warning-color);
}

.status-pending::before {
  background-color: var(--danger-color);
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(28, 200, 138, 0.7); }
  70% { box-shadow: 0 0 0 6px rgba(28, 200, 138, 0); }
  100% { box-shadow: 0 0 0 0 rgba(28, 200, 138, 0); }
}

/* Priority badges with futuristic design */
[class^="priority-"] {
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  display: inline-block;
  position: relative;
  overflow: hidden;
}

.priority-high {
  background-color: rgba(28, 200, 138, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(28, 200, 138, 0.3);
}

.priority-medium {
  background-color: rgba(246, 194, 62, 0.1);
  color: var(--warning-color);
  border: 1px solid rgba(246, 194, 62, 0.3);
}

.priority-low {
  background-color: rgba(231, 74, 59, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(231, 74, 59, 0.3);
}

/* Action buttons with improved styling */
.btn-sm {
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin: 0 3px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.btn-sm:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.btn-sm::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  transform: translate(-50%, -50%) scale(0);
  opacity: 0;
  transition: all 0.3s ease;
}

.btn-sm:hover::after {
  transform: translate(-50%, -50%) scale(2);
  opacity: 1;
}

.btn-sm i {
  position: relative;
  z-index: 2;
  font-size: 0.9rem;
}

/* Add alternating subtle row colors */
#myTable tbody tr:nth-child(odd) {
  background-color: rgba(248, 249, 252, 0.7);
}

/* Add hover indicators for rows */
#myTable tbody tr::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 3px;
  background: linear-gradient(to bottom, var(--primary-color), var(--accent-color));
  border-radius: 3px;
  opacity: 0;
  transition: all 0.3s ease;
}

#myTable tbody tr:hover::before {
  opacity: 1;
}

/* Add better spacing for readability */
#myTable td {
  padding-top: 14px;
  padding-bottom: 14px;
}

/* Add zebra striping that's subtle but noticeable */
#myTable tbody tr:nth-child(even) {
  background-color: #ffffff;
}

#myTable tbody tr:nth-child(odd) {
  background-color: rgba(7, 46, 99, 0.02);
}

/* Enhance task ID column to look more futuristic */
#myTable td:first-child {
  font-family: monospace;
  font-weight: 700;
  font-size: 0.85rem;
  color: var(--primary-light);
  letter-spacing: 0.5px;
}

/* Add a subtle hover effect to highlight data in focus */
#myTable td:hover {
  background-color: rgba(248, 195, 0, 0.05);
}

/* Add smooth scrolling */
.table-responsive {
  scroll-behavior: smooth;
}

/* Add custom scrollbar for the table */
.table-responsive::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
  background: rgba(7, 46, 99, 0.05);
  border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, var(--primary-light), var(--primary-dark));
  border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
  background: var(--primary-color);
}

/* Add data visualization for dates */
.date-indicator {
  position: relative;
  display: inline-block;
  padding-left: 20px;
}

.date-indicator::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.date-recent::before {
  background-color: var(--success-color);
}

.date-approaching::before {
  background-color: var(--warning-color);
}

.date-overdue::before {
  background-color: var(--danger-color);
}

/* Fix for responsive design */
@media (max-width: 992px) {
  #myTable {
    width: 100%;
    border-spacing: 0 5px;
  }
  
  #myTable th, #myTable td {
    padding: 12px 8px;
  }
  
  .status-completed, .status-progress, .status-pending {
    padding: 4px 8px;
    min-width: 80px;
  }
}

@media (max-width: 768px) {
  #myTable th, #myTable td {
    padding: 10px 6px;
    font-size: 0.8rem;
  }
  
  .btn-sm {
    width: 28px;
    height: 28px;
  }
}

/* Apply these styles to match the existing HTML structure */
/* Convert inline styles to classes */
span[style*="color:green"] {
  color: var(--success-color) !important;
  padding: 5px 10px;
  border-radius: 20px;
  background-color: rgba(28, 200, 138, 0.1);
  border: 1px solid rgba(28, 200, 138, 0.3);
  display: inline-block;
}

span[style*="color:orange"] {
  color: var(--warning-color) !important;
  padding: 5px 10px;
  border-radius: 20px;
  background-color: rgba(246, 194, 62, 0.1);
  border: 1px solid rgba(246, 194, 62, 0.3);
  display: inline-block;
}

span[style*="color:red"] {
  color: var(--danger-color) !important;
  padding: 5px 10px;
  border-radius: 20px;
  background-color: rgba(231, 74, 59, 0.1);
  border: 1px solid rgba(231, 74, 59, 0.3);
  display: inline-block;
}
/* Export Buttons Styling */
.dt-buttons {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  justify-content: flex-start;
}

/* Base button styling */
.dt-button {
  background: white;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--primary-dark);
  transition: all 0.3s ease;
  box-shadow: 0 2px 6px rgba(7, 46, 99, 0.08);
  position: relative;
  overflow: hidden;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Hover effect */
.dt-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(7, 46, 99, 0.15);
  background: linear-gradient(to bottom, white, rgba(248, 249, 252, 1));
}

/* Active state */
.dt-button:active {
  transform: translateY(1px);
  box-shadow: 0 1px 2px rgba(7, 46, 99, 0.1);
}

/* Focus state */
.dt-button:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(7, 46, 99, 0.2);
}

/* Add icons before text */
.dt-button::before {
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  margin-right: 8px;
  font-size: 0.9rem;
}

.buttons-copy::before {
  content: '\f0c5'; /* Clone icon */
  color: var(--primary-color);
}

.buttons-csv::before {
  content: '\f15c'; /* File icon */
  color: var(--success-color);
}

.buttons-excel::before {
  content: '\f1c3'; /* Excel file icon */
  color: #1d6f42; /* Excel green */
}

.buttons-pdf::before {
  content: '\f1c1'; /* PDF file icon */
  color: #ff0000; /* PDF red */
}

.buttons-print::before {
  content: '\f02f'; /* Print icon */
  color: var(--primary-light);
}

/* Add subtle ripple effect on click */
.dt-button:after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 5px;
  height: 5px;
  background: rgba(255, 255, 255, 0.5);
  opacity: 0;
  border-radius: 100%;
  transform: scale(1, 1) translate(-50%);
  transform-origin: 50% 50%;
}

@keyframes ripple {
  0% {
    transform: scale(0, 0);
    opacity: 0.5;
  }
  100% {
    transform: scale(20, 20);
    opacity: 0;
  }
}

.dt-button:focus:not(:active)::after {
  animation: ripple 1s ease-out;
}

/* Add border accent */
.dt-button::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(to right, var(--primary-color), var(--accent-color));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.dt-button:hover::after {
  opacity: 0;
}

/* Add subtle tooltip on hover */
.dt-button span {
  position: relative;
}

.dt-button span::after {
  content: attr(data-tooltip);
  position: absolute;
  bottom: -25px;
  left: 50%;
  transform: translateX(-50%);
  background-color: rgba(7, 46, 99, 0.8);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.7rem;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  white-space: nowrap;
  pointer-events: none;
}

.dt-button:hover span::after {
  opacity: 1;
  visibility: visible;
}

/* Button group effect */
.dt-buttons {
  position: relative;
  background: rgba(248, 249, 252, 0.5);
  border-radius: 10px;
  padding: 10px;
  box-shadow: inset 0 0 5px rgba(7, 46, 99, 0.05);
}

/* Add a glowing border effect */
.dt-buttons::before {
  content: '';
  position: absolute;
  top: -1px;
  left: -1px;
  right: -1px;
  bottom: -1px;
  background: linear-gradient(45deg, var(--primary-light), transparent, var(--accent-color), transparent);
  z-index: -1;
  border-radius: 12px;
  opacity: 0.3;
  animation: border-glow 6s linear infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .dt-buttons {
    justify-content: center;
    padding: 8px;
  }
  
  .dt-button {
    padding: 6px 12px;
    font-size: 0.8rem;
  }
  
  .dt-button::before {
    margin-right: 5px;
    font-size: 0.8rem;
  }
}

@media (max-width: 576px) {
  .dt-buttons {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 8px;
  }
  
  .dt-button {
    flex: 1 0 calc(50% - 8px);
    min-width: 120px;
  }
}

/* Add JS attribute for tooltips */
/* You would add these attributes in JavaScript */
.buttons-copy span { data-tooltip: "Copy to clipboard"; }
.buttons-csv span { data-tooltip: "Export as CSV"; }
.buttons-excel span { data-tooltip: "Export as Excel"; }
.buttons-pdf span { data-tooltip: "Export as PDF"; }
.buttons-print span { data-tooltip: "Print view"; }

/* Enhanced Button Styling */
.btn-primary {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  border: none;
  color: var(--text-color);
  border-radius: var(--border-radius);
  padding: 10px 24px;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  box-shadow: var(--shadow), 0 0 0 rgba(248, 195, 0, 0);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  overflow: hidden;
  z-index: 1;
}

.btn-primary::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: all 0.6s ease;
  z-index: -1;
}

.btn-primary::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 100%;
  height: 3px;
  background: var(--accent-color);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform 0.4s ease;
  z-index: -1;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: var(--hover-shadow), 0 0 15px rgba(248, 195, 0, 0.4);
  background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
}

.btn-primary:hover::before {
  left: 100%;
}

.btn-primary:hover::after {
  transform: scaleX(1);
  transform-origin: left;
}

.btn-primary:active {
  transform: translateY(0);
  box-shadow: var(--shadow);
}

/* Add a subtle pulse animation for extra attention */
@keyframes pulse {
  0% {
    box-shadow: var(--shadow), 0 0 0 0 rgba(248, 195, 0, 0.5);
  }
  70% {
    box-shadow: var(--shadow), 0 0 0 10px rgba(248, 195, 0, 0);
  }
  100% {
    box-shadow: var(--shadow), 0 0 0 0 rgba(248, 195, 0, 0);
  }
}

.btn-primary.pulse {
  animation: pulse 2s infinite;
}

/* Custom add icon */
.btn-primary.with-icon {
  padding-left: 50px;
  position: relative;
}

.btn-primary.with-icon::before {
  content: '+';
  position: absolute;
  left: 20px;
  font-size: 1.2rem;
  font-weight: bold;
  transition: all 0.3s ease;
}

.btn-primary.with-icon:hover::before {
  transform: rotate(90deg);
}

/* Media Queries for Responsive Design */
@media (max-width: 768px) {
  .btn-primary {
    padding: 8px 20px;
    font-size: 0.9rem;
  }
}

@media (max-width: 576px) {
  .btn-primary {
    padding: 7px 16px;
    font-size: 0.8rem;
  }
}
/* Enhanced Form Controls Styling */
.form-control {
  border: 1px solid rgba(7, 46, 99, 0.2);
  border-radius: var(--border-radius);
  padding: 10px 16px;
  font-size: 0.95rem;
  transition: var(--transition);
  background-color: rgba(255, 255, 255, 0.9);
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
  color: var(--primary-dark);
}

.form-control:focus {
  border-color: var(--accent-color);
  box-shadow: 0 0 0 3px rgba(248, 195, 0, 0.25);
  outline: none;
  background-color: #ffffff;
}

.form-control::placeholder {
  color: rgba(7, 46, 99, 0.5);
  font-style: italic;
  font-weight: 300;
}

/* Date input styling */
input[type="date"].form-control {
  position: relative;
  color: var(--primary-dark);
  cursor: pointer;
  min-width: 150px;
}

input[type="date"].form-control::-webkit-calendar-picker-indicator {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  filter: invert(12%) sepia(83%) saturate(1559%) hue-rotate(199deg) brightness(93%) contrast(97%);
  opacity: 0.7;
  transition: var(--transition);
}

input[type="date"].form-control::-webkit-calendar-picker-indicator:hover {
  opacity: 1;
  transform: translateY(-50%) scale(1.1);
}

/* Search input styling */
input[type="text"].form-control {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23072e63' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 16px 16px;
  padding-right: 40px;
}

/* Container styling */
.d-flex.align-items-center.gap-2 {
  background: linear-gradient(to right, rgba(255, 255, 255, 0.9), rgba(248, 195, 0, 0.1));
  padding: 10px 15px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  transition: var(--transition);
  border: 1px solid rgba(7, 46, 99, 0.1);
}

.d-flex.align-items-center.gap-2:focus-within {
  box-shadow: var(--hover-shadow);
  border-color: rgba(7, 46, 99, 0.2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .d-flex.align-items-center.gap-2 {
    flex-direction: column;
    align-items: stretch !important;
    gap: 10px !important;
  }
  
  .form-control.me-2 {
    margin-right: 0 !important;
    margin-bottom: 8px;
  }
  
  input[type="date"].form-control {
    min-width: auto;
  }
}
  </style>
</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <?php include './header.php'; ?>

    <div class="content-wrapper">
      <div class="container-fluid">

        <!-- Header -->
        <div class="header-bar d-flex justify-content-between align-items-center flex-wrap my-3">
          <h2 class="mb-2 mb-md-0">Task Management Tracker</h2>
          <div class="d-flex align-items-center gap-2">
            <input type="date" class="form-control me-2" />
            <input type="text" class="form-control" placeholder="Search tasks..." />
          </div>
        </div>

        <!-- Action Button -->
        <div class="mb-3">
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adminModal">Add Task</button>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <form action="insert_task.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                  <h5 class="modal-title" id="adminModalLabel">Add Task</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="task_name">Task Name</label>
                      <input type="text" class="form-control" name="task_name" placeholder="Task Name" required />
                    </div>
                    <div class="form-group col-md-6">
                      <label for="description">Description</label>
                      <input type="text" class="form-control" name="description" placeholder="Description" required />
                    </div>
                    <div class="form-group col-md-6">
                      <label for="assigned_to">Assigned To</label>
                      <input type="text" class="form-control" name="assigned_to" placeholder="Assigned To" required />
                    </div>
                    <div class="form-group col-md-6">
                      <label for="start_date">Start Date</label>
                      <input type="date" class="form-control" name="start_date" required />
                    </div>
                    <div class="form-group col-md-6">
                      <label for="due_date">Due Date</label>
                      <input type="date" class="form-control" name="due_date" required />
                    </div>
                    <div class="form-group col-md-6">
                      <label for="Priority">Priority</label>
                      <select name="Priority" class="form-control" required>
                        <option value="">Select</option>
                        <option value="High" style="color: green; font-weight: bold;">High</option>
                        <option value="Medium" style="color: orange; font-weight: bold;">Medium</option>
                        <option value="Low" style="color: red; font-weight: bold;">Low</option>
                      </select>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="completion_date">Completion Date</label>
                      <input type="date" class="form-control" name="completion_date" required />
                    </div>
                    <div class="form-group col-md-6">
                      <label for="notes">Notes</label>
                      <input type="text" class="form-control" name="notes" placeholder="Notes" required />
                    </div>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-primary" name="submit">Submit</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive overflow-scroll">
          <table id="myTable" class="table table-bordered text-center align-middle w-100">
            <thead>
            <tr>
  <th>Task ID</th>
  <th>Task Name</th>
  <th>Description</th>
  <th>Assigned To</th>
  <th>Start Date</th>
  <th>Due Date</th>
  <th>Priority</th>
  <th>Completion Date</th>
  <th>Notes</th>
  <th>Status</th>
  <th>Overdue</th>
  <th>Pending Days</th>
  <th>Pending Age</th>
  <th>Action</th>
</tr>
</thead>
<tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= $row['task_name'] ?></td>
              <td><?= $row['description'] ?></td>
              <td><?= $row['assigned_to'] ?></td>
              <td><?= $row['start_date'] ?></td>
              <td><?= $row['due_date'] ?></td>
              <td><span style="color:<?= $row['priori'] == 'High' ? 'green' : ($row['priori'] == 'Medium' ? 'orange' : 'red') ?>;font-weight:bold"><?= $row['priori'] ?></span></td>
              <td><?= $row['completion_date'] ?></td>
              <td><?= $row['notes'] ?></td>
              <td>
                <?php
                  $today = date('Y-m-d');
                  $completed = $row['completion_date'] && $row['completion_date'] !== '0000-00-00';
                  if ($completed) echo '<span style="color: green; font-weight: bold;">Completed</span>';
                  elseif ($today >= $row['start_date'] && $today <= $row['due_date']) echo '<span style="color: orange; font-weight: bold;">In Progress</span>';
                  else echo '<span style="color: red; font-weight: bold;">Pending</span>';
                ?>
              </td>
              <td><?= (!$completed && strtotime($row['due_date']) < strtotime($today)) ? '<span style="color:red;font-weight:bold">Yes</span>' : 'No' ?></td>
              <td><?= (strtotime($row['due_date']) - strtotime($today)) / 86400 >= 0 ? round((strtotime($row['due_date']) - strtotime($today)) / 86400) . ' day(s)' : 'Overdue' ?></td>
              <td><?= round((time() - strtotime($row['start_date'])) / 86400) . ' day(s)' ?></td>
              <td>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>"><i class="fa fa-edit"></i></button>
                <form action="delete_task.php" method="POST" class="d-inline">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <button class="btn btn-sm btn-danger" name="delete"><i class="fa fa-trash"></i></button>
                </form>
              </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form action="update_task.php" method="POST">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Task #<?= $row['id'] ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-2">
                      <input type="text" name="task_name" value="<?= $row['task_name'] ?>" class="form-control" required>
                      <input type="text" name="description" value="<?= $row['description'] ?>" class="form-control" required>
                      <input type="text" name="assigned_to" value="<?= $row['assigned_to'] ?>" class="form-control" required>
                      <input type="date" name="start_date" value="<?= $row['start_date'] ?>" class="form-control" required>
                      <input type="date" name="due_date" value="<?= $row['due_date'] ?>" class="form-control" required>
                      <select name="Priority" class="form-control" required>
                        <option value="High" <?= $row['priori'] == 'High' ? 'selected' : '' ?>>High</option>
                        <option value="Medium" <?= $row['priori'] == 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="Low" <?= $row['priori'] == 'Low' ? 'selected' : '' ?>>Low</option>
                      </select>
                      <input type="date" name="completion_date" value="<?= $row['completion_date'] ?>" class="form-control" required>
                      <input type="text" name="notes" value="<?= $row['notes'] ?>" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-primary" name="update">Update Task</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php } ?>
          </tbody>
        </table>
      </div>
  <!-- JS Dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="./src/js/adminlte.min.js"></script>

  <!-- DataTables + Export Buttons -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

  <!-- DataTable Initialization -->
  <script>
    $(document).ready(function () {
      $('#myTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
      });
    });
  </script>
</body>
</html>
