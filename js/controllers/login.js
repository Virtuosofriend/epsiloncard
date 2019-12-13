////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Card App - Created by eCosmos 			     			//
//                                                                    //
//										www.euacosmos.com						       							//
//																																		//
////////////////////////////////////////////////////////////////////////

mainapp.controller("loginCtrl", ['$scope', '$rootScope', '$state', 'fetchUserLogin', function ($scope, $rootScope, $state, fetchUserLogin) {
  $scope.login = {};
  $scope.signup = {};

  $scope.doLogin = function(customer) {
    fetchUserLogin.post('login', {
      customer: customer
    }).then(function(results) {
      if (results.status == "success") {
        localStorage.sessionId = results.session_id;
        $state.go("app", {
          user_id: results.uid
        });
      }

    });
  };

  $scope.signUp = function(customer) {
    fetchUserLogin.post('signUp', {
      customer: customer
    }).then(function(results) {
      fetchUserLogin.toast(results);
      if (results.status == "success") {
        $state.go("app");
      }
    });
  };
}]);


mainapp.controller('logoutController', function($scope, $rootScope, $window, $stateParams, $http, $state, fetchUserLogin) {
  $scope.logout = function() {
    fetchUserLogin.get('logout').then(function(results) {
      $state.go("login", {
        reload: true,
        inherit: false,
        notify: true
      });
    });
  };
});
