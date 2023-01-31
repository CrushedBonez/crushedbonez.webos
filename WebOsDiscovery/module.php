<?
class WebOsDiscovery extends IPSModule
{
	public function Create()
	{
		//Never delete this line!
		parent::Create();
        
	}
    
	public function ApplyChanges()
	{
	    //Never delete this line!
	    parent::ApplyChanges();
	}  

	public function GetConfigurationForm()
	{
		$ModuleForm = json_decode(file_get_contents(__DIR__ . "/form.json"), true);
		$TVs = $this->mDnsDiscoverLGTV();
		$FormEntries = [];
		foreach ($TVs as $TV) {
			$Entry = [
				'Addr'		=> $TV['Addr'],
				'MAC'		=> $TV['MAC'],
				'Name'		=> $TV['Name'],
				'Host'		=> $TV['Host'],
				'instanceID'	=> $this->GetWebOSInstance($TV['MAC'])
			];

			$Entry['create'] = [
				[
					'moduleID' => '{4B55D292-0BD0-4F8C-A40D-2A8B5E782EEF}',
					'name' => "WebOS TV " . $TV['Name'],
					'configuration' => [
						'MAC' => $TV['MAC']
					]
				],
				[
					'moduleID' => '{D68FD31F-0E90-7019-F16C-1949BD3079EF}',
					'name' => "WS Client " . $TV['Name'],
					'configuration' => [
						'URL'		=> 'ws://' . $TV['Addr'] . ":3000",
						'Active'	=> true
					] 
				]
			];
			$FormEntries[] = $Entry;
		}
		$ModuleForm['actions'][0]['values'] = $FormEntries;
		return json_encode($ModuleForm);	
	}

	public function mDnsDiscoverLGTV() {
		$TVs = [];
		// _airplay._tcp => deviceid=, 9
		// _display._tcp => p2pMAC=, 7
		$mDnsInstances = IPS_GetInstanceListByModuleID('{780B2D48-916C-4D59-AD35-5A429B2355A5}');
		$mDnsResults = ZC_QueryServiceType($mDnsInstances[0], "_display._tcp.", "");

		foreach ($mDnsResults as $key => $value) {
			$LGTV = [];
			$device = ZC_QueryService($mDnsInstances[0], $value['Name'], '_display._tcp', 'local.');
			if(!empty($device)) {
				if (preg_match('/webOS/i', $value['Name'], $matches)) {
					$LGTV['Name'] = $value['Name'];
					$LGTV['Host'] = $device[0]['Host'];
					$LGTV['Addr'] = $device[0]['IPv4'][0];
					foreach ($device[0]['TXTRecords'] as $TXTRecord) {
						if (strstr($TXTRecord, "p2pMAC=")) {
							$LGTV['MAC'] = substr($TXTRecord, 7);
						}
					}
					array_push($TVs, $LGTV);
				}
			}
		}
		return $TVs;
	}

	public function GetWebOSInstance($MAC) {
		$InstanceIDs = IPS_GetInstanceListByModuleID('{4B55D292-0BD0-4F8C-A40D-2A8B5E782EEF}');
		foreach ($InstanceIDs as $ID) {
			if (IPS_GetProperty($ID, 'MAC') == $MAC) {
				return $ID;
			}
		}
		return 0;
	}
}
?>
