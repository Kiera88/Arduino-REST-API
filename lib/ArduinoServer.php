<?php
/**
 * Arduino API Server Class
 * 1.0.0  - 25 Feb 2013
 */

class ArduinoServer {

	/**
   	 * Constant
   	 */
	const HTTP_GET 			= 'GET';
	const HTTP_POST 		= 'POST';
	const API_KEY			= '75f5750f6dd6afbec57b0928a0ec306b';
	const INFO_COMMAND		= 'info';
	const READ_COMMAND		= 'read';
	const WRITE_COMMAND		= 'write';
	const READ_MESSAGE		= '1';
	const WRITE_MESSAGE 	= '2';
	const DIGITAL_MESSAGE 	= '1';
	const ANALOG_MESSAGE 	= '2';

	/**
   	 * Properties
   	 */
    private $requestURI;
    private $serverMessage;
    private $arduinoMessage;
    private $verb;
    private $arduinoIP;
    private $arduinoPort;
    private $errorMessage;
    private $maxDigitalPin 			= 13;
    private $maxAnalogPin 			= 5;
    private $forbiddenDigitalPin 	= array('0', '1', '2', '10', '11', '12', '13');
    private $forbiddenAnalogPin;    
	private $report 		 		= array(
		    							'success'		=> 'success',
		    							'connerror'		=> 'Cannot Connect To Arduino',
		    							'urierror'		=> 'Bad Request',
		    							'modeerror'		=> 'Bad Pin Type (Digital - Analog)',
		    							'pinerror'		=> 'Bad Pin Number',
		    							'valueerror'	=> 'Bad Value',
		    							'permission'	=> 'Bad API Key'
		    						);
    private $JSON;

	/**
   	 * Constructor
   	 */
    public function __construct($config, $debug = false){
    	if(!$debug){
    		error_reporting(0);
    	}
    	$this->setArduinoIP($config['arduinoIP']);
    	$this->setArduinoPort($config['arduinoPort']);
    }

	/**
   	 * Setter
   	 */
    public function setArduinoIP($arduinoIP = ''){
    	$this->arduinoIP = $arduinoIP;
    }

    public function setArduinoPort($arduinoPort = ''){
    	$this->arduinoPort = $arduinoPort;  	
    }

    public function setMaxDigitalPin($maxDigitalPin = ''){
    	$this->maxDigitalPin = $maxDigitalPin;   	
    }

    public function setMaxAnalogPin($maxAnalogPin = ''){
    	$this->maxAnalogPin = $maxAnalogPin;  	
    }

    public function setforbiddenDigitalPin($forbiddenDigitalPin = array()){
    	$this->forbiddenDigitalPin = $forbiddenDigitalPin; 	
    }  

    public function setforbiddenAnalogPin($forbiddenAnalogPin = array()){
    	$this->forbiddenAnalogPin = $forbiddenAnalogPin; 	
    }  

	/**
   	 * Main Function
   	 */
    public function startServer(){
    	/* Set HTTP Method and Request-URI */
    	$this->verb = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '');
    	$reqstr = (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');

    	/* Remove first / character on Request-URI */
		$req = substr($reqstr, 1);

		/* Explode Request-URI to array(seqment) */
		$this->requestURI = explode('/', $req);  
    	
    	$this->handleRequest();
    }

    public function handleRequest(){
    	switch($this->verb){
 			case self::HTTP_GET :
    			$this->handleGetRequest($this->requestURI);
    			break;
			case self::HTTP_POST :
    			$this->handlePostRequest($this->requestURI, $_POST);
    			break;
			default :
				$this->errorMessage = $this->report['urierror'];
    			$this->handleErrorRequest();
    			break;
    	}
    }

    public function handleGetRequest($seqment){
		switch($seqment[0]){
			case self::READ_COMMAND :
				$this->readPin($seqment);
				break;
			case self::INFO_COMMAND :
				$this->getInfo();
				break;
			default :
				$this->errorMessage = $this->report['urierror'];
				$this->handleErrorRequest();
    			break;
		}
    }

    public function handlePostRequest($seqment, $entity){
		switch($seqment[0]){
			case self::WRITE_COMMAND :
				$this->WritePin($seqment, $entity);
				break;
			default :
				$this->errorMessage = $this->report['urierror'];
				$this->handleErrorRequest();
    			break;
		}
    }

	public function handleErrorRequest(){
		$data = array(
  					'status' => 
  						array(
  							'result'	=> 'failed',
  							'errormsg'	=> $this->errorMessage
  						)
 				);
		$this->buildJson($data);
		$this->generateHTTPResponse('404');
	}

    public function readPin($seqment){
    	$smsg = $this->validateReadRequest($seqment);
    	if($smsg){
    		$this->serverMessage = $smsg;
    		$amsg = $this->callArduino();
    		if($amsg != ''){
    			$this->arduinoMessage = $amsg;
    			$data = $this->buildData(self::READ_COMMAND, $seqment);
    			$this->buildJSON($data);
    			$this->generateHTTPResponse('200');
    		}else{
    			$this->errorMessage = $this->report['connerror'];
				$this->handleErrorRequest();
    		}
    	}else{
			$this->handleErrorRequest();    		
    	}
    }

    public function getInfo(){
    	$data = $this->buildData(self::INFO_COMMAND);
    	$this->buildJSON($data);
    	$this->generateHTTPResponse('200');
    } 

    public function writePin($seqment, $entity){
    	$smsg = $this->validateWriteRequest($seqment, $entity);
    	if($smsg){
    		$this->serverMessage = $smsg;
    		$amsg = $this->callArduino();
    		if($amsg != ''){
    			$this->arduinoMessage = $amsg;
    			$data = $this->buildData(self::WRITE_COMMAND, $entity);
    			$this->buildJSON($data);
    			$this->generateHTTPResponse('200');
    		}else{
    			$this->errorMessage = $this->report['connerror'];
				$this->handleErrorRequest();
    		}
    	}else{
			$this->handleErrorRequest();    		
    	}
    }  

    public function validateReadRequest($seqment){

    	/* Error flag and message initial value */
 		$error = false;
		$message = '';

		if($seqment[0] = self::READ_COMMAND){
			/* Command - 1st Request-URI seqment */
			$message .= self::READ_MESSAGE;

			/* Mode/Pin Type - 2nd Request-URI seqment */
			switch($seqment[1]){
				case 'digital' :
					$message .= self::DIGITAL_MESSAGE;
					/* Pin Number - 3rd Request-URI seqment */
					if(!in_array($seqment[2], $this->forbiddenDigitalPin)){
						$pin = $seqment[2];
	
						if(is_numeric($pin)){
							if(((int)$pin >= 0) && ((int)$pin <= $this->maxDigitalPin)){
								$message .= str_pad($pin, 2, '0', STR_PAD_LEFT);
							}else{
								$this->errorMessage = $this->report['pinerror'];
								$error = true;
							}						
						}else{
							$this->errorMessage = $this->report['pinerror'];
							$error = true;
						}
					}else{
						$this->errorMessage = $this->report['pinerror'];
						$error = true;						
					}
					break;

				case 'analog' :
					$message .= self::ANALOG_MESSAGE;
					/* Pin Number - 3rd Request-URI seqment */
					if(!in_array($seqment[2], $this->forbiddenAnalogPin)){
						$pin = $seqment[2];
	
						if(is_numeric($pin)){
							if(((int)$pin >= 0) && ((int)$pin <= $this->maxAnalogPin)){
								$message .= str_pad($pin, 2, '0', STR_PAD_LEFT);
							}else{
								$this->errorMessage = $this->report['pinerror'];
								$error = true;
							}						
						}else{
							$this->errorMessage = $this->report['pinerror'];
							$error = true;
						}
					}else{
						$this->errorMessage = $this->report['pinerror'];
						$error = true;						
					}
					break;
				default:
					$this->errorMessage = $this->report['modeerror'];
					$error = true;
					break;
			}

			if(!$error){
				$message .= "\n"; //End Message
				return $message;
			}else{
				return false;
			}

		}else{
			return false;
		}
	}

    public function validateWriteRequest($seqment, $entity){

    	/* Error flag and message initial value */
 		$error = false;
		$message = '';

		if($seqment[0] = self::WRITE_COMMAND){

			if($entity['apiKey'] == self::API_KEY){

				/* Command - 1st Request-URI seqment */
				$message .= self::WRITE_MESSAGE;

				/* Mode/Pin Type - Mode Post Entity */
				switch($entity['mode']){
					case 'digital' :
						$message .= self::DIGITAL_MESSAGE;

						/* Pin Number - Pin Post Entity */
						if(!in_array($entity['pin'], $this->forbiddenDigitalPin)){
							$pin = $entity['pin'];
		
							if(is_numeric($pin)){
								if(((int)$pin >= 0) && ((int)$pin <= $this->maxDigitalPin)){
									$message .= str_pad($pin, 2, '0', STR_PAD_LEFT);
								}else{
									$this->errorMessage = $this->report['pinerror'];
									$error = true;
								}						
							}else{
								$this->errorMessage = $this->report['pinerror'];
								$error = true;
							}
						}else{
							$this->errorMessage = $this->report['pinerror'];
							$error = true;						
						}

						/* Value (0-1) - 4th Value Post Entity */
						$value = $entity['value'];
						if(is_numeric($value)){
							if(($value >= 0) && ($value <= 1)){
								$message .= str_pad($value, 3, '0', STR_PAD_LEFT);
							}else{
								$this->errorMessage = $this->report['valueerror'];
								$error = true;
							}
						}else{
							$this->errorMessage = $this->report['valueerror'];
							$error = true;
						}

						break;
					case 'analog' :
						$message .= self::ANALOG_MESSAGE;

						/* Pin Number - 3rd Request-URI seqment */
						if(!in_array($entity['pin'], $this->forbiddenDigitalPin)){
							$pin = $entity['pin'];
		
							if(is_numeric($pin)){
								if(((int)$pin >= 0) && ((int)$pin <= $this->maxDigitalPin)){
									$message .= str_pad($pin, 2, '0', STR_PAD_LEFT);
								}else{
									$this->errorMessage = $this->report['pinerror'];
									$error = true;
								}						
							}else{
								$this->errorMessage = $this->report['pinerror'];
								$error = true;
							}
						}else{
							$this->errorMessage = $this->report['pinerror'];
							$error = true;						
						}

						/* PWM Value (0-255) - 4th Value Post Entity */
						$value = (int)$entity['value'];
						if(is_numeric($value)){
							if(($value >= 0) && ($value <= 255)){
								$message .= str_pad($value, 3, '0', STR_PAD_LEFT);
							}else{
								$this->errorMessage = $this->report['valueerror'];
								$error = true;
							}
						}else{
							$this->errorMessage = $this->report['valueerror'];
							$error = true;
						}					

						break;
					default:
						$this->errorMessage = $this->report['modeerror'];
						$error = true;
						break;
				}

				if(!$error){
					$message .= "\n"; //End Message
					return $message;
				}else{
					return false;
				}

			}else{
				$this->errorMessage = $this->report['permission'];
				return false;
			}

		}else{
			$this->errorMessage = $this->report['urierror'];
			return false;
		}
	}

	public function callArduino(){
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$message = $this->serverMessage;
  		//Set Timeout, Connect and Send Message
		if(socket_connect($socket, $this->arduinoIP, $this->arduinoPort)){
			if(socket_write($socket, $message, strlen($message))){
		 		$response = socket_read($socket, 1024, PHP_BINARY_READ);
				if($response != ''){
					socket_close($socket);
					return $response;
				}else{
					$this->errorMessage = $this->report['connerror'];
					socket_close($socket);
					return false;	
				}		 							
			}
		socket_close($socket);				
		}else{
			$this->errorMessage = $this->report['connerror'];
			return false;
		}
	}

	public function buildData($command, $params){
		switch($command){
			case self::READ_COMMAND :
				$data = array(
  							'status' =>
  								array(
  									'result' => $this->report['success']
  								),
  							'data' => 
  								array(
  									'mode' 	=> $params[1],
  									'pin'	=> $params[2],
  									'value'	=> (int)$this->arduinoMessage
  								)
 						);
				break;

			case self::WRITE_COMMAND :
				$data = array(
  							'status' =>
  								array(
  									'result' => $this->report['success']
  								),
  							'data' => 
  								array(
  									'mode' 	=> $params['mode'],
  									'pin'	=> $params['pin'],
  									'value'	=> (int)$this->arduinoMessage
  								)
 						);
				break;

			case self::INFO_COMMAND :
				$data = array(
    				'arduinoIP'		=> $this->arduinoIP,
  					'arduinoPort'	=> $this->arduinoPort,
  					'maxDigitalPin' => $this->maxDigitalPin,
  					'maxAnalogPin'	=> $this->maxAnalogPin,
					'forbiddenPin'	=> $this->forbiddenPin
  				);
				break;

			default :
				break;			
		}
		return $data;
	}

	public function buildJSON($data){
		$this->JSON = json_encode($data, JSON_PRETTY_PRINT);
	}

  	public function generateHTTPResponse($code){
  		if($code == '200'){
   			header('HTTP/1.1 200 OK'); 			
  		}else{
  			header('HTTP/1.1 404 Not Found'); 	
  		}	

		header('Content-type: application/json');
  		echo $this->JSON;
  	}
}
?>