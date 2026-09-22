/**
 * TaskService - Handles Task Management & Dynamic Rule Engine API Integrations
 */
app.factory('TaskService', ['$http', function($http) {
    var service = {};

    service.getTasks = function(params) {
        return $http.get('/api/v1/tasks', { params: params })
            .then(function(response) {
                return response.data;
            });
    };

    service.getTask = function(id) {
        return $http.get('/api/v1/tasks/' + id)
            .then(function(response) {
                return response.data.task;
            });
    };

    service.createTask = function(taskData) {
        return $http.post('/api/v1/tasks', taskData)
            .then(function(response) {
                return response.data;
            });
    };

    service.updateTask = function(id, taskData) {
        return $http.put('/api/v1/tasks/' + id, taskData)
            .then(function(response) {
                return response.data;
            });
    };

    service.deleteTask = function(id) {
        return $http.delete('/api/v1/tasks/' + id)
            .then(function(response) {
                return response.data;
            });
    };

    service.getMyEligibleTasks = function() {
        return $http.get('/api/v1/my-eligible-tasks')
            .then(function(response) {
                return response.data;
            });
    };

    service.getEligibleUsers = function(taskId) {
        return $http.get('/api/v1/tasks/' + taskId + '/eligible-users')
            .then(function(response) {
                return response.data;
            });
    };

    service.recomputeEligibility = function() {
        return $http.post('/api/v1/tasks/recompute-eligibility')
            .then(function(response) {
                return response.data;
            });
    };

    return service;
}]);
