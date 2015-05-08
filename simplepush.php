<?php
/**
* this script will send push notification
*
*/
$message = 'PN from closrr'.date("d-m-Y H:i:s");
$key_file_path = '/var/www/html/closrr/iospemfiles/pushcert.pem';
// Put your private key's passphrase here:
$passphrase = 'closrr123';
// Put your device token here (without spaces):
$deviceToken = 'd7c726bc61efe47873abdc40e09a9d4265190bbd29c0a944ca3ccf5a288ff7b0';

if($_POST['token'] <> '' and $_POST['msg'] <> ''){
//print_r($_POST); exit;
	
	$deviceToken = $_POST['token'];

	// Put your alert message here:	
	$message = $_POST['msg'];

	////////////////////////////////////////////////////////////////////////////////

	$ctx = stream_context_create();
	stream_context_set_option($ctx, 'ssl', 'local_cert', $key_file_path);
	stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

	// Open a connection to the APNS server
	$fp = stream_socket_client(
		'ssl://gateway.sandbox.push.apple.com:2195', $err,
		$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

	if (!$fp){
		exit("Failed to connect: $err $errstr" . PHP_EOL);

	}else{

		echo 'Connected to APNS' . PHP_EOL;

		// Create the payload body
		$body['aps'] = array(
			'alert' => $message,
			'sound' => 'default'
			);

		// Encode the payload as JSON
		$payload = json_encode($body);

		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

		for($i=1;$i <= $_POST['times']; $i++){
			// Send it to the server
			$result = fwrite($fp, $msg, strlen($msg));
		}

		if (!$result){
			echo '<span style="color:red; margin-left:40%;">Message not delivered' . PHP_EOL.'</span>';
		}else{
			echo '<span style="color:green; margin-left:40%;">Message successfully delivered' . PHP_EOL.'</span>';
		}
	}

	// Close the connection to the server
	fclose($fp);
}
?>
<div style="margin: 5% 0% 0% 35%" >
<h3>Enter details for push notification</h3>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" >
Enter How Many Time Need to Send <input type="number" name="times" id="times" value="1"><br/><br/>
Enter Device Token <input type="text" name="token" size="100" id="token" value="<?php echo $deviceToken; ?>"><br/><br/>
Enter Message <input type="text" name="msg" id="msg" size="100" value="<?php echo $message; ?>"> <br /><br />
<input type="submit" value="Send Push Notification" >

</form>
<!--span>Key File: <?php echo $key_file_path; ?></span-->
</div>
