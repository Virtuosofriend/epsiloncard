////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Card App - Created by eCosmos 			     			//
//                                                                    //
//										www.euacosmos.com						       							//
//																																		//
////////////////////////////////////////////////////////////////////////

mainapp.controller("addCompCtrl", ['$scope', '$rootScope', '$state','addNewComp','toaster', function ($scope, $rootScope, $state, addNewComp, toaster) {
  $scope.formData = {};
  let actions = $scope.$resolve.auth;

  $scope.addcompany = function () {
    actions["action"] = "addcompany";
    let returnedObject = Object.assign(actions, $scope.formData);
    addNewComp.getData(returnedObject).then(function(response) {
      if (response.status === 200) {
        toaster.pop({
          type: 'success',
          title: success('company'),
          onShowCallback: function () {
            $state.go("app.companies");
          }
        });
      } else {
        toaster.pop({
          type: 'error',
          title: error(),
          onShowCallback: function () {
            $state.go("app");
          }
        });
      }
    });
  };
}]);


mainapp.controller("editCompCtrl", ['$scope', '$rootScope', '$state','addNewComp','editCompanyServ','toaster', function ($scope, $rootScope, $state, addNewComp,editCompanyServ,toaster) {
  $scope.formData = editCompanyServ.getProducts();
  let actions = $scope.$resolve.auth;

  $scope.addcompany = function () {
    actions["action"] = "addcompany";
    let returnedObject = Object.assign(actions, $scope.formData);

    addNewComp.getData(returnedObject).then(function(response) {
      if (response.status === 200) {
        toaster.pop({
          type: 'success',
          title: success(),
          onShowCallback: function () {
            $state.go("app.companies");
          }
        });
      } else {
        toaster.pop({
          type: 'error',
          title: error(),
          onShowCallback: function () {
            $state.go("app");
          }
        });
      }

    });
  };
}]);


mainapp.controller("companiesActionsCtrl", ['$scope', '$rootScope', '$state','$mdDialog', 'editCompanyServ','delComp','toaster', function ($scope, $rootScope, $state, $mdDialog, editCompanyServ,delComp,toaster) {
  var originatorEv;
  this.openMenu = function($mdMenu, ev) {
    originatorEv = ev;
    $mdMenu.open(ev);
  };

  this.edit_company = function (name,vat,address) {
    var obj = {
      "company_name": name,
      "vat":  vat,
      "address": address
    };
    editCompanyServ.addProduct(obj);
    $state.go("app.companies.edit");
  };

  this.delete_company = function (vat, ev) {
    var delete_obj = $rootScope.actions;
    delete_obj["action"] = "deletecompany";
    delete_obj["vat"] = vat;
    var confirm = $mdDialog.confirm()
          .title('Are you sure?')
          .textContent('This action cannot be undone.')
          .ariaLabel('Lucky day')
          .targetEvent(ev)
          .ok('Please do it!')
          .cancel('Ehm, wrong button');

    $mdDialog.show({
      templateUrl: 'templates/dialogs/delete.html',
      parent: angular.element(document.body),
      targetEvent: ev,
      clickOutsideToClose: false,
      controller: DialogController
    }).then(function() {
      delComp.getData(delete_obj).then(function (response) {
        if (response.status === 200) {
          toaster.pop({
            type: 'success',
            title: success(),
            onShowCallback: function () {
              $state.reload();
            }
          });
        } else {
          toaster.pop({
            type: 'error',
            title: error(),
            onShowCallback: function () {
              $state.reload();
            }
          });
        }
      });
    }, function() {
      console.log("Canceled");
    });
  };

  function DialogController($scope, $mdDialog) {

    $scope.cancel = function() {
      $mdDialog.cancel();
    };

    $scope.confirm = function() {
      $mdDialog.hide();
    };
  }
}]);


mainapp.controller("companiesCtrl", ['$scope', '$rootScope', '$state','fetchComp','toaster', function ($scope, $rootScope, $state, fetchComp, toaster) {
  $scope.now = {
   time: new Date(),
   date: moment()
  };
  let actions = $scope.$resolve.auth;
  actions["action"] = "getcompanies";
  $scope.companies_list = [];
  fetchComp.getData(actions).then(function (response) {
    $scope.companies_list = response.data.data;
  });

  var bookmark;
  $scope.selectedItems = [];
  $scope.limitOptions = [5, 10, 15];

  $scope.options = {
    rowSelection: false,
    multiSelect: true,
    autoSelect: true,
    decapitate: false,
    largeEditDialog: false,
    boundaryLinks: false,
    limitSelect: true,
    pageSelect: true
  };

  $scope.query = {
    order: 'id',
    limit: 5,
    page: 1
  };

  $scope.filter = {
    options: {
      debounce: 500
    }
  };

  // Close search
  $scope.removeFilter = function () {
    $scope.filter.show = false;
    $scope.filter.search = '';

    if($scope.filter.form.$dirty) {
      $scope.filter.form.$setPristine();
    }
  };

  $scope.$watch('query.filter', function (newValue, oldValue) {
    if(!oldValue) {
      bookmark = $scope.query.page;
    }

    if(newValue !== oldValue) {
      $scope.query.page = 1;
    }

    if(!newValue) {
      $scope.query.page = bookmark;
    }
  });

}]);
