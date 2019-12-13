////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Card App - Created by eCosmos 			     			//
//                                                                    //
//										www.euacosmos.com						       							//
//																																		//
////////////////////////////////////////////////////////////////////////

mainapp.controller("addProjCtrl", ['$scope', '$state' , 'addNewProject', 'toaster', function ($scope,$state, addNewProject, toaster) {
  $scope.formData = {};
  
  $scope.paymentSet = {
    fields: []
  };

  let actions = $scope.$resolve.auth;

  $scope.addpayment = function () {
    var obj = {};
    $scope.paymentSet.fields.push('');
  };

  $scope.removePayment = function (index) {
    $scope.paymentSet.fields.splice(index,1);
  };

  // Add New Project
  $scope.addproject = function () {
    actions["action"] = "addproject";
    $scope.formData["company_id"] = parseInt($scope.formData["company_id"], 10);
    var returnedObject = Object.assign(actions, $scope.formData);
    addNewProject.getData(returnedObject).then(function (response) {
      if (response.status === 200) {
        toaster.pop({
          type: 'success',
          title: success('project'),
          onShowCallback: function () {
            $state.go("app.project");
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

mainapp.controller("projectsActionsCtrl", ['$scope', '$rootScope', '$state','$mdDialog', 'toaster', 'editCompanyServ', 'delProj', function ($scope, $rootScope, $state, $mdDialog,toaster, editCompanyServ, delProj) {
  let originatorEv;
  this.openMenu = function($mdMenu, ev) {
    originatorEv = ev;
    $mdMenu.open(ev);
  };


  this.view_project = function (project) {
    $state.go("app.project.view", 
      {
        name: project.full_project_name, 
        id: project.case_number,
        start_date: project.start_date,
        end_date: project.end_date
      });
  };

  this.edit_project = function (project) {
    editCompanyServ.addProduct(project);
    $state.go("app.project.edit");
  };

  this.delete_project = function (case_number, company_id, ev) {
    let actions = $scope.$resolve.auth;
    actions["action"] = "deleteproject";
    actions["case_number"] = case_number;
    actions["company_id"] = company_id;

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
      delProj.getData(actions).then(function (response) {
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

mainapp.controller("editProjCtrl", ['$scope', '$state','addNewComp','editCompanyServ','toaster', function ($scope,$state, addNewComp,editCompanyServ,toaster) {
  $scope.formData = editCompanyServ.getProducts();
  let actions = $scope.$resolve.auth;

  $scope.addproject = function () {
    actions["action"] = "addproject";
    let returnedObject = Object.assign(actions, $scope.formData);

    addNewComp.getData(returnedObject).then(function(response) {
      if (response.status === 200) {
        toaster.pop({
          type: 'success',
          title: success(),
          onShowCallback: function () {
            $state.go("app.project");
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

mainapp.controller("projectsCtrl", ['$scope', '$state','fetchProjects', function ($scope, $state, fetchProjects) {
  $scope.now = {
   time: new Date(),
   date: moment()
  };
  let actions = $scope.$resolve.auth;
  let obj_fetch_comp = actions;
  obj_fetch_comp["action"] = "getprojects";
  $scope.companies_list = [];
  fetchProjects.getData(obj_fetch_comp).then(function (response) {
    $scope.projects_list = response.data.data;
  });

  var bookmark;
  $scope.selectedItems = [];
  $scope.limitOptions = [5, 10, 15];

  $scope.options = {
    rowSelection: false,
    multiSelect: false,
    autoSelect: true,
    decapitate: false,
    largeEditDialog: false,
    boundaryLinks: false,
    limitSelect: true,
    pageSelect: true
  };

  $scope.query = {
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


mainapp.controller("individualProjectCtrl", [
  '$scope', 
  '$state',
  '$timeout',
  'individualProject',
  'toaster',
  'fetchUsers', function ($scope,$state,$timeout,individualProject, toaster, fetchUsers) {
    
    $scope.projectName = $state.params.name;
    $scope.projectStarts = $state.params.start_date;
    $scope.projectEnds = $state.params.end_date;
    $scope.projectCase = $state.params.id;
    let actions = $scope.$resolve.auth;
    $scope.expensesGenform = [{}];
    $scope.expensesForm = [{}];

    let projectObj = actions;
    projectObj["action"] = "getprojectinfo";
    projectObj["case_number"] = $state.params.id;
    projectObj["start_date"] = $state.params.start_date;
    projectObj["end_date"] = $state.params.end_date;
        
    $scope.currentProject = {};
    individualProject.getData(projectObj).then(function(response) {
      if (response.status === 200) {
        $scope.currentProject = response.data.data;
        $scope.personnel = response.data.data.personnel_detail;
        $scope.generalExpenses = response.data.data.expense_detail;
        console.log(response.data.data);
        
        let fetchingUsers = $scope.$resolve.auth;
        fetchingUsers["action"] = "getavailableusers";
        fetchUsers.getData(fetchingUsers).then(function(response) {
          $scope.personnel = $scope.personnel.map(elem => {
            let name = elem.name;
            let final = response.data.data.find(elem => {
              if (elem.name == name) {
                return elem.uid;
              }
            });
            elem.userID = final.uid;
            return elem;
          });          
        }); 
      }
    
    });
    

    // Adding Fns

    $scope.addEmployees = function(e) {
      let obj = actions;
      obj.action = "addpersonnelexpensetoproject";
      obj["case_number"] = $scope.projectCase;
      obj.expenses = $scope.expensesForm;
      
      individualProject.getData(obj).then(function(response) {
        if (response.status === 200) {
          toaster.pop({
            type: 'success',
            title: success('added'),
            onShowCallback: function () {
              $timeout(function () {
                $state.reload();
              },1000)
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

    $scope.addgenExpenses = function(e) {
      let obj = actions;
      obj.action = "addexpensetoproject";
      obj["case_number"] = $scope.projectCase;
      obj.expenses = $scope.expensesForm;
      individualProject.getData(obj).then(function(response) {
        if (response.status === 200) {
          toaster.pop({
            type: 'success',
            title: success('added'),
            onShowCallback: function () {
              $timeout(function () {
                $state.reload();
              },1000)
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
