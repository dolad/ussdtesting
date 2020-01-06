<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class UssdController extends Controller
{
    use UssdMenuTrait;
    use SmsTrait;


    public function ussdRequeestHandler(Request $request){
        $sessionId   = $request["sessionId"];
        $serviceCode = $request["serviceCode"];
        $phone       = $request["phoneNumber"];
        $text        = $request["text"];

        header('Content-type: text/plain');

        if(User::where('phone',$phone)->exists()){
            $this->handleReturnUser($text,$phone);
        }else{

            $this->handleNewUser($text, $phone);
        }

    }

    public function handleNewUser($ussd_string,$phone){

        $ussd_string_exploded=explode("*",$ussd_string);
        $level=count($ussd_string_exploded);

        if(empty($ussd_string) or $level == 0 ){
            $this->returnUsermenu();
            // show the menu
        }
        switch($level){
            case($level==1 && !empty($ussd_string)):
                if($ussd_string_exploded[0]== "1" ){
                    // if user select 1, send them to the registration menu
                    $this->ussd_proceed("Please enter your fullname and desire pin separated by commas. \n eg: Jane Doe, 1234 ");
                }else if($ussd_string_exploded[0]=="2"){
                    $this->ussd_stop("You will receive more information on SampleUSSD via sms shortly.");
                    $this->sendText("This is a subscription service from SampleUSSD.", $phone);
                }else if($ussd_string_exploded[0]=="3"){
                    $this->ussd_stop("Thank you for reaching out to SampleUSSD.");
                }
            break;
            case 2:
                if($this->ussdRegister($ussd_string_exploded[1], $phone)=="success"){
                    $this->serviceMenu();
                }
            break;
        }

    }
    public function handleReturnUser($ussd_string,$phone){
        $ussd_string_exploded=explode ("*",$ussd_string);
        $level = count($ussd_string_exploded);

        if(empty($ussd_string) or $level== 0 ){
            $this->returnUserMenu(); 
        }
        switch($level){
            case($level==1 && !empty($ussd_string)):
                if($ussd_string_exploded[0]=="1"){
                    $this->ussd_proceed("kindly input your pin");
                }else if($ussd_string_exploded[0]=="2"){

                    $this->ussd_stop("Thank you for reaching out to SampleUSSD.");

                }else{
                    $this->ussd_stop("Invalid input");
                }
            break;

            case 2:
                if ($this->ussdLogin($ussd_string_exploded[1], $phone) == "Success") {
					$this->servicesMenu();
                }
            break;

            case 3:
                if ($ussd_string_exploded[2] == "1") {                   
					$this->ussd_stop("You will receive an sms shortly.");
					$this->sendText("You have successfully subscribed to updates from SampleUSSD.", $phone);
				}else if($ussd_string_exploded[2] =="2"){
                    $this->ussd_stop("You will receive more information on SampleUSSD via sms shortly.");
					$this->sendText("This is a subscription service from SampleUSSD.",$phone);
                }else if($ussd_string_exploded[2]== "3"){
                    $this->ussd_stop("thanks for reaching out to SampleUSSD.");
                }else{
                    $this->ussd_stop("Invalid input!");
                }
            break;

        }
    }

    


    public function ussdRegister($details, $phone){
        $input = explode(",", $details);
        $full_name=$input[0];
        $pin= $input[1];

        $user = new User;
        $user->name=$full_name;
        $user->phone=$phone;
        $user->pin = $pin;
        $user->save();

        return "success";

    }

    public function ussdLogin($details, $phone){
        $user= User::where('phone', $phone)->first();

        if($user->pin== $details){
            return "Success";
        }else{
            return $this->ussd_stop("Login was unsuccessfully !");
        }
    }

    public function ussd_proceed($ussd_text){
        echo "CON $ussd_text";
    }

    

    public function ussd_stop($ussd_text){
        echo "END $ussd_text";
    }

}
