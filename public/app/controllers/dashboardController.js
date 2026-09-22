/**
 * DashboardController - Handles Dashboard metrics & quick actions
 */
app.controller('DashboardController', ['$scope', 'TaskService', 'AuthService', function($scope, TaskService, AuthService) {
    $scope.user = AuthService.getUser();
    $scope.stats = {
        totalTasks: 0,
        myTasks: 0,
        unassignedTasks: 0,
        completedTasks: 0
    };
    $scope.loading = true;
    $scope.recomputing = false;
    $scope.message = '';

    $scope.loadDashboard = function() {
        $scope.loading = true;
        TaskService.getTasks({ per_page: 100 })
            .then(function(data) {
                var tasks = data.data || [];
                $scope.stats.totalTasks = data.total || tasks.length;
                $scope.stats.unassignedTasks = tasks.filter(function(t) { return t.assignment_status === 'unassigned'; }).length;
                $scope.stats.completedTasks = tasks.filter(function(t) { return t.status === 'done'; }).length;
            })
            .finally(function() {
                $scope.loading = false;
            });

        TaskService.getMyEligibleTasks()
            .then(function(data) {
                $scope.stats.myTasks = data.assigned_tasks_count || 0;
            });
    };

    $scope.triggerRecompute = function() {
        $scope.recomputing = true;
        $scope.message = '';
        TaskService.recomputeEligibility()
            .then(function(res) {
                $scope.message = 'Eligibility recomputation job dispatched to queue worker.';
                $scope.loadDashboard();
            })
            .finally(function() {
                $scope.recomputing = false;
            });
    };

    $scope.loadDashboard();
}]);
