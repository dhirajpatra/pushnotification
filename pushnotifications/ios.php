<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * This class process ISO push notification
 * @author rappier
 *
 */
class Ios {

    /**
     * Sending push notificaion to IOS device
     * @param unknown $devicetoken
     * @param unknown $passpharse
     * @param unknown $filepath
     * @param unknown $pushmode
     * @param unknown $message
     * @return boolean
     */
    public function sendnotification($devicetoken,$passpharse,$filepath,$pushmode,$message)
    {

// Put your device token here (without spaces):
        $deviceToken = $devicetoken;

// Put your private key's passphrase here:
        $passphrase = $passpharse;

////////////////////////////////////////////////////////////////////////////////

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $filepath);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

		// Open a connection to the APNS server
		$url='ssl://gateway.sandbox.push.apple.com:2195';//Testing mode
		if($pushmode=='LIVE'){
		  $url='ssl://gateway.push.apple.com:2195';	//Live mode
		}

        $fp = stream_socket_client(
            $url, $err,
            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
        
        // Non block for fp pointer
        stream_set_blocking($fp, 0);
		
        if (!$fp){
            log_message('debug', sprintf('[Offline push]' . "Push Notification Failed to connect: $err $errstr" . PHP_EOL));
        }else{

        //    echo 'Connected to APNS' . PHP_EOL;
              
            // Encode the payload as JSON
            //$payload = json_encode($message);
            $payload = $message;
    
            // Build the binary notification
            $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
    
            // Send it to the server
            $result = fwrite($fp, $msg, strlen($msg));
            
            if (!$result){
                $this->checkAppleErrorResponse($fp);
            }
                        
        }
        
        // Close the connection to the server
        fclose($fp);
        
        if (!$result){            
            return false;            
        }else{
            return true;
        }
    }
    
    
    // FUNCTION to check if there is an error response from Apple
    // Returns TRUE if there was and FALSE if there was not
    public function checkAppleErrorResponse($fp) {
    
        //byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID).
        // Should return nothing if OK.
    
        //NOTE: Make sure you set stream_set_blocking($fp, 0) or else fread will pause your script and wait
        // forever when there is no response to be sent.
    
        $apple_error_response = fread($fp, 6);
    
        if ($apple_error_response) {
    
            // unpack the error response (first byte 'command" should always be 8)
            $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);
    
            if ($error_response['status_code'] == '0') {
                $error_response['status_code'] = '0-No errors encountered';
    
            } else if ($error_response['status_code'] == '1') {
                $error_response['status_code'] = '1-Processing error';
    
            } else if ($error_response['status_code'] == '2') {
                $error_response['status_code'] = '2-Missing device token';
    
            } else if ($error_response['status_code'] == '3') {
                $error_response['status_code'] = '3-Missing topic';
    
            } else if ($error_response['status_code'] == '4') {
                $error_response['status_code'] = '4-Missing payload';
    
            } else if ($error_response['status_code'] == '5') {
                $error_response['status_code'] = '5-Invalid token size';
    
            } else if ($error_response['status_code'] == '6') {
                $error_response['status_code'] = '6-Invalid topic size';
    
            } else if ($error_response['status_code'] == '7') {
                $error_response['status_code'] = '7-Invalid payload size';
    
            } else if ($error_response['status_code'] == '8') {
                $error_response['status_code'] = '8-Invalid token';
    
            } else if ($error_response['status_code'] == '255') {
                $error_response['status_code'] = '255-None (unknown)';
    
            } else {
                $error_response['status_code'] = $error_response['status_code'].'-Not listed';
    
            }
    
            log_message('debug', sprintf('[Offline push]' . '<br><b>+ + + + + + ERROR</b> Push Notification Response Command:<b>' . $error_response['command'] . '</b>&nbsp;&nbsp;&nbsp;Identifier:<b>' . $error_response['identifier'] . '</b>&nbsp;&nbsp;&nbsp;Status:<b>' . $error_response['status_code'] . '</b><br>'.PHP_EOL));
    
            log_message('debug', sprintf('[Offline push]' . 'Push Notification Identifier is the rowID (index) in the database that caused the problem, and Apple will disconnect you from server. To continue sending Push Notifications, just start at the next rowID after this Identifier.<br>'.PHP_EOL));
    
            return true;
        }
         
        return false;
    }
    
}
