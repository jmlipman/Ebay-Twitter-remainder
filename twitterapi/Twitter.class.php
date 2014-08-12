<?php

	/**
	 * Twitter Class
	 *
	 * This class helps to directly send request to your Tiwtter application, since the OAuth has been
	 * released and the old method (Basic Authentication) has been depricated.
	 * It uses OAuth library developed by @abraham and it is free to download and use, which can be used for free by this url:
	 *
	 * Download Page for TwitterAuth: http://github.com/abraham/twitteroauth
	 * Other Twitter OAuth Libraries: http://dev.twitter.com/pages/oauth_libraries
	 *
	 * @class Twitter
	 * @author Arlind Nushi <arlindd@gmail.com>
	 */
	
	// Include Configuration file and OAuth library (do not change these declarations)
	include("twitteroauth/twitteroauth.php");
	include("config.php");
	
	
	/**
	  * @class Twitter
	  */
	  
	class Twitter
	{
		private $twitter_auth_instc;
		private $account_info;
		
		private $authenticated;
		
		private $consumer_key;
		private $consumer_secret;
		
		private $oauth_token;
		private $oauth_token_secret;
		
		/**
		 * Overwrite access tokens and consumer keys (if you have others), or leave empty and by default they will be replaced
		 * with those ones that can be found on configuration file /config.php
		 *
		 * @param $consumer_key - Consumer key
		 * @param $consumer_secret - Consumer secret
		 * @param $oauth_token - Access Token (oauth_token)
		 * @param $oauth_token_secret - Access Token Secret (oauth_token_secret)
		 */
		 
		public function __construct($consumer_key = '', $consumer_secret = '', $oauth_token = '', $oauth_token_secret = '')
		{
			if( $consumer_key == '' )
				$consumer_key = CONSUMER_KEY;
				
			if( $consumer_secret == '' )
				$consumer_secret = CONSUMER_SECRET;
				
			if( $oauth_token == '' )
				$oauth_token = OAUTH_TOKEN;
				
			if( $oauth_token_secret == '' )
				$oauth_token_secret = OAUTH_TOKEN_SECRET;
			
			$this->setConsumerKey($consumer_key);
			$this->setConsumerSecret($consumer_secret);
			$this->setAuthToken($oauth_token);
			$this->setAuthTokenSecret($oauth_token_secret);
		}
		
		/**
		 * Get/Set methods for Consumer Keys and OAuth tokens
		 */
		
		public function setConsumerKey($consumer_key)
		{
			$this->consumer_key	= $consumer_key;
		}
		
		public function getConsumerKey()
		{
			return $this->consumer_key;
		}
		
		public function setConsumerSecret($consumer_secret)
		{
			$this->consumer_secret	= $consumer_secret;
		}
		
		public function getConsumerSecret()
		{			
			return $this->consumer_secret;
		}
		
		public function setAuthToken($oauth_token)
		{
			$this->oauth_token	= $oauth_token;
		}
		
		public function getAuthToken()
		{			
			return $this->oauth_token;
		}
		
		public function setAuthTokenSecret($oauth_token_secret)
		{
			$this->oauth_token_secret	= $oauth_token_secret;
		}
		
		
		public function getAuthTokenSecret()
		{
			
			return $this->oauth_token_secret;
		}
		
		
		/**
		 * Authenticate twitter user
		 * It returns certain status numbers which means:
		 *
		 * 0 - insufficient authentication information
		 * 1 - Incorrect signature
		 */
		 
		public function authenticate()
		{
			$this->authenticated = false;
			
			$consumer_key		= $this->getConsumerKey();
			$consumer_secret	= $this->getConsumerSecret();
			$oauth_token		= $this->getAuthToken();
			$oauth_token_secret	= $this->getAuthTokenSecret();
			
			
			if( empty($consumer_key) || empty($consumer_secret) || empty($oauth_token) || empty($oauth_token_secret) )
			{
				echo $this->error(0);
				return 0;
			}
			
			$this->twitter_auth_instc = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
			
			$info = $this->twitter_auth_instc->get('account/verify_credentials');
			
			$this->account_info = $info;
			
			
			if( $info->error == 'Incorrect signature' )
			{
				echo $this->error(1);
				return -1;
			}
			else
			if( $info->error == 'Invalid / used nonce' )
			{
				$this->twitter_auth_instc = new TwitterOAuth($consumer_key, $consumer_secret);
				$url = $this->twitter_auth_instc->getAuthorizeURL($oauth_token);
				header("Location: $url");
			}
			else
			if( strlen($info->error) > 0 )
			{
				echo $this->error(3, $info->error);
				return -2;
			}
			
			$this->authenticated = true;

			return 1;
		}
		
		
		/**
		 * Authentication for all users (with their approval for profile information access)
		 */
		
		public function requestAuthentication($callback_url = '')
		{
			if( !$callback_url )
				$callback_url = $this->oauthCleanURL( $this->getCurrentURL() );
			
			$consumer_key		= $this->getConsumerKey();
			$consumer_secret	= $this->getConsumerSecret();
				
			$oauth_token		= $_SESSION['oauth_token'];
			$oauth_token_secret	= $_SESSION['oauth_token_secret'];
			$oauth_verifier		= $_REQUEST['oauth_verifier'];
			
			$this->twitter_auth_instc = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
			//echo "Oauth_token: ".$oauth_token . "<br>";
			//echo "Outh_token_secret: ".$oauth_token_secret . "<br>";
			# Assumption that user can be already logged in
			if( $_REQUEST['oauth_verifier'] )
			{
				$url = $this->oauthCleanURL( $callback_url );
				
				$access_token = $this->twitter_auth_instc->getAccessToken( $_REQUEST['oauth_verifier'] );
				
				if( !$access_token['user_id'] )
				{
					$_SESSION['oauth_token'] = null;
					$_SESSION['oauth_token_secret'] = null;
					$_SESSION['oauth_verifier'] = null;
					
					session_destroy();
					header( "Location: $url" );
					return;
				}
				else
				{
					$info = $this->twitter_auth_instc->get('account/verify_credentials');
					$this->authenticated = true;
					$this->account_info = $info;
				}
				
				return;
			}
			
			if( $_GET['oauth_verifier'] )
			{
				$_SESSION['oauth_verifier'] = $oauth_verifier = $_GET['oauth_verifier'];
			}
			
			if( !$oauth_token || !$oauth_token_secret || !$oauth_verifier )
			{
				$this->twitter_auth_instc = new TwitterOAuth($consumer_key, $consumer_secret);
				$consumer_key		= $this->getConsumerKey();
				$consumer_secret	= $this->getConsumerSecret();
				
				$request_token = $this->twitter_auth_instc->getRequestToken( $callback_url );
				
				$_SESSION['oauth_token']		= $oauth_token			= $request_token['oauth_token'];
				$_SESSION['oauth_token_secret']	= $oauth_token_secret	= $request_token['oauth_token_secret'];
				
				if( !$oauth_token )
				{
					session_destroy();
					echo '<a href="'.$this->getCurrentURL().'">Reload page</a>';
					exit;
				}
				
				$url = $this->twitter_auth_instc->getAuthorizeURL($oauth_token);
				header("Location: $url");
			}
			
			$access_token = $this->twitter_auth_instc->getAccessToken( $oauth_verifier );
			$user_id = $access_token['user_id'];
			
		}
		
		
		/**
		 * Error reporing function
		 *
		 * @error_id - Error ID
		 */
		 
		public function error($error_id, $extra_msg = '')
		{
			$defined_array = array(
									0 => "Missing information about Consumer Keys and OAuth token keys!",
									1 => "Incorrect signature - OAuth tokens and consumer keys are given invalid or they have expired!",
									2 => "You are not authenticated!",
									3 => "An error occured: <b>$extra_msg</b>"
								   );
			
			return $defined_array[$error_id];
		}
		
		
		/**
		 * Check if current user is authenticated
		 */
		
		public function isAuthenticated()
		{
			return $this->authenticated ? true : false;
		}
		
		
		/**
		 * Get TwitterOAuth Instance
		 *
		 * This is often used to make different actions 
		 * with OAuth class, for example authentication other user such a
		 * Getting OAuth token and OAuth token secret
		 */
		
		public static function getTwitterOAuthInstance()
		{
			return $this->twitter_auth_instc;
		}
		
		
		/**
		 * Get Account Details Instance
		 * To learn more about details of this instance
		 * try to dump it using this declaration:
		 * var_dump( $any_instance->getAccountDetailsInstance() );
		 */
		
		public function getAccountDetailsInstance()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info;
		}
		
		
		/**
		 * Update Status to your current twitter logged account
		 */
		
		public function setStatus($status)
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			
			return $this->twitter_auth_instc->post('1.1/statuses/update', array('status' => $status));
		}
		
		/**
		 * Delete status from specified statusId
		 * Returns true if status is deleted otherwise returns false!
		 */
		
		public function deleteStatusID($status_id)
		{
			$return = $this->twitter_auth_instc->post("statuses/destroy", array('id' => $status_id));
			
			if( $return->error )
			{
				return false;
			}
			
			return true;
		}
		
		/**
		 * @author: #jmlipman
		 * Follow (make friend) some user by his ID.
		 */
		
		public function follow($user_id)
		{
			$return = $this->twitter_auth_instc->post("friendships/create", array('user_id' => $user_id));
			
			if( $return->error )
			{
				return false;
			}
			
			return true;
		}
		
		/**
		 * @author: #jmlipman
		 * Send a DM to someone.
		 */
		public function directMessage($user_id, $texto)
		{
			$return = $this->twitter_auth_instc->post("direct_messages/new", array('user_id' => $user_id, 'text' => $texto));
			
			if( $return->error )
			{
				echo $return->error;
				return false;
			}
			
			return true;
		}

		
		/**
		 * Get followers list
		 */
		
		public function getFollowersList($limit = 10)
		{
			$followers_list = $this->twitter_auth_instc->get('statuses/followers');
			
			$rebuilded_array = array();			
			
			foreach($followers_list as $follower)
			{	
				$followers_arr = $this->parseUserArray($follower);		
				array_push($rebuilded_array, $followers_arr);
			}
			
			$rebuilded_array = array_slice($rebuilded_array, 0, $limit);
			return $rebuilded_array;
		}
		
		/**
		 * @author: @jmlipman
		 * Get your follower's IDs
		 */
		
		public function getFollowersIds($username)
		{
			$followers_list = $this->twitter_auth_instc->get('followers/ids', array('screen_name' => $username));
			return $followers_list->{'ids'};
		}
		
		/**
		 * @author: @jmlipman
		 * Get your friend's IDs
		 */
		
		public function getFriendsIds($username)
		{
			$friends_list = $this->twitter_auth_instc->get('friends/ids', array('screen_name' => $username));
			return $friends_list->{'ids'};
		}

		
		/**
		 * Get friends (users that you are following)
		 */
		
		public function getFriendsList($limit = 10)
		{
			$friends_list = $this->twitter_auth_instc->get('statuses/friends');
			
			$rebuilded_array = array();			
			
			foreach($friends_list as $friend)
			{	
				$friend_arr = $this->parseUserArray($friend);		
				array_push($rebuilded_array, $friend_arr);
			}
			
			$rebuilded_array = array_slice($rebuilded_array, 0, $limit);
			return $rebuilded_array;
		}
		
		
		public function getLatestMentions($limit = 10)
		{
			$user_timeline = $this->twitter_auth_instc->get('statuses/mentions');
			print_r($user_timeline);
			var_dump($user_timeline);
			
			$rebuilded_array = array();
			
			foreach($user_timeline as $timeline_opt)
			{				
				$timeline_status = $this->parseStatusArray($timeline_opt);
				array_push($rebuilded_array, $timeline_status);
			}
			
			return $rebuilded_array;
		}
		
		
		
		/**
		 * Get most recent posts by current authenticated user
		 */
		
		public function getLatestTweets($limit = 10)
		{
			$user_timeline = $this->twitter_auth_instc->get('statuses/user_timeline', array('count' => $limit));
			
			$rebuilded_array = array();
			
			foreach($user_timeline as $timeline_opt)
			{				
				$timeline_status = $this->parseStatusArray($timeline_opt);										
				array_push($rebuilded_array, $timeline_status);
			}
			
			return $rebuilded_array;
		}
		
		
		/**
		 * After Successfull authentication you can have access on any of below defined methods
		 *
		 * Get Specific Account Details
		 * To get more from this use ::getAccountDetailsInstance()
		 */
		
		public function profileId()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->id;
		}
		
		public function getName()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->name;
		}
		
		public function getDescription()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->description;
		}
		
		public function isVerified()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->verified;
		}
		
		public function getLocation()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->location;
		}
		
		public function getURL()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->url;
		}
		
		public function followersCount()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->followers_count;
		}
		
		public function userLanguage()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->lang;
		}
		
		public function friendsCount()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->friends_count;
		}
		
		public function screenName()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->screen_name;
		}
		
		public function profilePictureURL()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->profile_image_url;
		} 
		
		public function profileBackgroundPictureURL()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->profile_background_image_url;
		}  
		
		public function profileBackgroundColor()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->profile_background_color;
		} 
		
		public function profileTextColor()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->profile_text_color;
		} 
		
		public function listedCount()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->listed_count;
		}  
		
		public function profileLinkColor()
		{
			if( !$this->isAuthenticated() )
				return $this->error(2);
			
			return $this->account_info->profile_link_color;
		}
		
		
		/**
		 * Private methods used to determine and process datas
		 */
		
		private function parseStatusArray($status)
		{
			$array = array();
			
			$status_array = array(
									"id" => $status->id,
									"retweet_count" => $status->retweet_count,
									"favorited" => $status->favorited,
									"created_at" => $status->created_at,
									"source" => $status->source,
									"text" => $status->text,
									"in_reply_to_screen_name" => $status->in_reply_to_screen_name,
									"in_reply_to_status_id" => $status->in_reply_to_status_id
								 );
								 
			return $status_array;
		}
		
		private function parseUserArray($friend)
		{
			$status_arr	= $this->parseStatusArray( $friend->status );
				
			$friend_arr		= array	(
										"name" => $friend->name,
										"profile_image_url" => $friend->profile_image_url,
										"screen_name" => $friend->screen_name,
										"place" => $friend->place,
										"verified" => $friend->verified,
										"location" => $friend->location,
										"url" => $friend->url,
										"created_at" => $friend->created_at,
										"statuses_count" => $friend->statuses_count,
										"friends_count" => $friend->friends_count,
										"listed_count" => $friend->listed_count,
										"followers_count" => $friend->followers_count,
										"status" => $status_arr
									);
			return $friend_arr;
		}
		
		public function getCurrentURL()
		{
			$page_url = 'http';
			
			if($_SERVER["HTTPS"] == "on")
			{
				$page_url .= "s";
			}
			
			$page_url .= "://";
			
			if($_SERVER["SERVER_PORT"] != "80")
			{
				$page_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			} 
			else
			{
				$page_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			}
			
			return $page_url;
		}
		
		private function oauthCleanURL($url)
		{
			$url = preg_replace("/\?(.*)$/", "?", $url);
			
			$clean_keys = array('oauth_token', 'oauth_verifier');
			
			foreach($_GET as $key => $val)
			{
				if( !in_array($key, $clean_keys) )
				{
					$url .= $key . ($val ? ("=" . $val . "&") : '');
				}
			}
			
			if( substr($url, -1, 1) == "&"  )
				$url = substr($url, 0, -1);
			
			return $url;
		}		
	}
	
?>