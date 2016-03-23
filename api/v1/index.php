<?php
require '.././libs/Slim/Slim.php';
require_once 'eventHelper.php';
// require_once 'authHelper.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();
$db = new dbHelper();

/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */

$app->post('/register', function() use ($app){ 
        $data = json_decode($app->request->getBody());
        $mandatory = array('name','email','password');
        global $db;
        $db->verifyRequiredParams($data, $mandatory);
        $response = $db->registerMe("users", $data);
        echoResponse(200, $response);

   });

/**
 * User Logout
 * url - /logout
 * method - GET
 */

$app->get('/logout', function(){ 
    global $db;
    $response = $db->destroy_session();    
        echoResponse(200, $response);

   });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app){ 
        $data = json_decode($app->request->getBody());
        $mandatory = array('email','password');
        
        global $db;
        $db->verifyRequiredParams($data, $mandatory);
        $response = $db->checkLogin($data);
        echoResponse(200, $response);

   });

/**
 * Get Session
 * url - /session
 * method - GET
 */
$app->get('/session', function(){         
    if(!isset($_SESSION['api_key']) || (trim($_SESSION['api_key']) == '')) {
        $response['status'] = "error";
        $response['message'] = "session does not exist";
        echoResponse(200, $response);
    }else{
        $response['status'] = "success";
        $response['userID'] = $_SESSION['userID'];
        $response['userName'] = $_SESSION['userName'];
        $response['api_key'] = $_SESSION['api_key'];
        $response['userType'] = $_SESSION['userType'];
        echoResponse(200, $response);
    }

   });

/**
 * User Events
 * url - /events
 * method - get
 */
$app->get('/events', function() { 
    global $db;
    /* if user is End user*/
    if ($_SESSION['userType']=='super_usr') {
        $rows = $db->select("events","event_id,event,event_start_datetime,event_end_datetime,status",array());
    }else if($_SESSION['userType']=='end_usr'){
        $condition = array('user_id'=>$_SESSION['userID']);
        $rows = $db->select("events","event_id,event,event_start_datetime,event_end_datetime,status",$condition,array());
    }
    echoResponse(200, $rows);
});

/**
 * User Event
 * url - /events
 * method - POST
 * params - eventID
 */
$app->get('/events/:event_id', function($event_id) { 
    global $db;
    $condition = array('event_id'=>$event_id);
    $rows = $db->select("events","event_id,event,event_start_datetime,event_end_datetime,status",$condition,array());
    echoResponse(200, $rows);
});

// // Get this_month_events 
// $app->get('/monthevents', function() { 
//     global $db;
//     $rows = $db->monthevents("events");
//     echoResponse(200, $rows);
// });

/**
 * Post an event
 * url - /events
 * method - POST
 * params - event, event start and eevnt end datetime
 */
$app->post('/events', function() use ($app) { 
    $data = json_decode($app->request->getBody());
    $mandatory = array('event','event_start_datetime','event_end_datetime');
    global $db;
    $db ->eventValid($data, $mandatory);
    $db->eventexist("events", $data, $mandatory);
    $rows = $db->insert("events", $data, $mandatory);
    if($rows["status"]=="success"){
         $rows["message"] = "Event added successfully.";
     }
    echoResponse(200, $rows);
});

/**
 * Update event
 * url - /event/event_id
 * method - PUT
 * params - event, event start and eevnt end datetime
 */$app->put('/events/:event_id', function($event_id) use ($app) { 
    $data = json_decode($app->request->getBody());
    $condition = array('event_id'=>$event_id);
    $mandatory = array('event','event_start_datetime','event_end_datetime');
    global $db;
    $db ->checkValid($data, $mandatory);
    $db->checkExistBeforeUpadte("events", $event_id, $data, $mandatory);

    $rows = $db->update("events", $data, $condition, $mandatory);
    if($rows["status"]=="success")
        $rows["message"] = "event updated successfully.";
    echoResponse(200, $rows);
});

/**
 * Approve event
 * url - /approve/event_id
 * method - PUT
 */
$app->put('/approve/:event_id', function($event_id) use ($app) { 
    $data = json_decode($app->request->getBody());  
    $condition = array('event_id'=>$event_id);
    $mandatory = array();
    global $db;
    $rows = $db->update("events", $data, $condition, $mandatory);
    if($rows["status"]=="success")
        $rows["message"] = "event approved successfully.";
    echoResponse(200, $rows);
});

/**
 * Delete event
 * url - /event/event_id
 * method - DELETE
 */
$app->delete('/events/:event_id', function($event_id) { 
    global $db;
    $rows = $db->delete("events", array('event_id'=>$event_id));
    if($rows["status"]=="success")
        $rows["message"] = "Product removed successfully.";
    echoResponse(200, $rows);
});


/**
 * Responnse
 */
function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}

$app->run();
?>