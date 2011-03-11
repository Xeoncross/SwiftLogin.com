<?php
/**
 * Verify a callback request
 */
class Verify_Controller_Index extends SwiftLogin_Controller
{
	/*
	 * Respond
	 */
	public function action()
	{
		//die(__LINE__);
		
		// Don't even bother
		if(get('key') AND is_string(get('key')) AND mb_strlen(get('key')) === 32)
		{
			$this->load_database();
			
			$user = $this->db->row('SELECT * FROM "user" WHERE login_key = ?', array(get('key')));
			
			if($user)
			{
				$response = array(
					'email' => $user->email,
					'rating' => 0,
					'timestamp' => time(),
				);
				
				//$this->db->query('UPDATE user SET login_key = \'\' WHERE user_id = ?', array($user->id));
				//$this->db->update('user', array('login_key' => ''), array('user_id' => $user->id));
				
				die(json_encode($response));
			}
		}
		
		// Bad request
		header('HTTP/1.1 500 Internal Server Error');
		die(json_encode(array('error' => 'Invalid Request')));
	}

}
