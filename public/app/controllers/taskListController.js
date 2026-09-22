/**
 * TaskListController - System Task Explorer & Filter
 */
app.controller('TaskListController', ['$scope', 'TaskService', function($scope, TaskService) {
    $scope.tasks = [];
    $scope.filters = {
        department: '',
        status: '',
        priority: ''
    };
    $scope.loading = true;

    $scope.loadTasks = function() {
        $scope.loading = true;
        var queryParams = {};
        if ($scope.filters.department) queryParams.department = $scope.filters.department;
        if ($scope.filters.status) queryParams.status = $scope.filters.status;
        if ($scope.filters.priority) queryParams.priority = $scope.filters.priority;

        TaskService.getTasks(queryParams)
            .then(function(data) {
                $scope.tasks = data.data || [];
            })
            .finally(function() {
                $scope.loading = false;
            });
    };

    $scope.deleteTask = function(id) {
        if (confirm('Are you sure you want to delete this task?')) {
            TaskService.deleteTask(id).then(function() {
                $scope.loadTasks();
            });
        }
    };

    $scope.loadTasks();
}]);
