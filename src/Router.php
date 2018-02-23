<?php

class Router{
  public $make;
  public $model;

  /**
	 * constructor
	 */
	public function __construct()
	{
    $this->defineRoutes();
    Flight::start();
  }
  
  public function defineRoutes(){
    Flight::route('/', function(){
      echo 'Afeefa Message API index route, nothing here to do';
    });

    Flight::route('POST /send/test', function(){
      
      $json = $this->validateRequest();
      
      # send mail to given address
      echo "sending test mail to: " . $json->to;
    });

    Flight::route('GET /send/userMessageToContact', function(){
      $json = $this->validateRequest();
      
      # build message
      # send message
      # log messaging
    });
  }
  
  public function auth(){
    $valid = true;
    if(!$valid) {
      Flight::halt(401, "access denied");
      die;
    }
  }

  public function validateRequest(){
    $this->auth();
    
    // check parameters
    $req_data = Flight::request()->data;
    if(count($req_data)) {
      return $req_data;
    }
    else {
      Flight::halt(400, 'please provide json data as required; also be sure to set "Content-Type" header to "application/json"');
      die;
    }

    // check other things
    // ...
  }
}

?>