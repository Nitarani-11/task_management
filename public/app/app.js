/**
 * Task Management System & Dynamic Rule Engine
 * AngularJS Application Module & Route Configuration
 */
var app = angular.module('taskApp', ['ngRoute']);

// Configure Routes & HTTP Interceptor
app.config(['$routeProvider', '$httpProvider', function($routeProvider, $httpProvider) {
    
    // Register Sanctum Bearer Token Interceptor
    $httpProvider.interceptors.push('AuthInterceptor');

    $routeProvider
        .when('/login', {
            templateUrl: '/app/views/login.html',
            controller: 'LoginController'
        })
        .when('/register', {
            templateUrl: '/app/views/register.html',
            controller: 'RegisterController'
        })
        .when('/dashboard', {
            templateUrl: '/app/views/dashboard.html',
            controller: 'DashboardController',
            requiresAuth: true
        })
        .when('/my-tasks', {
            templateUrl: '/app/views/my-tasks.html',
            controller: 'MyTasksController',
            requiresAuth: true
        })
        .when('/tasks', {
            templateUrl: '/app/views/task-list.html',
            controller: 'TaskListController',
            requiresAuth: true
        })
        .when('/tasks/create', {
            templateUrl: '/app/views/task-create.html',
            controller: 'TaskCreateController',
            requiresAuth: true
        })
        .when('/tasks/:id', {
            templateUrl: '/app/views/task-detail.html',
            controller: 'TaskDetailController',
            requiresAuth: true
        })
        .otherwise({
            redirectTo: '/dashboard'
        });
}]);

// Auth Interceptor Service
app.factory('AuthInterceptor', ['$q', '$location', function($q, $location) {
    return {
        request: function(config) {
            var token = localStorage.getItem('auth_token');
            if (token) {
                config.headers['Authorization'] = 'Bearer ' + token;
            }
            return config;
        },
        responseError: function(response) {
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('auth_user');
                $location.path('/login');
            }
            return $q.reject(response);
        }
    };
}]);

// Authentication Guard & Route Listener
app.run(['$rootScope', '$location', 'AuthService', function($rootScope, $location, AuthService) {
    $rootScope.$on('$routeChangeStart', function(event, next, current) {
        if (next && next.requiresAuth && !AuthService.isAuthenticated()) {
            $location.path('/login');
        }
    });
}]);

// Global Main Layout Controller
app.controller('MainController', ['$scope', '$location', 'AuthService', function($scope, $location, AuthService) {
    var self = this;

    self.isAuthenticated = function() {
        return AuthService.isAuthenticated();
    };

    self.currentUser = AuthService.getUser();

    $scope.$on('userUpdated', function(event, user) {
        self.currentUser = user;
    });

    self.isActive = function(path) {
        return $location.path() === path;
    };

    self.logout = function() {
        AuthService.logout().then(function() {
            $location.path('/login');
        });
    };
}]);
