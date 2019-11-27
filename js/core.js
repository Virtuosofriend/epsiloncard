////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Reporting Tool - Created by eCosmos						//
//                                                                    //
//													www.euacosmos.com													//
//																																		//
////////////////////////////////////////////////////////////////////////

/*****************
Global Settings
*****************/

//const apiURL = "http://5.189.185.194/epsiloncard/authenticate/api/v1/session";
const apiURL = "authenticate/api/v1/session";
//const serviceBase = 'http://5.189.185.194/epsiloncard/authenticate/api/v1/';
const serviceBase = 'authenticate/api/v1/';
//const appUrl = "http://5.189.185.194/epsiloncard/service/service.php";
const appUrl = "service/service.php";

var mainapp = angular.module('mainapp', ['ui.router', 'angularMoment', 'ngMaterial', 'ngMessages', 'moment-picker', 'md.data.table', 'toaster']);

mainapp.config(['$compileProvider', function($compileProvider) {
  $compileProvider.debugInfoEnabled(false);
}]);

mainapp.config(function($stateProvider, $urlRouterProvider) {
  $stateProvider
    .state("login", {
      url: "login/",
      templateUrl: "templates/login.html",
      controller: "loginCtrl"
    })
    .state("app", {
      url: "/",
      views: {
        'header': {
          templateUrl: "templates/layout/header.html",
          controller: "headerCtrl"
        },
        "content": {
          templateUrl: "templates/layout/content.html"
        },
        "container@app": {
          templateUrl: "templates/dashboard.html",
        },
        "footer": {
          templateUrl: 'templates/layout/footer.html'
        }
      },
      resolve: {
        auth: function(fetchUserLogin, $rootScope, $state) {
          return fetchUserLogin.get('session').then(function(results) {
            
            if (results.uid) {
              $rootScope.user = results;
              $rootScope.user.uid = +results.uid;
      
              // $rootScope.actions = {
              //   "type": results.type,
              //   "user_id": +results.uid,
              //   "session_id": results.session_id
              // };

              let actions = {
                "type": results.type,
                "user_id": +results.uid,
                "session_id": results.session_id
              };
              return actions;
            } else {
              $rootScope.user = results;
              $state.go('login');
            }
          });
        }
      }
    })
    .state("app.users", {
      url: "users/",
      views: {
        "container@app": {
          templateUrl: "templates/users.html",
          controller: "usersCtrl"
        }
      }
    })
    .state("app.project", {
      url: "projects/",
      views: {
        "container@app": {
          templateUrl: "templates/projects.html",
          controller: "projectsCtrl"
        }
      }
    })
    .state("app.project.view", {
      url: "projects/:name/case=:id/start_date=:start_date&end_date=:end_date/view",
      views: {
        "container@app": {
          templateUrl: "templates/project_standalone.html",
          controller: "individualProjectCtrl"
        },
        "projectContainer@app.project.view": {
          templateUrl: "templates/projects/index.html"
        }
      },
    })
    .state("app.project.view.personnelExpenses", {
      views: {
        "projectContainer@app.project.view": {
          templateUrl: "templates/projects/personnel_expenses.html"
        }
      }
    })
    .state("app.project.view.generalExpenses", {
      views: {
        "projectContainer@app.project.view": {
          templateUrl: "templates/projects/general_expenses.html"
        }
      }
    })
    .state("app.project.new", {
      url: "project/new",
      views: {
        "container@app": {
          templateUrl: "templates/addnew.html",
          controller: "addProjCtrl"
        }
      }
    })
    .state("app.project.edit", {
      url: "project/edit",
      views: {
        "container@app": {
          templateUrl: "templates/projects_edit.html",
          controller: "editProjCtrl"
        }
      }
    })
    .state("app.companies", {
      url: "companies/list",
      views: {
        "container@app": {
          templateUrl: "templates/companies.html",
          controller: "companiesCtrl"
        }
      }
    })
    .state("app.companies.new", {
      url: "companies/addnew",
      views: {
        "container@app": {
          templateUrl: "templates/companies_addnew.html",
          controller: "addCompCtrl"
        }
      }
    })
    .state("app.companies.edit", {
      url: "companies/edit",
      views: {
        "container@app": {
          templateUrl: "templates/companies_edit.html",
          controller: "editCompCtrl"
        }
      }
    })
    .state("otherwise", {

      url: "/login/",
      views: {
        "content": {
          templateUrl: "templates/login.html",
          controller: "loginCtrl"
        },
      }
    });

  $urlRouterProvider.otherwise("/login/");

});

/*
mainapp.run(function($rootScope, $transitions, $state, fetchUserLogin) {
  $rootScope.user = {};

  $transitions.onStart({}, function(transition) {
    $rootScope.user.authenticated = false;
    fetchUserLogin.get('session').then(function(results) {
      if (results.uid) {
        $rootScope.user.authenticated = true;
        $rootScope.user.id = +results.uid;
        $rootScope.user.name = results.name;
        $rootScope.user.email = results.email;
        $rootScope.user.session = results.session_id;
        $rootScope.user.type = results.type;

        $rootScope.actions = {
          "type": results.type,
          "user_id": +results.uid,
          "session_id": results.session_id
        };
        
        // if (transition.to().name == "otherwise") {
        //   $state.go("app", {user_id: results.uid});
        // }
        
      } else {
        $rootScope.user.authenticated = false;
        $rootScope.user.id = 0;
        $rootScope.user.name = "";
        $rootScope.user.email = "";
        var nextUrl = transition.to().name;

      }
    });
  });

});
*/

mainapp.config(function($mdIconProvider) {
  $mdIconProvider
    .defaultFontSet('FontAwesome')
    .fontSet('fa', 'FontAwesome');
});

// Functions

function success(text) {
  if (text === 'project') {
    return "Project is added";
  } else if (text === 'company') {
    return "Company is created";
  } else if( text === 'user_removed_proj') {
    return "User is removed from project";
  } else if ( text === 'user_start_work') {
    return "Thanks! Have a good day.";
  } else if ( text === 'user_finished_work' ) {
    return "Great! Get some rest now!";
  } else {
    return "Your changes are saved!";
  }
}

function error() {
  return "Oops! Something went terribly wrong! Try again.";
}

function time() {
  return now = moment.utc();
}
