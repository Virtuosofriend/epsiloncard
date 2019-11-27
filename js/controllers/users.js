////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Card App - Created by eCosmos 			     			//
//                                                                    //
//										www.euacosmos.com						       							//
//																																		//
////////////////////////////////////////////////////////////////////////

mainapp.controller("getAvailableUsersCtrl", [
  '$scope',
  'fetchUsers',
  function($scope, fetchUsers) {
    let actions = $scope.$resolve.auth;
    actions["action"] = "getavailableusers";

    fetchUsers.getData(actions).then(function (response) {
      $scope.users = response.data.data;      
    });
  }]);

mainapp.controller("usersCtrl", 
  [ '$scope', 
    '$rootScope', 
    '$state',
    'fetchUsers', 
    'addToProjectUser',
    'fetchProjects',
    'toaster',
    '$timeout',
    'removeFromProjectUser', 
    function ($scope, $rootScope, $state, fetchUsers, addToProjectUser, fetchProjects,toaster,$timeout,removeFromProjectUser) {
    
    let actions = $scope.$resolve.auth;
    
    var vm = this;
    $scope.now = {
    time: new Date(),
    date: moment()
    };

  // Projects list
  $scope.projectNames = [];
  $scope.chips = {
    readonly: false,
    removable: true,
    enable_edit: false
  };

  let objectFetchUsers = actions;
  objectFetchUsers["action"] = "getavailableemployees";
  
  let assignProjectsToUsers = (userID) => {
    let objectFetchProjects = actions;
    objectFetchProjects["action"] = "getemployeeprojectsbyadmin";
    objectFetchProjects["request_user_id"] = userID;
    console.log(userID);
    
    fetchUsers.getData(objectFetchProjects).then(function (response) {
      console.log(response.data.data);
    });
  };

  fetchUsers.getData(objectFetchUsers).then(function (response) {
    $scope.users = response.data.data;
    
    for(let user of $scope.users) {
      assignProjectsToUsers(user.uid);
    }
  });




  // Checked items
  $scope.selectedUsers = [];
  $scope.selectedItem = function (item) {
    $scope.selectedUsers.push(item);
  };

  $scope.unselectItem = function (item) {
    var found = $scope.selectedUsers.find(function(element) {
      return element.uid == item.uid;
    });
    $scope.selectedUsers.splice($scope.selectedUsers.indexOf(found), 1);
  };

  var bookmark;
  $scope.selectedItems = [];
  $scope.limitOptions = [5, 10, 15];

  $scope.options = {
    rowSelection: true,
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

  // Action on users
  $scope.enable_second_menu = false;
  $scope.optionsUsers = function (value) {
    if (value == 'add_project') {
      $scope.enable_second_menu = true;
    }
  };



  // Submit Button Actions
  var usrTmpProjArray = [];
  $scope.$on("projectSelection", function (ev,args) {
    usrTmpProjArray.length = 0;
    usrTmpProjArray.push(args.project);
  });

  $scope.submitbtn = function () {
    let obj = actions;
    obj["action"] = "assignemployeetoproject";
    obj["project_id"] = usrTmpProjArray[0].case_number;
    for (var i = 0; i < $scope.selectedUsers.length; i++) {
      obj["employee_id"] = $scope.selectedUsers[i].uid;
      addToProjectUser.getData(obj).then(function (response) {
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
    }
  };

  // Remove Employee from project
  // and chips options



  $scope.removeEmployee = function (chip, index, event, uid, project_id) {
    let obj = actions;
    obj["action"] = "removeemployeefromproject";
    obj["employee_id"] = uid;
    obj["project_id"] = project_id;

    removeFromProjectUser.getData(obj).then(function (response) {
      if (response.status === 200) {
        toaster.pop({
          type: 'success',
          title: success('user_removed_proj'),
          onShowCallback: function () {
            $timeout(function () {
              $state.reload();
            },2000)
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

mainapp.controller("searchProjCtrl", ['$scope', '$rootScope', '$element', 'fetchProjects', function ($scope, $rootScope,$element,fetchProjects) {
  var vm = this;
  vm.searchTerm = "";

  let actions = $scope.$resolve.auth;
  actions["action"] = "getprojects";
  console.log(actions);
  
  fetchProjects.getData(actions).then(function (response) {
    console.log(response.data);
    
    vm.availableItems = response.data.data;
  });

  vm.selected = null;
  $element.find('input').on('keydown', function(ev) {
    ev.stopPropagation();
  });

  vm.clear = function(){
    vm.selected = null;
  }

  vm.emitEvent = function () {
    $scope.$emit("projectSelection", {project: vm.selected});
  }

}]);


mainapp.controller("userShift", ['$scope', '$rootScope', 'startWorkinProject','stopWorkinProject','toaster', 'employeeProjects','finalizeWorkinProject', '$state', '$timeout', function ($scope, $rootScope, startWorkinProject, stopWorkinProject,toaster,employeeProjects,finalizeWorkinProject, $state, $timeout) {
  $scope.work = false;
  $scope.message = "";
  $scope.allocate = false;
  $scope.formData = {};
  $scope.total_time = { "hours": 0, "minutes": 0 };
  $scope.errormessage = false;

  $scope.start = function () {
    var obj = $rootScope.actions;
    obj["action"] = "startwork";
    startWorkinProject.getData(obj).then(function (response) {
      if (response.data.status === "success") {
        obj["work_day_id"] = response.data.work_day_id;
          toaster.pop({
            type: 'success',
            title: success('user_start_work'),
            onShowCallback: function () {
              $scope.work = !$scope.work;
            }
          });
      }
    });
  };

  $scope.stop = function () {
    var obj = $rootScope.actions;
    obj["action"] = "checkstartemployeework";
    stopWorkinProject.getData(obj).then(function (response) {
      if (response.data.status === "success") {
        var start_date = response.data.data.start_date;
        var day = response.data.data.id;
        var ending_date = time();
        ending_date = ending_date.format("YYYY-MM-DD hh:mm:ss");
        $scope.allocate_time = {
          //"hours": moment(ending_date).hour() - moment(start_date).hour(),
          //"minutes": moment(ending_date).minute() - moment(start_date).minute()
          "hours": 6,
          "minutes": 44
        };
        $scope.remaining_time = {
          "hours": $scope.allocate_time.hours - 0,
          "minutes": $scope.allocate_time.minutes - 0,
        };
        $scope.work = !$scope.work;
        $scope.allocate = !$scope.allocate;
        obj["action"] = "getemployeeprojects";
        employeeProjects.getData(obj).then(function (response) {
          $scope.employee_projects = response.data.data;

          $scope.finalizeday = function () {
            var tmp = Object.values($scope.formData);
            obj["work_projects"] = tmp;
            obj["action"] = "endwork";
            obj["end_date"] = ending_date;

            var total = {
              "hours": 0,
              "minutes": 0
            }
            for(var key in $scope.formData) {
              for (var value in $scope.formData[key]) {
                if (value === "hours") {
                  total.hours += Number($scope.formData[key].hours);
                } else if (value === "minutes") {
                  total.minutes += Number($scope.formData[key].minutes);
                }
              }
            }

            if (total.hours == $scope.allocate_time.hours && total.minutes == $scope.allocate_time.minutes) {
              finalizeWorkinProject.getData(obj).then(function (response) {
                if (response.data.status === "success") {
                  toaster.pop({
                    type: 'success',
                    title: success('user_finished_work'),
                    onShowCallback: function () {
                      $timeout(function () {
                        $state.reload()
                      }, 1000);
                    }
                  });
                }
              });
            } else {
              $scope.errormessage = !$scope.errormessage;
            }
          };

        });
      }
    });
  };


  $scope.checkthehours = function (id,data) {
    $scope.errormessage = false;
    var total = 0;
    var keys = [];
    for(var key in $scope.formData) {
      keys.push(key);
    }

    for (var i=0; i<keys.length; i++) {
      if ($scope.formData[keys[i]].hours) {
        total += $scope.formData[keys[i]].hours;
      }
    }
    $scope.total_time.hours = total;
  };

  $scope.checktheminutes = function (id,data) {
    $scope.errormessage = false;
    var total = 0;
    var keys = [];
    for(var key in $scope.formData) {
      keys.push(key);
    }

    for (var i=0; i<keys.length; i++) {
      if ($scope.formData[keys[i]].minutes) {
        total += $scope.formData[keys[i]].minutes;
      }
    }
    $scope.total_time.minutes = total;
  };


  function changetomins(hrs,mins) {
    mins += hrs*60;
    return mins;
  }

}]);
