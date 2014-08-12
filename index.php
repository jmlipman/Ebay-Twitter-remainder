<?


require_once 'twitterapi/TwitterOAuth-master/TwitterOAuth/TwitterOAuth.php';
require_once 'twitterapi/TwitterOAuth-master/TwitterOAuth/Exception/TwitterException.php';

//$url = "http://www.ebay.com/itm/MENS-T-SHIRTS-/171167074511?pt=US_Mens_Tshirts&hash=item27da5a48cf";
//$object = new Cronebay($url);
$config = array(
    'consumer_key' => 'PAgpS5aX1yduUkvPwwsA',
    'consumer_secret' => 'E7YvwTyIBnhQsWctyNN6pXeGqGj19fKtqRPcgpk',
    'oauth_token' => '562341307-1B5aVWBSGn39DLi9bCqNpyBES8gerlGQY47gC8oN',
    'oauth_token_secret' => 'ANXsKFqa6GwlJaFpceuskFRCZ7OP8NkZmuB7tKBAI',
    'output_format' => 'object'
);


$tw = new TwitterOAuth($config);

$params = array("status" => "asdasd22");


//$response = $tw->post('statuses/update', $params);



?>
