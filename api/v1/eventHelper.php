<?php
session_start();
require_once 'PassHash.php';
require_once 'config.php'; // Database setting constants [DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD]
class dbHelper {
    private $db;
    private $err;
    function __construct() {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8';
        try {
            $this->db = new PDO($dsn, DB_USERNAME, DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (PDOException $e) {
            $response["status"] = "error";
            $response["message"] = 'Connection failed: ' . $e->getMessage();
            $response["data"] = null;
            //echoResponse(200, $response);
            exit;
        }
    }
    function destroy_session(){
            unset($_SESSION['userName']);
            unset($_SESSION['userType']);
            unset($_SESSION['api_key']);
            $response["status"] = "success";
            $response["message"] = "Your account has been logged out successfully";
            return $response;
    }

    function registerMe($table, $columnsArray) {
            $c = array();
            $v = array();

            foreach ($columnsArray as $key => $value) {
                    $c[] = $key;
                    $v[] = $value;
                }
            $this->validateEmail($v[1]);
            $condition = array('email'=>$v[1]);

            if(!$this->checkExist("users", $condition)){
                $v[2] = PassHash::hash($v[2]);
                $api_key = $this->generateApiKey();
            
                $stmt =  $this->db->prepare("INSERT INTO $table(name,email,password,api_key)VALUES (:name, :email, :password, :api_key)");
                    $stmt->bindParam(':name', $v[0]);
                    $stmt->bindParam(':email', $v[1]);
                    $stmt->bindParam(':password', $v[2]);
                    $stmt->bindParam(':api_key', $api_key);
                $stmt->execute();
                $affected_rows = $stmt->rowCount();
                $lastInsertId = $this->db->lastInsertId();
                $response["status"] = "success";
                $response["message"] = "Your account has been created successfully";
                $response["data"] = $lastInsertId;
                
            }else{
                $response["status"] = "error";
                $response["message"] = 'user already exist with this email';
            }
        return $response;
    }
    function checkLogin($columnsArray){
        try{
                $v = array();

                foreach ($columnsArray as $key => $value) {
                    $v[] = $value;
                }
            $email = $v[0];
            $password = $v[1];
            $stmt = $this->db->prepare("select * from users where email = '".$email."'");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $password_hash = $row['password'];
                $userName = $row['name'];
                $userType = $row['user_type'];
                $api_key = $row['api_key'];
                $id = $row['id'];
            }
            if($password_hash){
                if (PassHash::check_password($password_hash, $password)) {
                        // User password is correct
                        session_regenerate_id();
                        $_SESSION['userID'] = $id;
                        $_SESSION['userName'] = $userName;
                        $_SESSION['userType'] = $userType;
                        $_SESSION['api_key'] = $api_key;
                        session_write_close();
                        $response["status"] = "success";
                        $response["message"] = 'Logged in successfully'; 

                    } else {
                        // user password is incorrect
                        $response["status"] = "error";
                        $response["message"] = 'Incorrect credential';   
                    }
            }else{
                $response["status"] = "error";
                $response["message"] = 'No such user found';            
            }
        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = 'Select Failed: ' .$e->getMessage();
            $response["data"] = null;
        }
        return $response;
    }

    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    function checkExist($table, $where){
        try{
            $a = array();
            $w = "";
            foreach ($where as $key => $value) {
                $w .= " and " .$key. " like :".$key;
                $a[":".$key] = $value;
            }
            $stmt = $this->db->prepare("select * from ".$table." where 1=1 ". $w);
            $stmt->execute($a);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return count($rows);
        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = 'Select Failed: ' .$e->getMessage();
            $response["data"] = null;
        }
    }
    function validateEmail($email) {
        $app = \Slim\Slim::getInstance();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response["status"] = "error";
                $response["message"] = 'Email address is not valid';
                echoResponse(400, $response);
        }
    }

    function select($table, $columns, $where){
        try{
            $a = array();
            $w = "";
            foreach ($where as $key => $value) {
                $w .= " and " .$key. " like :".$key;
                $a[":".$key] = $value;
            }
            $stmt = $this->db->prepare("select ".$columns." from ".$table." where 1=1 ". $w);
            $stmt->execute($a);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(count($rows)<=0){
                $response["status"] = "warning";
                $response["message"] = "No data found.";
            }else{
                $response["status"] = "success";
                $response["message"] = "Data selected from database";
            }
                $response["data"] = $rows;
        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = 'Select Failed: ' .$e->getMessage();
            $response["data"] = null;
        }
        return $response;
    }
    function monthevents($table){
        
            $stmt = $this->db->prepare("select count(*) from ".$table." WHERE YEAR(event_start_datetime) = YEAR(NOW()) AND MONTH(event_start_datetime)=MONTH(NOW())");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            if($count<=0){
                $response["status"] = "warning";
                $response["message"] = "No data found.";

            }else{
                $response["status"] = "success";
                $response["message"] = "Data selected from database";
            }
            $response["monthevents"] = $count; 
        return $response;
    }

    function weekevents($table){            
            $stmt = $this->db->prepare("select count(*) from ".$table." WHERE WEEKOFYEAR(event_start_datetime) = WEEKOFYEAR(NOW())");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            if($count<=0){
                $response["status"] = "warning";
                $response["message"] = "No data found.";

            }else{
                $response["status"] = "success";
                $response["message"] = "Data selected from database";
            }
            $response["weekevents"] = $count;  
        return $response;
    }

    function totalevents($table){   
            $stmt = $this->db->prepare("select count(*) from ".$table);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            if($count<=0){
                $response["status"] = "warning";
                $response["message"] = "No data found.";

            }else{
                $response["status"] = "success";
                $response["message"] = "Data selected from database";
            }
            $response["totalevents"] = $count;
        return $response;
    }

    function eventexist($table, $columnsArray, $requiredColumnsArray) {
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);
        
        try{
            $c = array();
            $v = array();

            foreach ($columnsArray as $key => $value) {
                $c[] = $key;
                $v[] = $value;
            }
            $startdate = "";
            $enddate = "";

            $startdate = $v[1];
            $enddate = $v[2];

            $sql = "select * from events
                    WHERE event_start_datetime BETWEEN '$startdate' AND '$enddate'
                    OR
                    event_end_datetime BETWEEN '$startdate' AND '$enddate'
                    OR
                    event_start_datetime <= '$startdate' AND  event_end_datetime >= '$enddate'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $num_rows = count($rows);
            if($num_rows > 0){
                $response = array();
            $response["status"] = "error";
            $response["message"] = "Event already exist please change your datetime";
            echoResponse(200, $response);
            exit;
            }

        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = 'Select Failed: ' .$e->getMessage();
            $response["data"] = null;
        }
    }

    function checkExistBeforeUpadte($table, $event_id, $columnsArray, $requiredColumnsArray) {
            $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);
            
            try{
                $c = array();
                $v = array();

                foreach ($columnsArray as $key => $value) {
                    $c[] = $key;
                    $v[] = $value;
                }
                $startdate = "";
                $enddate = "";

                $startdate = $v[2];
                $enddate = $v[3];

                $sql = "select event_id from events
                        WHERE event_start_datetime BETWEEN '$startdate' AND '$enddate'
                        OR
                        event_end_datetime BETWEEN '$startdate' AND '$enddate'
                        OR
                        event_start_datetime <= '$startdate' AND  event_end_datetime >= '$enddate'";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $rows = $stmt->fetchColumn();
                // $num_rows = count($rows);
                if($rows != $event_id){
                    $response = array();
                $response["status"] = "error";
                $response["message"] = "Event already exist please change your datetime";
                echoResponse(200, $response);
                exit;
                }

            }catch(PDOException $e){
                $response["status"] = "error";
                $response["message"] = 'Select Failed: ' .$e->getMessage();
                $response["data"] = null;
            }
    }

    function eventValid($columnsArray, $requiredColumnsArray) {   
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);     
        try{
            $c = array();
            $v = array();

            foreach ($columnsArray as $key => $value) {
                $c[] = $key;
                $v[] = $value;
            }
            $startdate = "";
            $enddate = "";
            $response = array();

            $startdate = $v[1];
            $enddate = $v[2];
            
            $datediff = date("Y-m-d H:i:s", strtotime($startdate ."+30 minutes"));
            //$diffDt = new DateTime($datediff);

            date_default_timezone_set('Asia/Kolkata');
            $date = date('Y-m-d H:i:s', time());
            
                if($startdate < $date or $enddate < $date){
                    $response["status"] = "error";
                    $response["message"] = "Datetime already passed please update your datetime";
                    echoResponse(200, $response);
                    exit;
                }elseif ($startdate > $enddate) {
                    $response["status"] = "error";
                    $response["message"] = "Event start datetime should not be greater than event end datetime please update event time";
                    echoResponse(200, $response);
                    exit;                
                }elseif($datediff > $enddate){
                    $response["status"] = "error";
                    $response["message"] = "Event should not be less than 30 min";
                    echoResponse(200, $response);
                    exit;
                } 

            }catch(PDOException $e){
                $response["status"] = "error";
                $response["message"] = 'Select Failed: ' .$e->getMessage();
                $response["data"] = null;
            }
    }

    function checkValid($columnsArray, $requiredColumnsArray) {   
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);     
        try{
            $c = array();
            $v = array();

            foreach ($columnsArray as $key => $value) {
                $c[] = $key;
                $v[] = $value;
            }
            $startdate = "";
            $enddate = "";
            $response = array();

            $startdate = $v[2];
            $enddate = $v[3];
            
            $datediff = date("Y-m-d H:i:s", strtotime($startdate ."+30 minutes"));
            //$diffDt = new DateTime($datediff);

            date_default_timezone_set('Asia/Kolkata');
            $date = date('Y-m-d H:i:s', time());
            
                if($startdate < $date or $enddate < $date){
                    $response["status"] = "error";
                    $response["message"] = "Datetime already passed please update your datetime";
                    echoResponse(200, $response);
                    exit;
                }elseif ($startdate > $enddate) {
                    $response["status"] = "error";
                    $response["message"] = "Event start datetime should not be greater than event end datetime please update event time";
                    echoResponse(200, $response);
                    exit;                
                }elseif($datediff > $enddate){
                    $response["status"] = "error";
                    $response["message"] = "Event should not be less than 30 min";
                    echoResponse(200, $response);
                    exit;
                } 

            }catch(PDOException $e){
                $response["status"] = "error";
                $response["message"] = 'Select Failed: ' .$e->getMessage();
                $response["data"] = null;
            }
    }
    
    function insert($table, $columnsArray, $requiredColumnsArray) {
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);
        try{
            $a = array();
            $c = "";
            $v = "";
            foreach ($columnsArray as $key => $value) {
                $c .= $key. ", ";
                $v .= ":".$key. ", ";
                $a[":".$key] = $value;
            }
            $c = rtrim($c,', ');
            $v = rtrim($v,', ');
            $stmt =  $this->db->prepare("INSERT INTO $table($c) VALUES($v)");
            $stmt->execute($a);
            $affected_rows = $stmt->rowCount();
            $lastInsertId = $this->db->lastInsertId();
            $response["status"] = "success";
            $response["message"] = $affected_rows." row inserted into database";
            $response["data"] = $lastInsertId;
        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = 'Insert Failed: ' .$e->getMessage();
            $response["data"] = 0;
        }
        return $response;
    }

    function update($table, $columnsArray, $where, $requiredColumnsArray){ 
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);
        try{
            $a = array();
            $w = "";
            $c = "";
            foreach ($where as $key => $value) {
                $w .= " and " .$key. " = :".$key;
                $a[":".$key] = $value;
            }
            foreach ($columnsArray as $key => $value) {
                $c .= $key. " = :".$key.", ";
                $a[":".$key] = $value;
            }
                $c = rtrim($c,", ");

            $stmt =  $this->db->prepare("UPDATE $table SET $c WHERE 1=1 ".$w);
            $stmt->execute($a);
            $affected_rows = $stmt->rowCount();
            if($affected_rows<=0){
                $response["status"] = "warning";
                $response["message"] = "No row updated";
            }else{
                $response["status"] = "success";
                $response["message"] = $affected_rows." row(s) updated in database";
            }
        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = "Update Failed: " .$e->getMessage();
        }
        return $response;
    }
    function delete($table, $where){
        if(count($where)<=0){
            $response["status"] = "warning";
            $response["message"] = "Delete Failed: At least one condition is required";
        }else{
            try{
                $a = array();
                $w = "";
                foreach ($where as $key => $value) {
                    $w .= " and " .$key. " = :".$key;
                    $a[":".$key] = $value;
                }
                $stmt =  $this->db->prepare("DELETE FROM $table WHERE 1=1 ".$w);
                $stmt->execute($a);
                $affected_rows = $stmt->rowCount();
                if($affected_rows<=0){
                    $response["status"] = "warning";
                    $response["message"] = "No row deleted";
                }else{
                    $response["status"] = "success";
                    $response["message"] = $affected_rows." row(s) deleted from database";
                }
            }catch(PDOException $e){
                $response["status"] = "error";
                $response["message"] = 'Delete Failed: ' .$e->getMessage();
            }
        }
        return $response;
    }
    function verifyRequiredParams($inArray, $requiredColumns) {
        $error = false;
        $errorColumns = "";
        foreach ($requiredColumns as $field) {
        // strlen($inArray->$field);
            if (!isset($inArray->$field) || strlen(trim($inArray->$field)) <= 0) {
                $error = true;
                $errorColumns .= $field . ', ';
            }
        }

        if ($error) {
            $response = array();
            $response["status"] = "error";
            $response["message"] = 'Required field(s) ' . rtrim($errorColumns, ', ') . ' is missing or empty';
            echoResponse(200, $response);
            exit;
        }
    }
}

?>
