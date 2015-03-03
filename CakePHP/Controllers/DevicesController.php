<?php

App::uses('HttpSocket', 'Network/Http');
App::import('Controller', 'Users');

class DevicesController extends AppController {
	public $helper = array('Html', 'Form');
	var $uses = array('Device', 'Mug', 'Log', 'User');
	
	public function grabRecentFiles($id = null) {
		$this->autoRender = false;
		
		$HttpSocket = new HttpSocket();
		$results = $HttpSocket->get('https://api-content.dropbox.com/1/files/auto/mugshot.png',
			array(
				'access_token' => 'YOUR_ACCESS_TOKEN'
			)
		);
		
		$date = date('Y_m_d_H.i');
		$imageName = 'mugshot_'.$date.'.png';
		$fp = fopen(WWW_ROOT.'mugshots/'.$imageName, 'a');
		fwrite($fp, $results);
		fclose($fp);
		
		$this->Mug->create();
		$data = array('user_id' => $id, 'name' => $imageName);
		$this->Mug->save($data);
		
		$HttpSocket = new HttpSocket();
		$results = $HttpSocket->get('https://api-content.dropbox.com/1/files/auto/NetworkInfo.txt',
			array(
				'access_token' => 'YOUR_ACCESS_TOKEN'
			)
		);
		
		$logName = 'NetworkInfo_'.$date.'.txt';
		$fp = fopen(WWW_ROOT.'logs/'.$logName, 'a');
		fwrite($fp, $results);
		fclose($fp);
		
		$this->Log->create();
		$data = array('user_id' => $id, 'name' => $logName);
		$this->Log->save($data);
	}
	
	public function release($id = null) {
		$this->autoRender = false;
		$device = $this->Device->findById($id);
		$this->Device->id = $device['Device']['id'];
		$this->Device->saveField('status', 0);
	}
	
	public function lockReleased($id = null) {
		$this->autoRender = false;
		
		$device = $this->Device->findById($id);
		
		if($device['Device']['status']) {
			return 'false';	//unsafe
		} else {
			return 'true'; //safe
		}
	}
	
	public function isPaidFor($id) {
		$this->autoRender = false;
		
		$device = $this->Device->findById($id);
		
		if($device['Device']['paid_for']) {
			return 'false';	//unsafe
		} else {
			return 'true'; //safe
		}
	}
	
	//Check if System is locked
	public function checkLock() {
		$this->autoRender = false;
		
		$HttpSocket = new HttpSocket();
		$results = $HttpSocket->get('https://api.dropbox.com/1/search/auto/',
			array(
				'query' => 'SystemIsLocked',
				'access_token' => 'YOUR_ACCESS_TOKEN'
			)
		);
		
		if(empty(json_decode($results->body))) {
			return false;
		}
		
		return true;
	}
	
	public function setLock($id = null) {
		$this->autoRender = false;
		
		$device = $this->Device->findById($id);
		$this->Device->id = $device['Device']['id'];		
		
		$Users = new UsersController;
		
		if($device['Device']['status']) {
			$this->Device->saveField('status', 0);	//safe
			$Users->sendEmail("Stopped Tracking", "We have stopped tracking ".$device['Device']['name']."'s location.");
		} else {
			$this->Device->saveField('status', 1); //unsafe
			$Users->sendEmail("Starting Tracking", "We are now tracking ".$device['Device']['name']."'s location! Make sure to visit Droplock to see more details!");
		}
	}
	
	public function clearLock($id = null) {
		$this->autoRender = false;
		
		$device = $this->Device->findById($id);
		$this->Device->id = $device['Device']['id'];	
		$this->Device->saveField('status', 0);	//safe
		$this->Device->saveField('paid_for', 0);	//safe
	}

	public function my_devices($id = null) {
		$devices = $this->Device->find('all', array(
        	'conditions' => array('Device.user_id' => $id)
		));
		
		$mugShots = $this->Mug->find('all', array(
        	'conditions' => array('Mug.user_id' => $id),
        	'order' => array('Mug.created' => 'desc')
        	
		));	
		
		$Logs = $this->Log->find('all', array(
        	'conditions' => array('Log.user_id' => $id),
        	'order' => array('Log.created' => 'desc')
		));
		
		$cleanLogs = array();
		
		foreach($Logs as $log) {
			$filepath = WWW_ROOT.'logs/'.$log['Log']['name'];
			
			$date = explode('_', $log['Log']['name']);
			$time = explode('.', $date[4]);
			
			$timeStamp = $date['2'].'/'.$date['3'].'/'.$date['1'].' '.$time[0].':'.$time[1];
			
			$longLat = '';
			$contents = file_get_contents($filepath);
			
			$pattern = preg_quote('loc', '/');
			$pattern = "/^.*$pattern.*\$/m";
			if(preg_match_all($pattern, $contents, $matches)){
			   $longLat = implode("\n", $matches[0]);
			}
			
			$longLat = explode('"', $longLat);
			$longLats = $longLat[3];
			
			$city = array();
			$contents = file_get_contents($filepath);
			$pattern = preg_quote('city', '/');
			$pattern = "/^.*$pattern.*\$/m";
			if(preg_match_all($pattern, $contents, $matches)){
			   $city = implode("\n", $matches[0]);
			}
			$city = explode('"', $city);
			$city = $city[3];
			
			$region = array();
			$contents = file_get_contents($filepath);
			$pattern = preg_quote('region', '/');
			$pattern = "/^.*$pattern.*\$/m";
			if(preg_match_all($pattern, $contents, $matches)){
			   $region = implode("\n", $matches[0]);
			}
			$region = explode('"', $region);
			$region = $region[3];
			
			
			$ip = array();
			$contents = file_get_contents($filepath);
			$pattern = preg_quote('ip', '/');
			$pattern = "/^.*$pattern.*\$/m";
			if(preg_match_all($pattern, $contents, $matches)){
			   $ip = implode("\n", $matches[0]);
			}
			$ip = explode('"', $ip);
			$ip = $ip[3];
			
			$ssid = array();
			$contents = file_get_contents($filepath);
			$pattern = preg_quote('SSID', '/');
			$pattern = "/^.*$pattern.*\$/m";
			if(preg_match_all($pattern, $contents, $matches)){
			   $ssid = implode("\n", $matches[0]);
			}
			$ssid = explode(':', $ssid);
			$ssid = $ssid[sizeof($ssid) - 1];
			
			array_push($cleanLogs, array(
				'longLat' => $longLats,
				'city' => $city,
				'region' => $region,
				'ip' => $ip,
				'ssid' => $ssid,
				'dateTime' => $timeStamp
			));
		}
		
		$this->set('main', $devices[0]);
		$this->set('mugShots', $mugShots);
		$this->set('cleanLogs', $cleanLogs);
	}
}