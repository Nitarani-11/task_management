/**
 * TaskCreateController - Task Creation & Dynamic Rule Builder UI (Story 1)
 */
app.controller('TaskCreateController', ['$scope', '$location', 'TaskService', function($scope, $location, TaskService) {
    $scope.task = {
        title: '',
        description: '',
        priority: 'medium',
        due_date: null,
        rule: {
            department: 'Finance',
            min_experience: 4,
            max_experience: null,
            max_active_tasks: 5,
            location: ''
        }
    };

    $scope.loading = false;
    $scope.errorMessage = '';
    $scope.assignedUser = null;

    $scope.submitTask = function() {
        $scope.loading = true;
        $scope.errorMessage = '';

        TaskService.createTask($scope.task)
            .then(function(res) {
                $location.path('/tasks/' + res.task.id);
            })
            .catch(function(err) {
                $scope.errorMessage = err.data && err.data.message ? err.data.message : 'Error creating task.';
            })
            .finally(function() {
                $scope.loading = false;
            });
    };
}]);
