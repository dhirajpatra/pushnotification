<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * This class process push notification to Android deviceas
 * @author rappier
 *
 */
class Android {
    /**
     * Sending push notification to Android devices
     * @param unknown $apikey
     * @param unknown $devicetoken
     * @param unknown $store
     * @param unknown $msg
     * @return boolean
     */
    public function sendnotification($apikey,$devicetoken,$msg)
    {
// API access key from Google API's Console
       // define( 'API_ACCESS_KEY', $apikey );
        $registrationIds = array($devicetoken);

// prep the bundle
       
        $fields = array
        (
            'registration_ids' 	=> $registrationIds,
            'data'				=> $msg
        );

        $headers = array
        (
            'Authorization: key=' . $apikey,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
        //print_r($result);

        $values=json_decode($result);
		
		$values=json_decode($result,true);
		if (!empty($values['success']) && $values['success']==1)
            return true;
        else
            return false;
		log_message('error', sprintf("SimpleEmailService::%s(): Encountered an error, but no description given", $functionname));

    }
}
