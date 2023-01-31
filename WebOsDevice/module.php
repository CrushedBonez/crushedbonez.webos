<?
class WebOsDevice extends IPSModule
{
	public function Create()
	{
	    //Never delete this line!
	    parent::Create();
        
	    // new version uses WebSocket Client
	    $this->RequireParent("{D68FD31F-0E90-7019-F16C-1949BD3079EF}");
	    $this->RegisterPropertyString("MAC", "");
	    $this->RegisterPropertyString("WebOSKey", "");
	}
    
	public function ApplyChanges()
	{
	    //Never delete this line!
	    parent::ApplyChanges();
	    //$this->update_parent_socket();
	}  

	
	public function connect()
	{
	    $WSClient = IPS_GetInstance($this->InstanceID)['ConnectionID'];
	    IPS_SetProperty($WSClient, "Active", true);
	    IPS_ApplyChanges($WSClient);
	    IPS_Sleep(1000);
	    $this->lg_handshake();
	    IPS_Sleep(1000);
	}
	
	public function disconnect()
	{
	    //close connection
	    $WSClient = IPS_GetInstance($this->InstanceID)['ConnectionID'];
	    IPS_SetProperty($WSClient, "Active", false);
	    IPS_ApplyChanges($WSClient);
	}

	public function message($msg)
	{
	    $command = '{"id":"message","type":"request","uri":"ssap://system.notifications/createToast","payload":{"message": "' . $msg .'"}}';
	    $this->SendData($command);
	}
	
	public function PowerOff()
	{
	    $command = '{"id":"power_off","type":"request","uri":"ssap://system/turnOff"}';
	    $this->SendData($command);
	}
	
	public function SetVolume($vol)
	{
	    $command = '{"id":"set_volume","type":"request","uri":"ssap://audio/setVolume","payload":{"volume":' . $vol . '}}';
	    $this->SendData($command);
	}

	public function SetMute($muted)
	{
	    $command = '{"id":"set_mute","type":"request","uri":"ssap://audio/setMute","payload":{"muted":'. $muted . '}}';
	    $this->SendData($command);
	}

	public function GetSystemInfo()
	{
	    $command = '{"id":"system_info", "type":"request","uri":"ssap://system/getSystemInfo"}';
	    $this->SendData($command);
	}

	public function GetExternalInputList()
	{
	    $command = '{"id":"get_input_list","type":"request","uri":"ssap://tv/getExternalInputList"}';
	    $this->SendData($command);
	}
	
	public function ReceiveData($JSONString)
	{
	    $data = json_decode($JSONString, true);
	    $data['Buffer'] = utf8_decode($data['Buffer']);
	    $this->SendDebug("Received Data :: JSON", $JSONString, 0);
	    $this->SendDebug("Received Data :: Buffer", $data['Buffer'], 0);
	    preg_match('#.*"client-key":"(.*)"}}$#mU', $data['Buffer'], $matches);
	    if ($matches)
	    {
	        $this->SendDebug("Received Data :: WebOS Key", "Extracted WebOS-Key: " . $matches[1], 0);
		if (empty($this->ReadPropertyString("WebOSKey"))) {
	            IPS_SetProperty($this->InstanceID, "WebOSKey", $matches[1]);
	            IPS_ApplyChanges($this->InstanceID);
		}
	    }
	    $payload = json_decode($data['Buffer'], true);
	    if ($payload['type'] === "response") {
		switch ($payload['id']) {
		    case 'system_info':
			$this->SendDebug("Received Data :: Response", "System Model " . $payload['payload']['modelName'] . ", Serial Number " . $payload['payload']['serialNumber'], 0);
			break;
		    default:
			$this->SendDebug("Received Data :: Response", "Unknown Response " . $payload['id'], 0);
		}
	    }
	}
	
	public function SendData($Data)
	{
	    $WSClient = IPS_GetInstance($this->InstanceID)['ConnectionID'];
	    if(!IPS_GetProperty($WSClient, "Active")) {
		$this->connect();
	    }
	
	    $this->SendDebug("Send Data :: Buffer", "$Data", 0);
	    $this->SendDataToParent(json_encode([
	        'DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}',
	        'Buffer' => utf8_encode($Data),
	    ]));
	}
	    
	public function lg_handshake()
	{
	    $handshake =    '{"type":"register","id":"register_0","payload":{"forcePairing":false,"pairingType":"PROMPT","client-key":"HANDSHAKEKEYGOESHERE","manifest":{"manifestVersion":1,"appVersion":"1.1","signed":{"created":"20140509","appId":"com.lge.test","vendorId":"com.lge","localizedAppNames":{"":"LG Remote App","ko-KR":"Ã«Â¦Â¬Ã«ÂªÂ¨Ã¬Â»Â¨ Ã¬â€¢Â±","zxx-XX":"Ã�â€ºÃ�â€œ RÃ‘ï¿½Ã�Â¼otÃ‘ï¿½ AÃ�Å¸Ã�Å¸"},"localizedVendorNames":{"":"LG Electronics"},"permissions":["TEST_SECURE","CONTROL_INPUT_TEXT","CONTROL_MOUSE_AND_KEYBOARD","READ_INSTALLED_APPS","READ_LGE_SDX","READ_NOTIFICATIONS","SEARCH","WRITE_SETTINGS","WRITE_NOTIFICATION_ALERT","CONTROL_POWER","READ_CURRENT_CHANNEL","READ_RUNNING_APPS","READ_UPDATE_INFO","UPDATE_FROM_REMOTE_APP","READ_LGE_TV_INPUT_EVENTS","READ_TV_CURRENT_TIME"],"serial":"2f930e2d2cfe083771f68e4fe7bb07"},"permissions":["LAUNCH","LAUNCH_WEBAPP","APP_TO_APP","CLOSE","TEST_OPEN","TEST_PROTECTED","CONTROL_AUDIO","CONTROL_DISPLAY","CONTROL_INPUT_JOYSTICK","CONTROL_INPUT_MEDIA_RECORDING","CONTROL_INPUT_MEDIA_PLAYBACK","CONTROL_INPUT_TV","CONTROL_POWER","READ_APP_STATUS","READ_CURRENT_CHANNEL","READ_INPUT_DEVICE_LIST","READ_NETWORK_STATE","READ_RUNNING_APPS","READ_TV_CHANNEL_LIST","WRITE_NOTIFICATION_TOAST","READ_POWER_STATE","READ_COUNTRY_INFO"],"signatures":[{"signatureVersion":1,"signature":"eyJhbGdvcml0aG0iOiJSU0EtU0hBMjU2Iiwia2V5SWQiOiJ0ZXN0LXNpZ25pbmctY2VydCIsInNpZ25hdHVyZVZlcnNpb24iOjF9.hrVRgjCwXVvE2OOSpDZ58hR+59aFNwYDyjQgKk3auukd7pcegmE2CzPCa0bJ0ZsRAcKkCTJrWo5iDzNhMBWRyaMOv5zWSrthlf7G128qvIlpMT0YNY+n/FaOHE73uLrS/g7swl3/qH/BGFG2Hu4RlL48eb3lLKqTt2xKHdCs6Cd4RMfJPYnzgvI4BNrFUKsjkcu+WD4OO2A27Pq1n50cMchmcaXadJhGrOqH5YmHdOCj5NSHzJYrsW0HPlpuAx/ECMeIZYDh6RMqaFM2DXzdKX9NmmyqzJ3o/0lkk/N97gfVRLW5hA29yeAwaCViZNCP8iC9aO0q9fQojoa7NQnAtw=="}]}}}';
	    if (empty($this->ReadPropertyString("WebOSKey"))) {
	        $handshake =    '{"type":"register","id":"register_0","payload":{"forcePairing":false,"pairingType":"PROMPT","manifest":{"manifestVersion":1,"appVersion":"1.1","signed":{"created":"20140509","appId":"com.lge.test","vendorId":"com.lge","localizedAppNames":{"":"LG Remote App","ko-KR":"Ã«Â¦Â¬Ã«ÂªÂ¨Ã¬Â»Â¨ Ã¬â€¢Â±","zxx-XX":"Ã�â€ºÃ�â€œ RÃ‘ï¿½Ã�Â¼otÃ‘ï¿½ AÃ�Å¸Ã�Å¸"},"localizedVendorNames":{"":"LG Electronics"},"permissions":["TEST_SECURE","CONTROL_INPUT_TEXT","CONTROL_MOUSE_AND_KEYBOARD","READ_INSTALLED_APPS","READ_LGE_SDX","READ_NOTIFICATIONS","SEARCH","WRITE_SETTINGS","WRITE_NOTIFICATION_ALERT","CONTROL_POWER","READ_CURRENT_CHANNEL","READ_RUNNING_APPS","READ_UPDATE_INFO","UPDATE_FROM_REMOTE_APP","READ_LGE_TV_INPUT_EVENTS","READ_TV_CURRENT_TIME"],"serial":"2f930e2d2cfe083771f68e4fe7bb07"},"permissions":["LAUNCH","LAUNCH_WEBAPP","APP_TO_APP","CLOSE","TEST_OPEN","TEST_PROTECTED","CONTROL_AUDIO","CONTROL_DISPLAY","CONTROL_INPUT_JOYSTICK","CONTROL_INPUT_MEDIA_RECORDING","CONTROL_INPUT_MEDIA_PLAYBACK","CONTROL_INPUT_TV","CONTROL_POWER","READ_APP_STATUS","READ_CURRENT_CHANNEL","READ_INPUT_DEVICE_LIST","READ_NETWORK_STATE","READ_RUNNING_APPS","READ_TV_CHANNEL_LIST","WRITE_NOTIFICATION_TOAST","READ_POWER_STATE","READ_COUNTRY_INFO"],"signatures":[{"signatureVersion":1,"signature":"eyJhbGdvcml0aG0iOiJSU0EtU0hBMjU2Iiwia2V5SWQiOiJ0ZXN0LXNpZ25pbmctY2VydCIsInNpZ25hdHVyZVZlcnNpb24iOjF9.hrVRgjCwXVvE2OOSpDZ58hR+59aFNwYDyjQgKk3auukd7pcegmE2CzPCa0bJ0ZsRAcKkCTJrWo5iDzNhMBWRyaMOv5zWSrthlf7G128qvIlpMT0YNY+n/FaOHE73uLrS/g7swl3/qH/BGFG2Hu4RlL48eb3lLKqTt2xKHdCs6Cd4RMfJPYnzgvI4BNrFUKsjkcu+WD4OO2A27Pq1n50cMchmcaXadJhGrOqH5YmHdOCj5NSHzJYrsW0HPlpuAx/ECMeIZYDh6RMqaFM2DXzdKX9NmmyqzJ3o/0lkk/N97gfVRLW5hA29yeAwaCViZNCP8iC9aO0q9fQojoa7NQnAtw=="}]}}}';
	    } else {
	        $handshake = str_replace('HANDSHAKEKEYGOESHERE',$this->ReadPropertyString("WebOSKey"),$handshake);
	    }
	    $this->SendData($handshake);
	}
}
?>
