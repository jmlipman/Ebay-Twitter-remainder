<?php

	/**
	  * Configuration File
	  *
	  * Add your details from your Twitter application 
	  * in order to work correctly with Twitter class
	  * For more help about getting information about
	  * your twitter application or creating new one
	  * please look on help/ folder.
	  *
	  * The apps page on twitter can be accessed from this
	  * URL: http://dev.twitter.com/apps
	  */
	
	// For purpose of this script because it is sold on 
	// marketplace I've created a sample application to 
	// use in proper with this script! So after you create
	// your own application please replace values below
	
	# After you login to your application page click
	# an application you want to use information of it
	# then copy the token values named as below:
	# * Consumer key
	# * Consumer secret
	# and then paste those values to variables below (to the correspoding order)
	
	define("CONSUMER_KEY", "PAgpS5aX1yduUkvPwwsA");
	define("CONSUMER_SECRET", "E7YvwTyIBnhQsWctyNN6pXeGqGj19fKtqRPcgpk");
	
	
	# On the same page you tooked consumer key and consumer secret you have
	# a link on the right module called `My Access Token`, click on it and take information as
	# described below
	
	# To automatically login to your account and
	# use the application please provide these
	# fields with access tokens which can be founded on
	# http://dev.twitter.com/apps/# YOUR APPLICATION ID #/my_token
	# where # YOUR APPLICATION ID # is the ID of application you are tending to use
	 
	define("OAUTH_TOKEN", "562341307-1B5aVWBSGn39DLi9bCqNpyBES8gerlGQY47gC8oN");
	define("OAUTH_TOKEN_SECRET", "ANXsKFqa6GwlJaFpceuskFRCZ7OP8NkZmuB7tKBAI");
?>