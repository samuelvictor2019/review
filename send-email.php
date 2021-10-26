#!/usr/bin/php
<?php

$send = isset($_REQUEST['send']) ? $_REQUEST['send']:'';

if($argv[1] !=''){
    parse_str($argv[3], $params);
    $send=$params['send'];
}
$send = 'true';

//$con = mysqli_connect("localhost","p4x9f3m7_wrdp17","iUQAjHv0AWihZpWf","p4x9f3m7_wrdp17");
//$con = mysqli_connect("localhost","root","","hlist");
$conn = new mysqli("localhost", "shahin_hlist", "john84f98", "shahin_hlist");

// Check connection
    if (mysqli_connect_errno())
    {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

$id = $conn->query("SELECT id FROM project WHERE name='last' ORDER BY id DESC LIMIT 1")->fetch_object()->id;

$sql = "SELECT * FROM project WHERE id > ".$id;

$result = $conn->query($sql);
$to_ids=[];
$to=[];
$done_ids=[];
if ($result->num_rows > 0) {
    
    while($rows = $result->fetch_assoc()) {
        $user_email = $rows['owner'];

        if( !in_array($rows['id'],$to_ids)){
            $to_ids[] = $rows['id'];
            $to[] = array(
                'project_id'=> $rows['id'],
                'project_name'=> $rows['name'],
                'project_description'=> $rows['description'],
                'project_owner'=> $rows['owner'],
                'project_budget'=> $rows['budget']
            );          
        }    
    }  
}


$email_body = file_get_contents("email-body.html");
$worker_emails_file = file_get_contents("worker-email.txt");
$worker_emails = explode(',',$worker_emails_file);

require_once "mailer/class.phpmailer.php";

if($send == 'true') {

foreach($to as $owner){

    $new_email_body = str_replace("#project_description#",$owner["project_description"],$email_body);
    $new_email_body = str_replace("#project_budget#",$owner["project_budget"],$new_email_body); 


    $mail = new PHPMailer();

    $mail->IsSMTP();   
    $mail->Host = "smtp.gmail.com"; 
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true; 
    $mail->Username = "help@hackerslist.co";
    $mail->Password = "jam9411161"; 
    
    $mail->From = "help@hackerslist.co";
    $mail->FromName = "Customer Help";
    
    $mail->AddAddress($owner['project_owner']);
    foreach($worker_emails as $worker_email){
        $mail->AddCC($worker_email);
    }
    
    $mail->AddReplyTo("help@hackerslist.co");
    $mail->Subject = "Hackers assigned for you";
    $mail->Body    = $new_email_body;
    

    if(!$mail->Send())
    {
        echo "Message could not be sent for.".$owner['project_id'];
    }else{
        $sql = "UPDATE project set name='last' WHERE id=".$owner["project_id"];
        $result = $conn->query($sql);
    }
    
    
}

}else{

    $mail = new PHPMailer();
    
        $mail->IsSMTP();   
        $mail->Host = "smtp.gmail.com"; 
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true; 
        $mail->Username = "help@hackerslist.co";
        $mail->Password = "jam9411161"; 
        
        $mail->From = "help@hackerslist.co";
        $mail->FromName = "Customer Help";
        
        //$mail->AddAddress("shiplu.hstu@gmail.com");
        
        
        $mail->AddReplyTo("help@hackerslist.co");
        $mail->Subject = "Hackers assigned for you";
        $mail->Body    = $email_body;
        
    
        if(!$mail->Send())
        {
            echo "Message could not be sent for.";
        }
}
$conn->close();
?>