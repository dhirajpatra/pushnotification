<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Pushnotification extends CI_Controller {

	public function __construct() {
		parent::__construct();
	 	//load the profile language file in
		$this -> load -> library('pushnotifications/android');
		$this -> load -> library('pushnotifications/ios');

		/**
		 * Load model Api model
		 * Location: ./application/models/api_model.php
		 */
		$this -> load -> model('admin/user_model');
		$this -> load -> model('api_model');

		//load helper common functions
		$this -> load -> helper('statuscodes');
		//load the Api language file in
		$this -> lang -> load('api');

	}

	/**
	 * index Method  for display admin profile details
	 * @method     index
	 * @access  Public
	 * @params   sessionid    [current session id]
	 * @return  array
	 */

	public function index() {
	    // for testing only hardcoded post values
	    /*$_POST['alert'] = 'fsdafasfdafa fdsaf adsfsd fasdfdsfs';
	    $_POST['badge'] = '+1';
	    $_POST['sound'] = 'default';
	    $_POST['channel'] =  '6591018424';
	    $_POST['info'] = array("from" => " 6591839203");*/

		if (!empty($_POST)) {
		    // It will create each element of post into separate variable
			extract($_POST);
			
			// Debug Testing log
			$encode = json_encode($_POST);
			$log = "URL: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "data: " . $encode . PHP_EOL . "RESPPONSE: " . $encode . PHP_EOL . "-------------------------" . PHP_EOL;
			log_message('debug', sprintf('[Offline push]' . $log));

			// Android API key
			$apikey = $this -> config -> item('ANDROID_API_KEY');
			
			// IOS PEM file
			$pemfilename = $this -> config -> item('IOS_PEM_FILE_PATH') . '/' . $this -> config -> item('IOS_PEM_FILE_NAME');
			$passpharse = $this -> config -> item('IOS_PASS_PHRASE');
			$pushmode = $this -> config -> item('IOS_PUSH_MODE');
			//live or test
			
			$from = '+' . trim($info['from']);
			$to = trim($channel);
			if(!preg_match('/\+/', $to)){
			     $to = '+' . trim($channel); // for phone no if + no not thre then add
			}
			
			//if (!empty($message)){
				$result = array();
				
				//Get device token
				$token_records = $this -> user_model -> getdeviceToken($to);
				//log_message('debug', sprintf('[Offline push token_records array]' . json_encode($token_records)));
				//print_r($token_records); exit;				
				if (isset($token_records) && is_array($token_records)){
				       
				    // user data
				    $where_array = array('mobile_number' => $from);
				    $user_record = $this -> user_model -> getUser($where_array);
				    $full_name = (count($user_record) > 0)?$user_record['full_name']:'';				    				    
				    $badges = intval($token_records[0]['badge_count']) + intval($badge); // db value + badge from MIM
				    log_message('debug', sprintf('[Offline push badge value] DB Badge: ' . intval($token_records[0]['badge_count']) .' , MIM Badge: '. intval($badge) ));
				    //$badges = 1;
				    // Update badge_count in device token table
				    $data['badge_count'] = $badges;
				    $data['mobile_number'] = $to;
				    $device_token_update = $this -> api_model -> updateNotificationPreview($data);
				    
				    // Prepare the message
				    $title = CLOSRR_PUSH_NOTIFICATION_TITLE;
				    $action_loc_key = 'VIEW';  // for preview to see button
				    // prepare message
				    if(($token_records[0]['show_preview'] == 0) && (!isset($metadata))){ // preview off and chat message				        
				        $message_body = $full_name . ': ' . 'sent you a message';
				        
				    }elseif(($token_records[0]['show_preview'] == 0) && (isset($metadata))){ // preview off and image, audio, video, location etc type message
				        $action_loc_key = 'PLAY';
				        $type = $metadata['type'];
				        
				        switch( $type ) {
				            case 'image':
				                $message_body = $full_name . ': ' . 'sent a image';
				                break;
				            case 'video':
				                $message_body = $full_name . ': ' . 'sent a video';
				                break;
				            case 'audio':
				                $message_body = $full_name . ': ' . 'sent a audio';
				                break;
				            case 'location':
				                $message_body = $full_name . ': ' . 'sent a location';
				                break;
				            default:
				                $message_body = $full_name . ': ' . 'sent a message';
				                break;
				        }
				            
				    }elseif(($token_records[0]['show_preview'] == 1) && (isset($metadata))){ // preview is on and message type image, audio, video or location
				        $action_loc_key = 'PLAY';
				        $type = $metadata['type'];
				        
				        switch( $type ) {
				            case 'image':
				                $message_body = $full_name . ': ' . 'sent a image';
				                break;
				            case 'video':
				                $message_body = $full_name . ': ' . 'sent a video';
				                break;
				            case 'audio':
				                $message_body = $full_name . ': ' . 'sent a audio';
				                break;
				            case 'location':
				                $message_body = $full_name . ': ' . 'sent a location';
				                break;
				            default:
				                $message_body = $full_name . ': ' . 'sent a message';
				                break;
				        }
				        			            
				    }elseif(($token_records[0]['show_preview'] == 1) && (!isset($metadata))){ // preview is on and chat message
				        
				        if(strlen($alert) > 100){ // if message more than 100 character long cut upto 97
				            $message_body = $full_name . ': ' . substr($alert, 0, 97) . '...';
				        }else{
				            $message_body = $full_name . ': ' . $alert;
				        }
				        
				    }

				    // IOS and Android server will get different push settings
					foreach ($token_records as $devicetoken) {
					    // IOS
						if ($devicetoken['device_type'] == 1 && $pemfilename != '') {//Ios Push notifications
						    
						    // preparing message
						    $message = array(
						                      "alert" => array(
						                                  'title' => $title,
						                                  'body' => $message_body,
						                                  'action-loc-key' => $action_loc_key
						                       ),
						                                  'sound' => $sound,
						                                  'badge' => $badges
						    );
						  
						    $result = array(
						            "aps" => $message,
						            "from" => $from,
						            "to" => $to
						            
						    );
						    $pushmessage = json_encode($result);
						    
							$result = $this -> ios -> sendnotification($devicetoken['device_token'], $passpharse, $pemfilename, $pushmode, $pushmessage);
							log_message('debug', sprintf('[Offline push result]' . $pushmessage));
							
						// Android	
						} elseif ($devicetoken['device_type'] == 2 && $apikey != '') {//Android push notifications
						    
						    // preparing message
						    $title = CLOSRR_PUSH_NOTIFICATION_TITLE . ' notification';
						    $message = array('title' => $title,
						                      'subtitle' => '',
						                      'message' => $message_body,
						                      'tickerText'	=> '',
                                              'vibrate'	=> 1,
						                      'sound' => 'default'
						    );
						    
						    $result = array("status_code" => CL_RESPONSE_SUCCESS,
						            "timestamp" => date('Y-m-d H:i:s'),
						            "status_message" => lang('CL_PUSH_NOTIFICATION_SUCCESS'),
						            "message" => $message,
						            "from" => $from,
						            "to" => $to);
						    $pushmessage = json_encode($result);
						    
							$result = $this -> android -> sendnotification($apikey, $devicetoken['device_token'], $pushmessage);
                            
							log_message('debug', sprintf('[Offline push result]' . $pushmessage));							
							
						}

					}
					
				}else{
				    log_message('debug', 'No of records for token is ' . count($token_records));
				}

			/*} else {
				//Debug  message
				log_message('debug', sprintf('[Offline push] No data found from post data'));
			}*/

		} else {
			//Debug  message
			log_message('debug', sprintf('[Offline push] No data found from post data'));
		}

	}
	
	
}

/* End of file profile.php */
/* Location: ./application/controllers/profile.php */
