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
$conn = new mysqli("localhost", "hakerist_hjp", "Ft&N%XQdHR-i", "hakerist_hlist_jp");

// Check connection
if (mysqli_connect_errno()) {
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

/********************************************************/

include '../payment/class/Paypal.php';
include '../payment/class/Westernunion.php';
//paypal
$paypal = new Paypal();
$paypal_data = $paypal->readDatafrompaypal();


//western union
$wu = new Westernunion();
$wu_data = $wu->readDatafromwu();

//paypal
$fname    =   $paypal_data['fname'];
$lname    =   $paypal_data['lname'];
$address  =   $paypal_data['address'];
$district =   $paypal_data['district'];
$division =   $paypal_data['division'];
$phone    =   $paypal_data['phone'];

//western union
$fullname    =   $wu_data['fullname'];
$address     =   $wu_data['address'];
$state       =   $wu_data['state'];
$postcode    =   $wu_data['postcode'];
$country     =   $wu_data['country'];
$tel         =   $wu_data['tel'];


/********************************************************/


$email_body = file_get_contents("email-body-invoice.php");
$worker_emails_file = file_get_contents("worker-email.txt");
$worker_emails = explode(',',$worker_emails_file);

require_once "mailer/class.phpmailer.php";

if($send == 'true') {
    foreach ($to as $owner) {
      
        $new_email_body = str_replace("#project_description#",$owner["project_description"],$email_body);
        $new_email_body = str_replace("#project_id#",$owner["project_id"],$new_email_body);
        /*********************************************************************************/
        //paypal
        $new_email_body = str_replace("#fname#",$fname,$new_email_body);
        $new_email_body = str_replace("#lname#",$lname,$new_email_body);
        $new_email_body = str_replace("#address#",$address,$new_email_body);
        $new_email_body = str_replace("#district#",$district,$new_email_body);
        $new_email_body = str_replace("#division#",$division,$new_email_body);
        $new_email_body = str_replace("#phone#",$phone,$new_email_body);
        
        /*********************************************************************************/
        
        
        /*********************************************************************************/
        //Western union
        $new_email_body = str_replace("#fullname#",$fullname,$new_email_body);
        $new_email_body = str_replace("#address#",$address,$new_email_body);
        $new_email_body = str_replace("#state#",$state,$new_email_body);
        $new_email_body = str_replace("#postcode#",$postcode,$new_email_body);
        $new_email_body = str_replace("#country#",$country,$new_email_body);
        $new_email_body = str_replace("#tel#",$tel,$new_email_body);
        
        /*********************************************************************************/
        $new_email_body = str_replace("#project_owner#",$owner["project_owner"],$new_email_body); 
        $new_email_body = str_replace("#project_budget#",$owner["project_budget"],$new_email_body);
        $btccoin = substr(file_get_contents("https://blockchain.info/tobtc?currency=JPY&value=".$owner["project_budget"]), 0, 5);

        $new_email_body = str_replace("#btccoin#",$btccoin,$new_email_body);


        $mail = new PHPMailer();
        
        $mail->IsHTML(true);
    
        $mail->IsSMTP();   
        $mail->Host = "smtp.gmail.com"; 
        
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true; 
        $mail->Username = "service@hakerlist.co";
        $mail->Password = "Hlist1234"; 
        
        $mail->From = "service@hakerlist.co";
        $mail->FromName = "Customer Help";
        
        $mail->AddAddress($owner['project_owner']);
        foreach($worker_emails as $worker_email){
            $mail->AddCC($worker_email);
        }
        
        $mail->AddReplyTo("service@hakerlist.co");
        $mail->Subject = "Your order invoice";
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
        $mail->Username = "service@hakerlist.co";
        $mail->Password = "Hlist1234"; 
        
        $mail->From = "service@hakerlist.co";
        $mail->FromName = "Customer Help";
        
        //$mail->AddAddress("shiplu.hstu@gmail.com");
        
        
        $mail->AddReplyTo("service@hakerlist.co");
        $mail->Subject = "Your Work Invoice";
        $mail->Body    = $email_body;
        
    
        if(!$mail->Send())
        {
            echo "Message could not be sent for.";
        }
}
$conn->close();








?>