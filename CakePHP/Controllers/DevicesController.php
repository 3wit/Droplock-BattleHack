<?php

App::uses('HttpSocket', 'Network/Http');
App::import('Controller', 'Users');

class DevicesController extends AppController {
	public $helper = array('Html', 'Form');
	var $uses = array('Device', 'Mug', 'Log', 'User');
	public $access_token = 'YOUR_DROPBOX_ACCESS_TOKEN';
	
	public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('grabRecentFiles', 'lockReleased', 'isPaidFor');
    }
	
	/*
		add
		- allows for new devices
		- currently locked to one device
	*/
	public function add() {
		$brands = array(
			'Acer', 'Apple', 'Asus', 'Dell', 'HP', 'Lenovo', 'MSI', 'Samsung', 'Sony', 'ThinkPad', 'Toshiba'
		);
		
		if($this->request->is('post')) {
			$this->request->data['Device']['brand'] = $brands[$this->request->data['Device']['brand']];
			$this->request->data['Device']['user_id'] = $this->Auth->user('id');
            		
            		$this->Device->create();
            		
            		if ($this->Device->save($this->request->data)) {
                		$this->Session->setFlash(__('Your post has been saved.'));
                		return $this->redirect(array('action' => 'index'));
            		}
            		$this->Session->setFlash(__('Unable to add your post.'));
        	}
	}
	
	/*
		add
		- edits a device
		- TODO
	*/
	public function edit() {
		
	}
	
	/*
		grabContent
		- retrieves files from the Images and Logs directories
	*/
	public function grabContent($type = null) {
		$HttpSocket = new HttpSocket();
		$results = $HttpSocket->get('https://api.dropbox.com/1/metadata/auto/'.$type,
			array(
				'access_token' => $this->access_token
			)
		);
		
		$results = json_decode($results->body);
		$results = array_reverse((array) $results->contents);
		return $results;
	}
	
	/*
		grabRecent
		- retrieves last image taken's thumbnail or recent log
		- Changed to thumbnail to speed up page load
	*/
	public function grabRecent($type = null, $object = null) {
		$recentPath = $object[0]->path;
		$HttpSocket = new HttpSocket();

		if($type == 'Image') {
			$image = $HttpSocket->get('https://api-content.dropbox.com/1/thumbnails/auto'.$recentPath,
				array(
					'access_token' => $this->access_token,
					'format' => 'png',
					'size' => 'l'
				)
			);
			
			return base64_encode($image->body);	
		} else if($type == 'Log') {
			$log = $HttpSocket->get('https://api-content.dropbox.com/1/files/auto'.$recentPath,
				array(
					'access_token' => $this->access_token
				)
			);
	
			return json_decode($log->body);
		}
	}
	
	/*
		index
		- front facing view of your device
	*/
	public function index() {
		$images = $this->grabContent('Images');
		$recentSnapshot = $this->grabRecent('Image', $images);
		
		$logs = $this->grabContent('Logs');
		$recentLog = $this->grabRecent('Log', $logs);
		
		$devices = $this->Device->find('all', array(
        		'conditions' => array(
        			'Device.user_id' => $this->Auth->user('id')
        		)
		));

		$this->set('devices', $devices);
		$this->set('recentSnapshot', $recentSnapshot);
		$this->set('recentLog', $recentLog);
		$this->set('ip', $this->request->clientIp());
	}
	
	/*
		release
		- flags the device as being found
		- Manual call for Pebble
		- TODO
	*/
	public function release() {
		$this->autoRender = false;
		
		$device = $this->Device->findById(15);
		$this->Device->id = $device['Device']['id'];
		$this->Device->saveField('status', 0);
	}
	
	/*
		clearLock
		- flags the device as being found and pid for
		- TODO
	*/
	public function clearLock($id = null) {
		$this->autoRender = false;
		
		$device = $this->Device->findById($id);
		$this->Device->id = $device['Device']['id'];	
		$this->Device->saveField('status', 0);	//safe
		$this->Device->saveField('paid_for', 0); //safe
	}

	/*
		lockReleased
		- Check for bash script to see if device is still locked
	*/
	public function lockReleased() {
		$this->autoRender = false;
		
		$device = $this->Device->findById(15);
		if($device['Device']['status']) {
			return 'false';	//unsafe
		} else {
			return 'true'; //safe
		}
	}
	
	/*
		isPaidFor
		- Check for bash script to see if device is still locked
		- TODO
	*/
	public function isPaidFor() {
		$this->autoRender = false;
		
		$device = $this->Device->findById(15);
		if($device['Device']['paid_for']) {
			return 'false';	//unsafe
		} else {
			return 'true'; //safe
		}
	}
	
	/*
		setLock
		- Lock for front-facing api
		- TODO
	*/
	public function setLock($id = null, $ip = null) {
		$this->autoRender = false;
		$Users = new UsersController;
		
		$device = $this->Device->findById($id);
		$this->Device->id = $device['Device']['id'];
		if($device['Device']['status']) {
			$this->Device->saveField('status', 0);	//safe
			$Users->sendEmail("Stopped Tracking", "We have stopped tracking ".$device['Device']['name']."'s location.", $ip);
		} else {
			$this->Device->saveField('status', 1); //unsafe
			$Users->sendEmail("Starting Tracking", "We are now tracking ".$device['Device']['name']."'s location! Make sure to visit Droplock to see more details!", $ip);
		}
	}
}
