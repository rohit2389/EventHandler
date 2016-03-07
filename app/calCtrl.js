
app.controller('calCtrl', function ($scope, $modal, $filter, Data) {
$scope.calEvents=[];

    Data.get('events').then(function(data){
        for(var i=0;i<data.data.length;i++){
            $scope.calEvents.push({
                id:data.data[i].event_id,
                title:data.data[i].event,
                start:data.data[i].event_start_datetime,
                end:data.data[i].event_end_datetime

            })
        }
     
$(document).ready(function() {    
        $('#calendar').fullCalendar({
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            eventMouseover: function(calEvent, jsEvent, view) {
                Data.get("events/"+calEvent.id).then(function(data){
                    $scope.ev = data.data;
                });
                
            },            

            selectHelper: true,
            lang: 'en',
            eventLimit: true, // allow "more" link when too many events
            events: $scope.calEvents
        });
        
    });

    $('td.fc-event-container').hover(function(event) {
        $('div#pop-up').show();
        $("div#pop-up").css('top', event.pageY).css('left', event.pageX);
        },function(){
        $('div#pop-up').hide();
    });
    
    $('td.fc-event-container').mousemove(function(e) {
        $("div#pop-up").css('top', e.pageY).css('left', e.pageX);
    });


    });


});
