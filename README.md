Arduino-REST-API
================

PHP REST API Server for Arduino

Overview
--------

1. Arduino use Ethernet Shield to communicate with the Server
2. Provided functionality to manipulate Arduino I/O pins through HTTP protocol
3. Supported Arduino functions are digitalRead, digitalWrite, analogRead and analogWrite
4. Use JSON as the data-interchange-format

Requirement
--------

1. Apache Web server with mod_rewrite enabled
2. PHP 5.4
3. Supported Arduino functions are digitalRead, digitalWrite, analogRead and analogWrite

Getting Started
--------

The constructor required one parameter which is an array contain the Arduino IP Address and Port number.
For example :

    <?php
    	require('lib/ArduinoServer.php');
		$config = array(
			'arduinoIP'		=> '192.168.1.2',
			'arduinoPort'	=> '3000'
		);
		$server = new ArduinoServer($config);
		$server->startServer();
	?>

Another required step is rewrite the url using .htaccess file. 

    RewriteEngine On

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]

How to use
--------

**Read Input pin state**
You need to create HTTP Request(GET) using this URL format:

	http://[server ip address]/read/[digital-analog]/[pin number]

Example

	http://192.168.1.1/read/digital/6

<br>
**Write value to output pin**
You need to create HTTP Request(POST) to this URL:

    http://[server ip address]/write

The POST Data :
<br>
<table>
  <tr>
    <th>Key</th>
    <th>Value (Digital)</th>
    <th>Value (Analog)</th>
  </tr>
  <tr>
    <td align="center">mode</td>
    <td align="center">digital</td>
    <td align="center">analog</td>
  </tr>
  <tr>
    <td align="center">pin</td>
    <td align="center">0,1,2...</td>
    <td align="center">(PWM) 3,5,6,...</td>
  </tr>
  <tr>
    <td align="center">value</td>
    <td align="center">0 or 1</td>
    <td align="center">0 - 255</td>
  </tr>
</table>
<br>

Example JSON Data
--------  

    {
        status : {
            result : "Success"
        },
        data : {
            mode  : "analog",
            pin   : 5,
            value : 512
        }
    }

Credits
-------

The arduino code based on <a href="https://github.com/ecto/duino" targe="_blank">this</a> awesome project 