////////////////////////////////////////////////////////////////////////
//																																		//
//							Epsilon Card App - Created by eCosmos 			     			//
//                                                                    //
//										www.euacosmos.com						       							//
//																																		//
////////////////////////////////////////////////////////////////////////


mainapp.service('fetchUsers', function($http) {
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

mainapp.service('addToProjectUser', function($http) {
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

mainapp.service('removeFromProjectUser', function($http) {
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

mainapp.service('startWorkinProject', function($http) {
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

mainapp.service('stopWorkinProject', function($http) {
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

mainapp.service('employeeProjects', function($http) {
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

mainapp.service('finalizeWorkinProject', function($http) {
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
