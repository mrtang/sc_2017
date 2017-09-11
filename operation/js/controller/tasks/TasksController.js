'use strict';

angular.module('app')
    .controller('TasksDetailController', ['$scope', '$rootScope',  '$http', '$state', '$stateParams', '$window', '$timeout', 'toaster', 'Merchant',  '$modal','Base', 'Config', 'Tasks', 'PhpJs',
    function($scope, $rootScope,  $http, $state, $stateParams,  $window, $timeout, toaster, Merchant, $modal, Base, Config, Tasks, PhpJs) {
        
        $scope.stateLoading  = false;
        $scope.list_data     = [];
        $scope.list_comment  = [];
        $scope.list_user     = {};
        $scope.count_state   = {};

        $scope.moment       = moment;
        $scope.defaultState = $stateParams.state;


        $scope.totalItems   = 0;
        $scope.currentPage  = 1;
        $scope.item_page    = 20;
        $scope.maxSize      = 5;

        $scope.id = $stateParams.id || 0;
        
        

        $scope.list_state = [
            {
                "key" : "NOT_STARTED",
                "name": "Chưa bắt đầu",
                "icon": "fa-stop"
            },
            {
                "key" : "IN_PROCESS",
                "name": "Đang làm",
                "icon": "fa-play"
            },
            {
                "key" : "SUCCESS",
                "name": "Hoàn thành",
                "icon": "fa-check"
            },
            {
                "key" : "PAUSED",
                "name": "Dừng lại",
                "icon": "fa-pause"
            }
        ];

        $scope.stateInfo = function (state){
            for (var i = $scope.list_state.length - 1; i >= 0; i--) {
                if(state == $scope.list_state[i].key){
                    return $scope.list_state[i];
                }
            }
            
        }
        



        $scope.setPage = function (page){
            $scope.currentPage = page;
            $scope.load($scope.defaultState);
        }

        $scope.load = function (state, extendParams){

            $scope.defaultState = state;
            $scope.stateLoading = true;
            
            var loadParams = {
                state      : state,
                page       : $scope.currentPage,
                item_page  : $scope.item_page,
                category   : $stateParams.category || ""
            };
            
            if(extendParams && angular.isObject(extendParams)){
                loadParams = angular.extend(loadParams, extendParams);
            }


            $scope.list_data = [];
            $http.get(ApiPath + 'tasks', {params: loadParams}).success(function (resp){
                $scope.stateLoading = false;
                if(!resp.error){
                    $scope.list_data  = resp.data;

                    $scope.totalItems = resp.total;
                }
            })
        }


        $scope.item = {};
        $scope.show = function (id){
            $scope.item         = {};
            $scope.list_comment = [];
            $scope.list_user    = {};
            $scope.stateLoading = true;
            $http.get(ApiPath + 'tasks/show/'+ id).success(function (resp){
                $scope.stateLoading = false;
                if(!resp.error){
                    $scope.item         = resp.data;
                    $scope.list_comment = resp.data.comments;
                    $scope.list_user    = resp.users;
                }
            })   
        }


        

        $scope.isExpired = function (time_estimate){
            if(moment(time_estimate * 1000).unix() <= moment().unix()){
                return true;
            }
            return false;
        }

        $scope.saveField = function (item, field, value){
            return $http.post(ApiPath + 'tasks/update-field', {id: item.id, field: field, value: value}).success(function (resp){
                if(resp.error){
                    toaster.pop('warning', 'Thông báo', 'Cập nhật thành công');
                }
            });
        }


        $scope.getCategory = function (){
            $http.get(ApiPath + 'tasks/task-category').success(function (resp){
                if(!resp.error){
                    $scope.list_category = resp.data;
                }
            })
        }


        $scope.commentContent = "";
        $scope.commentSending = false;
        $scope.InsertComment = function (content){
            
            content               = angular.copy(content);
            $scope.commentContent = "";
            $scope.commentSending = true;
            $http.post(ApiPath + 'tasks/create-comment/' + $scope.id, {
                content: content
            }).success(function (resp){
                $scope.commentSending = false;
                if(!resp.error){
                    $timeout(function (){
                        content = ""
                    })
                    
                    $scope.list_comment.unshift(resp.data);
                }else {
                    toaster.pop('warning', 'Thông báo', resp.error_message);
                }

            })
        }

        $scope.genProfileImage = function (email){
            return "http://www.gravatar.com/avatar/"+PhpJs.md5(email)+"?s=80&d=mm&r=g"
        }

        if(!$scope.id){
            $scope.load($stateParams.state);
        }else {
            $scope.show($stateParams.id)
        }

         $scope.getCategory();

        
    }
]);

angular.module('app').controller('TasksController', ['$scope', '$rootScope',  '$http', '$state', '$stateParams', '$window', 'toaster', 'Merchant',  '$modal','Base', 'Config', 'Tasks',
    function($scope, $rootScope,  $http, $state, $stateParams,  $window, toaster, Merchant, $modal, Base, Config, Tasks) {
        
        
        $scope.count_state   = {};
        $scope.list_category = [];
        $scope.newCategory   = {};

        $scope.currentState  = $stateParams.state;

        $scope.category_id = $stateParams.category || "";


        $scope.list_state = [
            {
                "key" : "NOT_STARTED",
                "name": "Chưa bắt đầu",
                "icon": "fa-stop"
            },
            {
                "key" : "IN_PROCESS",
                "name": "Đang làm",
                "icon": "fa-play"
            },
            {
                "key" : "SUCCESS",
                "name": "Hoàn thành",
                "icon": "fa-check"
            },
            {
                "key" : "PAUSED",
                "name": "Dừng lại",
                "icon": "fa-pause"
            }
        ];

        $scope.stateInfo = function (state){
            for (var i = $scope.list_state.length - 1; i >= 0; i--) {
                if(state == $scope.list_state[i].key){
                    return $scope.list_state[i];
                }
            }
            
        }

        $scope.gotoState = function (state){
            $scope.currentState = state;
            $scope.loadCountState(undefined);
        }

        $scope.AddTask = function (){
            Tasks.openModal([]).result.then(function (newTasks){
                if(newTasks){
                    $scope.AppendNewTask(newTasks);
                }
            })
        }

        $scope.addCategory  = function (newCategory){
            var data            = angular.copy(newCategory);
            $scope.newCategory  = {};

            $http.post(ApiPath + 'tasks/create-category', data).success(function (resp){
                if(!resp.error){
                    $scope.list_category.unshift(data);
                }
            })
        }

        $scope.loadCountState = function (category){
            var params = {
                state      : $stateParams.state,
                category   : (category !== undefined && category !== null) ?  category : $stateParams.category
            };
            $http.get(ApiPath + 'tasks/count-state', {
                params: params
            }).success(function (resp){
                $scope.stateLoading    = false;
                if(!resp.error){
                    $scope.count_state = resp.data;
                }
            })
        }   

        $scope.getCategory = function (){
            $http.get(ApiPath + 'tasks/task-category').success(function (resp){
                if(!resp.error){
                    $scope.list_category = resp.data;
                }
            })
        }

        $scope.selectCategory = function (currentCategory){
            

            if($stateParams.category == currentCategory){
                $scope.category_id = "";
                $state.go('tasks.list.detail', {state: $scope.currentState, id: "", category: ""});
            }else {
                $state.go('tasks.list.detail', {state: $scope.currentState, id: "", category: currentCategory});
                $scope.category_id = currentCategory;
            }
            $scope.loadCountState($scope.category_id);
        }

        $('.demo').each( function() {
            //
            // Dear reader, it's actually very easy to initialize MiniColors. For example:
            //
            //  $(selector).minicolors();
            //
            // The way I've done it below is just for the demo, so don't get confused
            // by it. Also, data- attributes aren't supported at this time...they're
            // only used for this demo.
            //
            $(this).minicolors({
                control: $(this).attr('data-control') || 'hue',
                defaultValue: $(this).attr('data-defaultValue') || '',
                format: $(this).attr('data-format') || 'hex',
                keywords: $(this).attr('data-keywords') || '',
                inline: $(this).attr('data-inline') === 'true',
                letterCase: $(this).attr('data-letterCase') || 'lowercase',
                opacity: $(this).attr('data-opacity'),
                position: $(this).attr('data-position') || 'bottom left',
                change: function(value, opacity) {
                    if( !value ) return;
                    /*if( opacity ) value += ', ' + opacity;*/
                    if( typeof console === 'object' ) {
                        $scope.$apply(function (){
                            $scope.newCategory['color'] = value;
                        })
                        
                    }
                },
                theme: 'bootstrap'
            });

        });

        $scope.getCategory();
        $scope.loadCountState();
    }
]);
