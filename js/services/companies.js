////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Card App - Created by eCosmos 			     			//
//                                                                    //
//										www.euacosmos.com						       							//
//																																		//
////////////////////////////////////////////////////////////////////////

mainapp.service('fetchComp', function($http) {
	this.getData = function(obj) {
		return $http({
			method  : 'POST',
			url     : appUrl,
			data	  : obj,
			headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
		}).then(function success(data) {
			return data;
		},function errorCallback(response) {
			console.log("Error fetching Livescore data");
			return null ;
		});
	}
});


mainapp.service('addNewComp', function($http) {
	this.getData = function(obj) {
		return $http({
			method  : 'POST',
			url     : appUrl,
			data	  : obj,
			headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
		}).then(function success(data) {
			return data;
		},function errorCallback(response) {
			console.log("Error fetching Livescore data");
			return null ;
		});
	}
});



mainapp.factory('editCompanyServ', function() {
  var companies = [];
  var addProduct = function(newObj) {
		companies.length = 0;
    companies.push(newObj);
  };
  var getProducts = function(){
    return companies[0];
  };
  return {
    addProduct: addProduct,
    getProducts: getProducts
  };
});

mainapp.service('delComp', function($http) {
	this.getData = function(obj) {
		return $http({
			method  : 'POST',
			url     : appUrl,
			data	  : obj,
			headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
		}).then(function success(data) {
			return data;
		},function errorCallback(response) {
			console.log("Error fetching Livescore data");
			return null ;
		});
	}
});
