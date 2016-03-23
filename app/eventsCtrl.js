
app.controller('eventsCtrl', function ($scope, $rootScope, $filter, $modal, Data) {
    
    Data.get('events').then(function(data){
        $scope.events = data.data;
    });

    $scope.changeEventStatus = function(event){
        event.status = (event.status=="Approved" ? "Pending" : "Approved");
        Data.put("approve/"+event.event_id,{status:event.status});
    };
    $scope.deleteProduct = function(event){
        if(confirm("Are you sure to remove the event")){
            Data.delete("events/"+event.event_id).then(function(result){
                // Data.toast(results);
                Data.get('events').then(function(data){
                    $scope.events = data.data;
                });
            });

        }
    };
    $scope.open = function (p) {
        
        setTimeout(function(){

                var today = getDateTime();

               $('#datetimepicker6').datetimepicker({
                    
                    format: 'YYYY-MM-DD HH:mm:ss',
                    useCurrent: false,
                });

                $('#datetimepicker7').datetimepicker({
                    format: 'YYYY-MM-DD HH:mm:ss',
                    useCurrent: false, //Important! See issue #1075
                });
                if(p){
                    $('#datetimepicker6').data("DateTimePicker").maxDate(p.event_end_datetime);
                    $('#datetimepicker7').data("DateTimePicker").minDate(p.event_start_datetime);
                }else{
                    var minDate = getDateTime();
                    $('#datetimepicker6').data("DateTimePicker").minDate(minDate);
                    $('#datetimepicker7').data("DateTimePicker").minDate(minDate);                    
                }

                $("#datetimepicker6").on("dp.change", function (e) {
                    $('#datetimepicker7').data("DateTimePicker").minDate(e.date);
                });
                $("#datetimepicker7").on("dp.change", function (e) {
                    $('#datetimepicker6').data("DateTimePicker").maxDate(e.date);
                });
        },1000);
        // alert(getDateTime())
        var modalInstance = $modal.open({
          templateUrl: 'partials/eventEdit.html',
          controller: 'eventEditCtrl',
          // size: size,
          resolve: {
            item: function () {
               if (p) {
                    if ($rootScope.userType=='end_usr') {
                        if(p.status=='Approved'){
                            p = {};
                        }
                    }
               }
              return p;
            }
          }
        });
        modalInstance.result.then(function(selectedObject) {
            if(selectedObject.save == "insert"){
                $scope.events.push(selectedObject);
                $scope.events = $filter('orderBy')($scope.events, 'event_id', 'reverse');
            }else if(selectedObject.save == "update"){
                p.event = selectedObject.event;
                p.event_start_datetime = selectedObject.event_start_datetime;
                p.event_end_datetime = selectedObject.event_end_datetime;
            }
        });
            function getDateTime() {
                var now     = new Date(); 
                var year    = now.getFullYear();
                var month   = now.getMonth()+1; 
                var day     = now.getDate();
                var hour    = now.getHours();
                var minute  = now.getMinutes();
                var second  = now.getSeconds(); 
                if(month.toString().length == 1) {
                    var month = '0'+month;
                }
                if(day.toString().length == 1) {
                    var day = '0'+day;
                }   
                if(hour.toString().length == 1) {
                    var hour = '0'+hour;
                }
                if(minute.toString().length == 1) {
                    var minute = '0'+minute;
                }
                if(second.toString().length == 1) {
                    var second = '0'+second;
                }   
                var dateTime = year+'-'+month+'-'+day+' '+hour+':'+minute+':'+second;   
                 return dateTime;
            } 
    };
    
 $scope.columns = [
                    // {text:"Event ID",predicate:"event_id",sortable:true,dataType:"number"},
                    {text:"Event Title",predicate:"event",sortable:true},
                    {text:"Event Start",predicate:"event_start_datetime",sortable:true},
                    {text:"Event End",predicate:"event_end_datetime",sortable:true},
                    {text:"Status",predicate:"status",sortable:true},
                    {text:"Action",predicate:"",sortable:false}
                ];

});


app.controller('eventEditCtrl', function ($scope, $rootScope, $modalInstance, item, Data) {

        $scope.event = angular.copy(item);
        $scope.title = (item) ? 'Edit Event' : 'Add Event';
        $scope.buttonText = (item) ? 'Update Event' : 'Add Event';

        $scope.cancel = function () {
            $modalInstance.dismiss('Close');
        };
        
        $scope.saveProduct = function (event) {

            if(item){
                event={
                    event_id: item.event_id,
                    event:  $("#title").val(),
                    event_start_datetime:$("#datetimepicker6").val(),
                    event_end_datetime:$("#datetimepicker7").val()
                }
            }else{
                event={
                    event:  $("#title").val(),
                    event_start_datetime:$("#datetimepicker6").val(),
                    event_end_datetime:$("#datetimepicker7").val()
                }
            }           

            if(event.event_id > 0){
                Data.put('events/'+event.event_id, event).then(function (result) {
                    if(result.status != 'error'){
                        var x = angular.copy(event);
                        x.save = 'update';
                        $modalInstance.close(x);
                        console.log(result);
                    }else{
                        console.log(result);
                        $scope.msg = result.message;
                    }
                });
            }else{
                event.status = 'Pending';    
                event.user_id = $rootScope.userID;
                Data.post('events', event).then(function (result) {
                    if(result.status != 'error'){
                        var x = angular.copy(event);
                        x.save = 'insert';
                        x.event_id = result.data;
                        $modalInstance.close(x);
                        console.log(result);
                    }else{
                        console.log(result);
                        $scope.msg = result.message;
                    }
                });
            }
        };
});
