<?php

App::uses('HttpSocket', 'Network/Http');
App::uses('AppController', 'Controller');
App::import('Controller', 'Devices');

class UsersController extends AppController {
	public $helper = array('Html', 'Form');
	var $uses = array('User', 'Device');
	
	public function login() {
		return $this->redirect(array('action' => 'add'));
	}
    
    public function add() {
        $this->layout = 'main';
        
        if ($this->request->is('post')) {
            $this->User->create();
            if ($this->User->save($this->request->data)) {
                $this->Session->setFlash(__('The Account has been saved.'));
                return $this->redirect(array('action' => 'view', 6));
            }
            $this->Session->setFlash(__('Unable to add the Account.'));
        }
        
        if($this->request->is('get') && isset($_GET['donationId'])) {
			$Devices = new DevicesController;
			$Devices->clearLock(1);
			$this->sendEmail("Donation Made", "Donation made to Laptops for Disabled! It's not a replacement but we hope it helps! Make sure to sign up your next system! ;)");
			$this->Session->setFlash(__('Your donation is much appreciated! Join Droplock today!'));
			return $this->redirect(array('action' => 'add'));
		}
    }

	public function pay(){
		require_once '../../vendor/braintree/braintree_php/lib/Braintree.php';
		Braintree_Configuration::environment('sandbox');
		Braintree_Configuration::merchantId('YOUR_MERCHANT_ID');
		Braintree_Configuration::publicKey('YOUR_PUBLIC_KEY');
		Braintree_Configuration::privateKey('YOUR_PRIVATE_KEY');	

		$Devices = new DevicesController;
		$device = $this->Device->findById(1);
		
		$clientToken = Braintree_ClientToken::generate();
		$this->set('clientToken', $clientToken);
		$this->set('laptopValue', $device['Device']['value']);
	}

	public function transaction(){
		$this->autoRender = false;
		$Devices = new DevicesController;
		
		require_once '../../vendor/braintree/braintree_php/lib/Braintree.php';
		Braintree_Configuration::environment('sandbox');
		Braintree_Configuration::merchantId('YOUR_MERCHANT_ID');
		Braintree_Configuration::publicKey('YOUR_PUBLIC_KEY');
		Braintree_Configuration::privateKey('YOUR_PRIVATE_KEY');
		
		
		if($this->request->is('post')) {
			$nonce = $_POST["payment_method_nonce"];
			$device = $this->Device->findById(1);
			
			$result = Braintree_Transaction::sale(array(
				'amount' => $device['Device']['value'],
				'paymentMethodNonce' => $nonce
			));
			
			if ($result->success) {
				$Devices->clearLock(1);
				$this->Session->setFlash(__('Your purchase has been appreciated! Join Droplock today!'));
	       		$this->sendEmail("Payment Received", "Payment received for ".$device['Device']['name']."! It's not a replacement but we hope it helps! Make sure to sign up your next system! ;)");
				return $this->redirect(array('action' => 'add'));
			} else if ($result->transaction) {
			    $this->Session->setFlash(__('Error processing transaction, try again.'));
			    return $this->redirect(array('action' => 'pay'));
			} else {
			    $this->Session->setFlash(__('Error processing transaction, try again.'));
			    return $this->redirect(array('action' => 'pay'));
			}
		}	
	}

	public function sendEmail($status = null, $message = null){
		$url = 'https://api.sendgrid.com/';
		$user = 'YOUR_USERNAME';
		$pass = 'YOUR_PASSWORD';

		$params = array(
   			'api_user'  => $user,
   			'api_key'   => $pass,
    			'to'        => 'test@test.com',
    			'subject'   => 'Droplock: Status Changed[ '.$status.' ]',
    			'text'      => $message,
    			'from'      => 'test@test.com',
  		);


		$request =  $url.'api/mail.send.json';

		// Generate curl request
		$session = curl_init($request);
		// Tell curl to use HTTP POST
		curl_setopt ($session, CURLOPT_POST, true);
		// Tell curl that this is the body of the POST
		curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
		// Tell curl not to return headers, but do return the response
		curl_setopt($session, CURLOPT_HEADER, false);
		// Tell PHP not to use SSLv3 (instead opting for TLS)
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		// obtain response
		$response = curl_exec($session);
		curl_close($session);
	}

}
