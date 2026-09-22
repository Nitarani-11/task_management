/**
 * AuthService - Manages Authentication, Tokens & User Profile State
 */
app.factory('AuthService', ['$http', '$rootScope', function($http, $rootScope) {
    var service = {};

    service.login = function(email, password) {
        return $http.post('/api/v1/login', { email: email, password: password })
            .then(function(response) {
                var data = response.data;
                localStorage.setItem('auth_token', data.access_token);
                localStorage.setItem('auth_user', JSON.stringify(data.user));
                $rootScope.$broadcast('userUpdated', data.user);
                return data;
            });
    };

    service.register = function(userData) {
        return $http.post('/api/v1/register', userData)
            .then(function(response) {
                var data = response.data;
                localStorage.setItem('auth_token', data.access_token);
                localStorage.setItem('auth_user', JSON.stringify(data.user));
                $rootScope.$broadcast('userUpdated', data.user);
                return data;
            });
    };

    service.logout = function() {
        return $http.post('/api/v1/logout').finally(function() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            $rootScope.$broadcast('userUpdated', null);
        });
    };

    service.updateProfile = function(profileData) {
        return $http.put('/api/v1/auth/profile', profileData)
            .then(function(response) {
                var user = response.data.user;
                localStorage.setItem('auth_user', JSON.stringify(user));
                $rootScope.$broadcast('userUpdated', user);
                return user;
            });
    };

    service.getToken = function() {
        return localStorage.getItem('auth_token');
    };

    service.getUser = function() {
        var user = localStorage.getItem('auth_user');
        return user ? JSON.parse(user) : null;
    };

    service.isAuthenticated = function() {
        return !!service.getToken();
    };

    return service;
}]);
