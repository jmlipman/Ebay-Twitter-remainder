<?
require_once 'cronebay.php';
require_once 'twitterapi/TwitterOAuth-master/TwitterOAuth/TwitterOAuth.php';
require_once 'twitterapi/TwitterOAuth-master/TwitterOAuth/Exception/TwitterException.php';

$url = $_GET['url'];

if(!$_GET['url'])
	die("Error, no param.");

$objeto = new Cronebay($url);


//TWEET
$config = array(
    'consumer_key' => 'PAgpS5aX1yduUkvPwwsA',
    'consumer_secret' => 'E7YvwTyIBnhQsWctyNN6pXeGqGj19fKtqRPcgpk',
    'oauth_token' => '562341307-1B5aVWBSGn39DLi9bCqNpyBES8gerlGQY47gC8oN',
    'oauth_token_secret' => 'ANXsKFqa6GwlJaFpceuskFRCZ7OP8NkZmuB7tKBAI',
    'output_format' => 'object'
);

$tw = new TwitterOAuth($config);
$params = array("status" => $objeto->generateTweet());
$response = $tw->post('statuses/update', $params);
//TWEET
var_dump($response);

?>