<?php

$DB_HOSTNAME = 'capsgoyal.com';
$DB_USERNAME = 'capsgr9s_excel';
$DB_PASSWORD = 'capsgr9s@@9911';
$DB_DBNAME = 'capsgr9s_goyal';

$dbcon = new mysqli($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

$_POST = json_decode(file_get_contents("php://input"), true);

$email = $_POST['email'];
$password = $_POST['password'];

$query = "SELECT * FROM user_table WHERE user_email='$email' LIMIT 0,1";

$result = $dbcon->query($query);
$result = $result->fetch_assoc();

if($result){
    $email = $result['user_email'];
    $correct_password = $result['user_password'];

    if(md5($password) == $correct_password)
    {
        $token = md5($email);

        http_response_code(200);
        echo json_encode(
            array(
                "message" => "Successful login.",
                "token" => $token,
                "email" => $email,
            ));
    }
    else{
        http_response_code(401);
        echo json_encode(array("message" => "Login failed."));
    }
}else{
    http_response_code(404);
    echo json_encode(array("message" => "Login failed. User Not Found"));
}
?>