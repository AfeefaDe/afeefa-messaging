<?php
require 'src/MessageBuilder.php';
require 'src/Messenger.php';

class Router{

	public function __construct() {
    $this->defineRoutes();
    Flight::start();
  }
  
  public function defineRoutes() {
    Flight::route('/', function(){
      $json = $this->validateRequest();
      
      echo 'Afeefa Message API index route, nothing here to do';
    });

    Flight::route('POST /send/test', function() {
      
      $json = $this->validateRequest();
      
      # build message
      $MessageBuilder = new MessageBuilder;
      $message = $MessageBuilder->testMail($json);

      # send message
      $Messenger = new Messenger;
      $Messenger->send($message, $json);
      
      # log messaging
      # ...
    });

    Flight::route('GET /send/userMessageToContact', function() {
      $json = $this->validateRequest();
    });
  }
  
  public function validateRequest() {
    // authenticate
    $this->auth();
    
    // check parameters
    $req_data = Flight::request()->data;
    if (count($req_data)) {
      return $req_data;
    } else {
      Flight::halt(400, 'please provide json data as required; also be sure to set "Content-Type" header to "application/json"');
      die;
    }
  }
  
  public function auth() {
    $conf = parse_ini_file('config/auth.ini');
    if ( Flight::request()->data->key !== $conf['key']) {
      Flight::halt(401, "access denied");
      die;
    }
  }
}

?>