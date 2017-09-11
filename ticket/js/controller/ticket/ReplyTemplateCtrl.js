'use strict';

// Button create ticket
angular.module('app').controller('ReplyTemplateCtrl', ['$scope', '$modal', function($scope, $modal) {
    
    $scope.openModalManagerTemplate = function () {

        var modalInstance = $modal.open({
            animation: $scope.animationsEnabled,
            templateUrl: 'tpl/ticket/modalListTemplate.html',
            controller: function($scope, $modalInstance, ReplyTemplate) {
                $scope.listData = [];
                $scope.stateLoading = false;
                $scope.page = function(page) {
                    $scope.params = {currentPage: page, itemsPerPage: 10};
                    $scope.listData = [];
                    $scope.stateLoading = true;
                    ReplyTemplate.list($scope.params)
                        .success(function(response) {
                            $scope.stateLoading = false;
                            if(!response.error) {
                                $scope.listData = response.data;
                                $scope.totalItems  = response.total;
                                $scope.item_stt = $scope.params.itemsPerPage*($scope.params.currentPage - 1);
                            } else {
                                $scope.totalItems = 0;
                            }
                        });
                };
                $scope.page(1);

                $scope.openEdit = function(id) {
                    if(id == undefined) {
                        id = "";
                    }
                    var modalInstance = $modal.open({
                        animation: $scope.animationsEnabled,
                        templateUrl: 'tpl/ticket/formEditTemplate.html',
                        controller: function($scope, $modalInstance, ReplyTemplate, Ticket, id, Base) {
                            $scope.replyTemplate = {};
                            $scope.listType = [];
                            $scope.onLoad = function() {
                                if(id != "" && id != undefined) {
                                    ReplyTemplate.load(id)
                                        .success(function(response) {
                                            if(!response.error) {
                                                $scope.replyTemplate = response.data;
                                            }
                                        });
                                }
                                Base.list_type_case()
                                    .success(function(response) {
                                        if(!response.error) {
                                            $scope.listType = response.data;
                                        }
                                    });
                            };
                            if(id == "" || id == undefined) {
                                id = 0;
                            }
                            $scope.save = function() {
                                ReplyTemplate.save(id, $scope.replyTemplate)
                                    .success(function(response) {
                                        $scope.replyTemplate.id = response.id; 
                                        $scope.replyTemplate.type_id = $scope.replyTemplate.type_id; 
                                        $scope.replyTemplate.type = response.type; 
                                        $scope.replyTemplate.isNew = (id == "") ? true : false;
                                        $modalInstance.close($scope.replyTemplate);
                                    });
                            };

                            $scope.close = function () {
                                $modalInstance.dismiss('cancel');
                            };
                        },
                        resolve: {
                            id: function () {
                              return id;
                            }
                        }
                    });
                    modalInstance.result.then(function (result) {
                        if(result.isNew) {
                            $scope.listData.push(result);
                            $scope.totalItems++;
                        } else {
                            var data = [];
                            $scope.listData.forEach(function(oneItem) {
                                if(oneItem.id != result.id) {
                                    data.push(oneItem);
                                } else {
                                    data.push(result);
                                }
                            });
                            $scope.listData = data;
                        }
                    }, function () {
                    });
                };

                $scope.close = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            size: 'lg'
        });
    };
}]);