<?php
/*
 * ?callback_id=13&callback_url=http://full.domain.tld/user/login?from=emaillogin
 */
class Controller_Login extends SwiftLogin_Controller
{
	public $template = 'thin_layout';

	/*
	 * Load a view that shows a welcome message
	 */
	function index()
	{
		// Fail
		if( ! get('url'))
		{
			$this->content = new View('login/no_callback', 'swiftlogin');
			return;
		}
		
		// Start the user session
		Session::start(config('session'));
		
		/* 
		 * Posible Ways a User Loads Page
		 * 
		 * Already Logged in (done)
		 * Not Logged in
		 *	* Loading Form
		 *	* Submitting Form
		 *		* Invalid Input
		 *		* Valid Input
		 *			* Existing Account
		 *			* New Account
		 * 
		 */
		
		if( ! $url = $this->valid_callback(get('url')))
		{
			$this->content = new View('login/no_callback', 'swiftlogin');
			return;
		}
		
		// Save it to the session!
		$_SESSION['callback_url'] = $url[0];
		$_SESSION['callback_domain'] = $url[1];
		
		/////////////////////////////////////////////
		
		
		// If the user is already logged in we don't need to do anything!
		if(session('user_id'))
		{
			redirect('/allow');
			Session::save();
			exit();
		}
		
		
		/////////////////////////////////////////////
		
		
		
		
		// Make sure this IP isn't being bad here
		
		
		
		
		/////////////////////////////////////////////
		
		$default_error = 'Sorry, there was an error, please resubmit the form.';
		$error = '';
		
		// If the form was submitted
		if($_POST)
		{
			$this->load_database();
			
			$email_input = session('email_input');
			
			if( ! $email_input OR ! is_string($email_input))
			{
				$error = $default_error;
			}
			elseif( ! post($email_input) OR ! $email = $this->parse_email(post($email_input)))
			{
				$error = 'Sorry, please enter a valid email.';
			}
			/*
			elseif ($captcha AND ($captcha_error = recaptcha_check(config('private_key', 'recaptcha'))))
			{
				$error = $captcha_error;
			}*/
			elseif ( ! post('token') OR session('token') !== post('token'))
			{
				$error = $default_error;
			}
			elseif ( ! post('password') OR mb_strlen(post('password')) < 8 OR mb_strlen(post('password')) > 100)
			{
				$error = 'Your password must be at least 8 characters long.';
			}
			elseif($this->password_strength(post('password')) < 3)
			{
				$error = 'Your password is not secure enough. Try mixing upper and '
				.' lower case letters, numbers, or special characters such as the dollar sign '
				.'($) or underscore (_). The more complex your password - the harder it will be for someone to guess!';
			}
			elseif($this->banned_email_domain($email))
			{
				$error = 'Sorry, that email is from a domain that has been blocked for spamming. Please use another email.';
			}
			else
			{
				// First we need to remove bad accounts (users have one day to verify their account!)
				$time = date("Y-m-d H:i:s", time() - (60 * 60 * 24));
				$this->db->delete('DELETE FROM "user" WHERE "created" < ? AND "activation_key" != \'\'', array($time));
				//Emaillogin_Model_User::remove_unactivated_accounts();
				
				// Try to find this user
				//$user = Emaillogin_Model_User::getByEmail($email);
				$user = $this->db->row('SELECT * FROM user WHERE email = ?', array($email));
		
				// If no user was found then create one!
				if( ! $user)
				{
					$user_salt = token();
					$activation_key = token();
					$domain = $this->get_domain(end(explode('@', $email)));
					
					$user = array(
						'email' => $email,
						'domain_id' => $domain->id,
						'password' => $this->hash_password($user_salt. post('password')),
						'user_salt' => $user_salt,
						'activation_key' => $activation_key,
						'created' => date("Y-m-d H:i:s")
					);
					$this->db->insert('user', $user);
					
					// Build the URL
					$url = DOMAIN. '/confirm?key='. $activation_key;
						
					// Load the email message
					$message = new View('login/email', 'swiftlogin');
					$message->set(array('email' => $email, 'ip_address' => ipaddress::get(), 'url' => $url));
						
					// Send them an email!
					//send_email($email, $message);
					$subject = 'Registration Verification - Emaillogin.net';
					$this->email($email, $subject, $message);
						
					// Show the user a message
					$this->content = new View('login/email_sent', 'swiftlogin');
					$this->content->email = $email;
					return;
						
				}
				else // See if they are verified
				{
					// They still need to activate their account!
					if($user->activation_key)
					{
						$this->content = new View('login/email_already_sent', 'swiftlogin');
						$this->content->email = $email;
						return;
					}
					// Invalid password !?
					elseif ($user->password !== $this->hash_password($user->user_salt. post('password')))
					{
						$error = 'Invalid email or password';
					}
					else
					{
						// @todo: log a successful login
						
						// User is now logged in!
						$this->log_user_in($user);
						
						// Redirect before setcookie
						redirect('/allow');
						Session::save();
		
						exit();
					}
				}
			}
			
			// If the login failed - then record it!
			if($error)
			{
				message('error', $error);
				
				$email = isset($email) ? $email : NULL;
				
				$this->db->insert('attempt_login', array('ip_address' => $this->ip_address(), 'email' => $email, 'created'=> date("Y-m-d H:i:s")));
				
				/*
				$login_attempt = new Emaillogin_Model_LoginAttempt();
				$login_attempt->ip_address = $this->ip2int(ipaddress::get());
				$login_attempt->email = $email;
				$login_attempt->save();
				*/
			}
		}
		
		
		/////////////////////////////////////////////
		
		// Create four random inputs (one is the true email)
		$inputs = array();
		for($x=0;$x<7;++$x)
		{
			$inputs[] = substr(base64_encode(token()), 0, 15);
		}
		
		$email_input = $inputs[array_rand($inputs)];
		$_SESSION['email_input'] = $email_input;
		$_SESSION['token'] = token();
		
		$this->content = new View('login/form', 'swiftlogin');
		$this->content->inputs = $inputs;
		$this->content->email_input = $email_input;
		
		Session::save();
		
	}
	
	/**
	 * Reveal the TRUE email input field
	 */
	public function ajax_input()
	{
		Session::start(config('session'));
		
		if(AJAX_REQUEST AND post('token') AND post('token') == session('token'))
		{
			print session('email_input');
		}
		exit();
	}
	
	/**
	 * Insure the callback URL given is a valid and safe URL
	 * 
	 * @param string $url
	 * @param object $key
	 */
	protected function valid_callback($url)
	{
		if( ! is_string($url) OR mb_strlen($url) > 300) return;
		
		preg_match('/^https?:\/\/(www\.)?(([a-z0-9\-]{2,60}\.)+(loc|com|net|org|edu|name|jp|uk|de|it|br|fr|es|ca|au|us|eu))((\?|\/)[a-z&%0-9?=+~_\-\.\/#]*)?$/i',$url,$matches);
	
		if( ! empty($matches[0]) AND ! empty($matches[2]) AND strlen($matches[2]) <= 60)
		{
			return array($matches[0], $matches[2]);
		}
		
	}

}
