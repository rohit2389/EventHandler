app.controller('authCtrl', function ($scope, $rootScope, $routeParams, $location, $http, Data) {
    $scope.login = {};
    $scope.signup = {};
        
    $scope.doLogin = function (login) {
        Data.post('login', login).then(function (results) {
            Data.toast(results);
            if (results.status == "success") {
                $location.path('dashboard');
            }
        });
    };
    $scope.signup = {name:'',email:'',password:''};
    $scope.signUp = function (signup) {
        Data.post('register', signup).then(function (results) {
            Data.toast(results);
            console.log(results);
            if (results.status == "success") {
                $location.path('dashboard');
            }
        });
    };
    $scope.logout = function () {
        Data.get('logout').then(function (results) {
            Data.toast(results);
            $rootScope.userName = results.userName;
            $rootScope.api_key = results.api_key;
            $rootScope.userType = results.userType;
            $location.path('login');
        });
    }
});