/**
 * MyTasksController - User Assigned Tasks with sub-200ms Redis latency metric
 */
app.controller('MyTasksController', ['$scope', 'TaskService', function($scope, TaskService) {
    $scope.assignedTasks = [];
    $scope.userProfile = null;
    $scope.responseTimeMs = 0;
    $scope.loading = true;

    $scope.loadMyTasks = function() {
        $scope.loading = true;
        TaskService.getMyEligibleTasks()
            .then(function(data) {
                $scope.assignedTasks = data.assigned_tasks || [];
                $scope.userProfile = data.user;
                $scope.responseTimeMs = data._meta ? data._meta.response_time_ms : 0;
            })
            .finally(function() {
                $scope.loading = false;
            });
    };

    $scope.markAsDone = function(task) {
        TaskService.updateTask(task.id, { status: 'done' })
            .then(function() {
                $scope.loadMyTasks();
            });
    };

    $scope.loadMyTasks();
}]);
