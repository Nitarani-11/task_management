<!DOCTYPE html>
<html lang="en" ng-app="taskApp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management & Rule Engine System | Indus Action</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Application Styling -->
    <link rel="stylesheet" href="/css/app-ui.css">

    <!-- AngularJS Core & Route Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular-route.min.js"></script>
</head>
<body ng-controller="MainController as main">
    
    <!-- Top Navigation Bar -->
    <header class="top-nav" ng-if="main.isAuthenticated()">
        <div class="nav-brand">
            <div class="logo-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div class="logo-text">TaskEngine <span>RTE</span></div>
        </div>

        <div class="nav-metrics" ng-if="main.currentUser">
            <div class="metric-pill">
                <i class="fa-solid fa-building"></i> @{{main.currentUser.department || 'General'}}
            </div>
            <div class="metric-pill workload">
                <i class="fa-solid fa-list-check"></i> Workload: <strong>@{{main.currentUser.active_tasks_count || 0}}</strong> tasks
            </div>
            <div class="metric-pill role" ng-class="main.currentUser.role">
                <i class="fa-solid fa-user-shield"></i> @{{main.currentUser.role | uppercase}}
            </div>
        </div>

        <div class="nav-user">
            <div class="user-info">
                <span class="user-name">@{{main.currentUser.name}}</span>
                <span class="user-email">@{{main.currentUser.email}}</span>
            </div>
            <button class="btn-logout" ng-click="main.logout()" title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </div>
    </header>

    <div class="app-layout" ng-class="{'full-width': !main.isAuthenticated()}">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" ng-if="main.isAuthenticated()">
            <nav class="sidebar-nav">
                <a href="#!/dashboard" class="nav-item" ng-class="{active: main.isActive('/dashboard')}">
                    <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
                </a>
                <a href="#!/my-tasks" class="nav-item" ng-class="{active: main.isActive('/my-tasks')}">
                    <i class="fa-solid fa-user-check"></i> <span>My Assigned Tasks</span>
                    <span class="badge" ng-if="main.currentUser.active_tasks_count">@{{main.currentUser.active_tasks_count}}</span>
                </a>
                <a href="#!/tasks" class="nav-item" ng-class="{active: main.isActive('/tasks')}">
                    <i class="fa-solid fa-list-check"></i> <span>All System Tasks</span>
                </a>
                <a href="#!/tasks/create" class="nav-item highlight" ng-class="{active: main.isActive('/tasks/create')}">
                    <i class="fa-solid fa-plus-circle"></i> <span>Create Task with Rules</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="system-status">
                    <span class="status-dot"></span> Queue Worker Engine Active
                </div>
            </div>
        </aside>

        <!-- Main View Container -->
        <main class="main-content">
            <div ng-view class="view-animate"></div>
        </main>
    </div>

    <!-- AngularJS Application Scripts -->
    <script src="/app/app.js"></script>
    <script src="/app/services/authService.js"></script>
    <script src="/app/services/taskService.js"></script>
    <script src="/app/controllers/loginController.js"></script>
    <script src="/app/controllers/registerController.js"></script>
    <script src="/app/controllers/dashboardController.js"></script>
    <script src="/app/controllers/myTasksController.js"></script>
    <script src="/app/controllers/taskListController.js"></script>
    <script src="/app/controllers/taskCreateController.js"></script>
    <script src="/app/controllers/taskDetailController.js"></script>
</body>
</html>
