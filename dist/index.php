<?php
// logging config
ini_set("error_log", "logs/errors.log");

require 'vendor/autoload.php';
require 'src/Router.php';

$Router = new Router;
?>