////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Card App - Created by eCosmos 			     			//
//                                                                    //
//										www.euacosmos.com						       							//
//																																		//
////////////////////////////////////////////////////////////////////////

mainapp.controller("headerCtrl", ['$scope', '$rootScope', '$state', function ($scope, $rootScope, $state) {
  $scope.now = {
   text: 'hello world!',
   time: new Date(),
   date: moment()
  };

  $('.usr-act').on('click', function () {
    $(this).toggleClass('active');
    return false;
  });
}]);
