<?php
require('lib/ArduinoServer.php');
$config = array(
            'arduinoIP'     => '192.168.1.2',
            'arduinoPort'   => '3000'
        );

$server = new ArduinoServer($config);
$server->startServer();
?>
