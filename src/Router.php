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
      $this->auth();
      
      $json = $this->checkParams(Flight::request()->data);
      
      # send mail to given address
      echo "sending test mail to: " . $json->to;
    });

    Flight::route('GET /send/userMessageToContact', function(){
      if(!$this->auth()) return;
      # build message
      # send message
      # log messaging
    });
  }
  
  public function auth(){
    $valid = true;
    if(!$valid) Flight::halt(401);
  }

  public function checkParams($request_data){
    if(count($request_data)) return $request_data;
    else Flight::halt(500, 'bad params');
  }
}

?>