/**
 * RegisterController - Handles user registration with profile attributes
 */
app.controller('RegisterController', ['$scope', '$location', 'AuthService', function($scope, $location, AuthService) {
    $scope.user = {
        name: '',
        email: '',
        password: '',
        role: 'user',
        department: 'Finance',
        years_of_experience: 3,
        location: 'Bhubaneswar'
    };

    $scope.errorMessage = '';
    $scope.loading = false;

    $scope.register = function() {
        $scope.errorMessage = '';
        $scope.loading = true;

        AuthService.register($scope.user)
            .then(function() {
                $location.path('/dashboard');
            })
            .catch(function(error) {
                if (error.data && error.data.errors) {
                    var firstErrKey = Object.keys(error.data.errors)[0];
                    $scope.errorMessage = error.data.errors[firstErrKey][0];
                } else {
                    $scope.errorMessage = error.data && error.data.message ? error.data.message : 'Registration failed.';
                }
            })
            .finally(function() {
                $scope.loading = false;
            });
    };
}]);
