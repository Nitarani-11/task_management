/**
 * TaskDetailController - Detail view, Eligible Users list & Audit logs
 */
app.controller('TaskDetailController', ['$scope', '$routeParams', 'TaskService', function($scope, $routeParams, TaskService) {
    $scope.taskId = $routeParams.id;
    $scope.task = null;
    $scope.eligibleUsers = [];
    $scope.loading = true;

    $scope.loadTask = function() {
        $scope.loading = true;
        TaskService.getTask($scope.taskId)
            .then(function(task) {
                $scope.task = task;
            })
            .finally(function() {
                $scope.loading = false;
            });

        TaskService.getEligibleUsers($scope.taskId)
            .then(function(data) {
                $scope.eligibleUsers = data.eligible_users || [];
            });
    };

    $scope.loadTask();
}]);
