'use strict';

//Api Key
angular.module('app').controller('ApiKeyCtrl', ['$scope', '$http', '$state', '$window', 'toaster', 'User', '$modal',
 	function($scope, $http, $state, $window, toaster, User, $modal) {
    // config
    $scope.list_data    = {};
    $scope.list_api     = {};
    $scope.wating       = true;

    // get key
        User.load_key().then(function (result) {
            if(!result.error){
                $scope.list_data     = result.data.data;
            }
            $scope.wating       = false;
        });
        
    //action
    
        $scope.add_key  = function(){
            User.create_key().then(function (result) {
                if(!result.error){
                    $scope.list_data.unshift(result.data.data);
                }
            });
            return;
        }
    
        $scope.edit_key    = function(item){
            var data = {'active': item.active}
            User.edit_key(data,item.id);
            return;
        }

        $scope.edit_auto    = function(item){
            var data = {'auto': item.auto}
            User.edit_auto(data,item.id);
            return;
        }
        
        $scope.onTourEnd = function(){
            $state.go('app.config.user');
        }

        //list webhook
        User.load_link_api().then(function (result) {
            if(!result.error){
                $scope.list_api     = result.data.data;
            }
            $scope.wating       = false;
        });
        //list status
        
        //add api
        $scope.add_api = function(){
            var modalInstance = $modal.open({
            templateUrl: 'tpl/config/modal.add_webhook.html',
            controller: function($scope, $modalInstance, $http) {
                $scope.submit_loading = false;
                $scope.accept = function (frm){
                    $scope.submit_loading = true;
                    User.add_link_api(frm).then(function (result) {
                        if(!result.error){
                            $scope.list_api.unshift(result.data.data);
                        }
                    });
                }

                $scope.cancel = function() {
                    $modalInstance.dismiss('cancel');
                };
            },
                size: 'md'
                // resolve: {
                //     id: function () {
                //         return id; 
                //     }
                // }
            });
        }



    }
    
    
]);
