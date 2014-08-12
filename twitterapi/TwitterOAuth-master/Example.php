<?php

require_once 'TwitterOAuth/TwitterOAuth.php';
require_once 'TwitterOAuth/Exception/TwitterException.php';



date_default_timezone_set('UTC');


/**
 * Array with the OAuth tokens provided by Twitter when you create application
 *
 * output_format - Optional - Values: text|json|array|object - Default: object
 */
$config = array(
    'consumer_key' => 'PAgpS5aX1yduUkvPwwsA',
    'consumer_secret' => 'E7YvwTyIBnhQsWctyNN6pXeGqGj19fKtqRPcgpk',
    'oauth_token' => '562341307-1B5aVWBSGn39DLi9bCqNpyBES8gerlGQY47gC8oN',
    'oauth_token_secret' => 'ANXsKFqa6GwlJaFpceuskFRCZ7OP8NkZmuB7tKBAI',
    'output_format' => 'object'
);

/**
 * Instantiate TwitterOAuth class with set tokens
 */
$tw = new TwitterOAuth($config);


/**
 * Returns a collection of the most recent Tweets posted by the user
 * https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
 */
/*$params = array(
    'screen_name' => 'ricard0per',
    'count' => 5,
    'exclude_replies' => true
);*/

$params = array("status" => "asdasd");

/**
 * Send a GET call with set parameters
 */
//$response = $tw->get('statuses/user_timeline', $params);
$response = $tw->post('statuses/update', $params);

var_dump($response);


/**
 * Creates a new list for the authenticated user
 * https://dev.twitter.com/docs/api/1.1/post/lists/create
 */
/*$params = array(
    'name' => 'TwOAuth',
    'mode' => 'private',
    'description' => 'Test List',
);
*/
/**
 * Send a POST call with set parameters
 */
/*$response = $tw->post('lists/create', $params);

var_dump($response);*/
