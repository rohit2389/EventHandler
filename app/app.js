var app = angular.module('myApp', ['ngRoute', 'ui.bootstrap', 'toaster']);

app.config(['$routeProvider',
  function($routeProvider) {
    $routeProvider.
    when('/dashboard', {
        templateUrl: 'partials/dashboard.html',
        controller: 'eventsCtrl'
    })
    .when('/calendar', {
        templateUrl: 'partials/calendar.html',
        controller: 'calCtrl'
    })
    .when('/login', {
        templateUrl: 'partials/login.html',
        controller: 'authCtrl'
    })
    .when('/signup', {
        templateUrl: 'partials/signup.html',
        controller: 'authCtrl'
    })
    .when('/logout', {
        templateUrl: 'partials/login.html',
        controller: 'authCtrl'
    })

    .otherwise({
      redirectTo: '/login'
    });
}])
.run(function ($rootScope, $location, Data) {
        $rootScope.$on("$routeChangeStart", function (event, next, current) {
            $rootScope.authenticated = false;
            Data.get('session').then(function (results) {
                if (results.api_key) {
                    $rootScope.authenticated = true;
                    $rootScope.userID = results.userID;
                    $rootScope.userName = results.userName;
                    $rootScope.api_key = results.api_key;
                    $rootScope.userType = results.userType;
                    var nextUrl = next.$$route.originalPath;
                    if (nextUrl == '/signup' || nextUrl == '/login') {

                        $location.path("/dashboard");
                    }
                } else {
                    var nextUrl = next.$$route.originalPath;
                    if (nextUrl == '/signup' || nextUrl == '/login') {

                    } else {
                        $location.path("/login");
                    }
                }
            });
        });
    });
