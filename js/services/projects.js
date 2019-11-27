////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Card App - Created by eCosmos 			     			//
//                                                                    //
//										www.euacosmos.com						       							//
//																																		//
////////////////////////////////////////////////////////////////////////


mainapp.service('fetchProjects', function($http) {
	this.getData = function(obj) {
		return $http({
			method  : 'POST',
			url     : appUrl,
			data	  : obj,
			headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
		}).then(function success(data) {
			return data;
		},function errorCallback(response) {
			console.error("Error fetching Projects");
			return null ;
		});
	}
});

mainapp.service('addNewProject', function($http) {
	this.getData = function(obj) {
		return $http({
			method  : 'POST',
			url     : appUrl,
			data	  : obj,
			headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
		}).then(function success(data) {
			return data;
		},function errorCallback(response) {
			console.error("Error adding a new project");
			return null ;
		});
	}
});

mainapp.service('delProj', function($http) {
	this.getData = function(obj) {
		return $http({
			method  : 'POST',
			url     : appUrl,
			data	  : obj,
			headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
		}).then(function success(data) {
			return data;
		},function errorCallback(response) {
			console.error("Error adding a new project");
			return null ;
		});
	}
});


mainapp.service('individualProject', function($http) {
	this.getData = function(obj) {
		return $http({
			method  : 'POST',
			url     : appUrl,
			data	  : obj,
			headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
		}).then(function success(data) {
			return data;
		},function errorCallback(response) {
			console.error("Error adding a new project");
			return null ;
		});
	}
});