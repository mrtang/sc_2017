'use strict';
angular.module('app')
.controller('PostOfficeActionCtrl', ['$scope', '$modal', '$http', '$state', '$window','$stateParams', 'toaster', 'bootbox', '$timeout',
function($scope, $modal, $http, $state, $window,$stateParams, toaster, bootbox, $timeout) {

    $scope.data = {
        lat: 0,
        lng: 0
    };
	//load courier
    $http({
        url: ApiPath+'courier',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCourier = result.data;
        }
    });
    //load city
    $http({
        url: ApiPath+'city?limit=all',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCity = result.data;
        }
    });
    //load district
    $scope.loadDistrict = function(city_id){
    	$http({
	        url: ApiPath+'district?city_id='+city_id+'&limit=all',
	        method: "GET",
	        dataType: 'json'
	    }).success(function (result, status, headers, config) {
	        if(!result.error){
	            $scope.listDistrict = result.data;
	        }
	    });
    }
    //load ward
    $scope.loadWard = function(district_id){
    	$http({
	        url: ApiPath+'ward/wardcache/'+district_id,
	        method: "GET",
	        dataType: 'json'
	    }).success(function (result, status, headers, config) {
	        $scope.listWard = result.data;
	    });
    }
    // Get info office
    if(parseInt($stateParams.id) > 0){
    	$scope.id = parseInt($stateParams.id);
        $http({
            url: ApiPath+'post-office/show/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.data = result.data;

                if(result.data.lat && result.data.lng){
                    
                    $timeout(function (){
                        setMarker({lat: result.data.lat, lng: result.data.lng})
                        $scope.map.panTo({lat: result.data.lat, lng: result.data.lng});
                    }, 1000)

                }

                //district
                $http({
			        url: ApiPath+'district?city_id='+$scope.data.city_id+'&limit=all',
			        method: "GET",
			        dataType: 'json'
			    }).success(function (result, status, headers, config) {
			        if(!result.error){
			            $scope.listDistrict = result.data;
			        }
			    });
			    //ward
			    $http({
			        url: ApiPath+'ward/wardcache/'+$scope.data.district_id,
			        method: "GET",
			        dataType: 'json'
			    }).success(function (result, status, headers, config) {
			        $scope.listWard = result.data;
			    });
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
        //
        $scope.saveEdit = function(data){
            data.address = document.getElementById('address_input').value;
	        $http({
	            url: ApiPath+'post-office/edit/'+parseInt($stateParams.id),
	            method: "POST",
	            data: data,
	            dataType: 'json'
	        }).success(function (result, status, headers, config) {
	            if(!result.error){
	                toaster.pop('success', 'Thông báo', result.message);
	            }
	            else{
	                toaster.pop('error', 'Thông báo', result.message);
	            }
	        });
	    }
    }
    //save
    $scope.saveData = function(data){
    	data.address = document.getElementById('address_input').value;
        $http({
            url: ApiPath+'post-office/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', result.message);
            }
            else{
                toaster.pop('error', 'Thông báo', result.message);
            }
        });
    }


    $scope.map      = null;
    $scope.marker   = null;
    $scope.initMap  = function (){
        $scope.map  = new google.maps.Map(document.getElementById('map'), {
            center: {lat: 21.007599191785754, lng: 105.8477783203125},
            zoom: 16
        });

        var input = document.getElementById('address_input');

        var autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry) {
              return;
            }
            setMarker(place.geometry.location);
            $scope.map.panTo(place.geometry.location);
            $timeout(function (){
                $scope.data.lat = place.geometry.location.lat();
                $scope.data.lng = place.geometry.location.lng();
            }, 0)
            
        })

        $scope.map.addListener('click', function(event) {
            setMarker(event.latLng);
            $timeout(function (){
                $scope.data.lat = event.latLng.lat();
                $scope.data.lng = event.latLng.lng();
            }, 0)
        });
    }

    var setMarker = function (LatLng){
        if($scope.marker){
                $scope.marker.setMap(null);
                $scope.marker = null;
        }

        if(LatLng){
            $scope.marker = new google.maps.Marker({
                position: LatLng,
                map: $scope.map,
                title: 'Địa điểm bưu cục'
            });
        }
    }

    $timeout(function (){
        $scope.initMap();
    }, 0);






}]);