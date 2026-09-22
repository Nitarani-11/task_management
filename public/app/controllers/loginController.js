/**
 * LoginController - Handles user authentication & quick demo logins
 */
app.controller('LoginController', ['$scope', '$location', 'AuthService', function($scope, $location, AuthService) {
    $scope.credentials = {
        email: '',
        password: ''
    };

    $scope.errorMessage = '';
    $scope.loading = false;

    if (AuthService.isAuthenticated()) {
        $location.path('/dashboard');
    }

    $scope.login = function() {
        $scope.errorMessage = '';
        $scope.loading = true;

        AuthService.login($scope.credentials.email, $scope.credentials.password)
            .then(function() {
                $location.path('/dashboard');
            })
            .catch(function(error) {
                $scope.errorMessage = error.data && error.data.message ? error.data.message : 'Invalid credentials.';
            })
            .finally(function() {
                $scope.loading = false;
            });
    };

    $scope.fillDemo = function(role) {
        if (role === 'admin') {
            $scope.credentials.email = 'admin@example.com';
            $scope.credentials.password = 'password';
        } else if (role === 'finance') {
            $scope.credentials.email = 'finance.user@example.com';
            $scope.credentials.password = 'password';
        } else if (role === 'manager') {
            $scope.credentials.email = 'manager@example.com';
            $scope.credentials.password = 'password';
        }
    };
}]);
