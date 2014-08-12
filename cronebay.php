<?

class Cronebay {
	
	private $itemURL;
	private $bid;
	private $name;
	private $hoursDelay = 2; //Depending on your GMT+X
	private $unixtime;
	private $id;
	private $screenname = "@jmlipman";
	
	private $tmp = "tmp";
	private $crontime;
	private $cronjobfile;
	

	/**
	 * This constructor will save item webpage.
	 */
	function __construct($url){
		
		preg_match_all("/\/([0-9]*)/",$url,$match);
		$this->id = $match[1][count($match[1])-1];
		$url = "www.ebay.com/itm/".$this->id;
		$this->itemURL = $url;
		
	}
	
	/**
	 * This function generate and returns the tweet we want to send.
	 *
	 * @return tweet we want to send.
	 */
	function generateTweet(){
		//Let's calculate time left
		$var = (($this->getUnixTime())-time());
		
		if($var<15*60)
		{
			$this->deleteCronjobs();
		}
		
		$hours = explode(".",$var/3600);
		$mins = explode(".",($var%3600)/60);
		$secs = (($var%3600)%60);
		$days = $hours[0]/24;
		$days = substr($days, 0, 3);
		
		$string = ". $hours[0] hours (".$days." days), $mins[0] min y $secs sec left. Price: ".$this->getBid().". Item: ";
		
		$size = strlen($string)+24; //URL
		$charsleft = 140-$size-strlen($this->getScreenName())-1; //Screen Name
		
		$name = substr($this->name,0,$charsleft-1);
		$string = $this->getScreenName(). " " . $name. $string . $this->getUrl();
		echo $string;
		return $string;
	}
	
	/**
	 * This function will establish and set all needed cronjobs for desired item.
	 */
	function setCronJobs() {
		//Open a file where all the cronjobs will be stored.
		//Execute that file in order to establish all the cronjobs
		$output = $this->generateCronJobs();
		//file_put_contents($this->tmp, $output.PHP_EOL);
		$f=fopen($this->getTempFile(),"w");
		fwrite($f,$output);
		fclose($f);
		exec('crontab '.$this->getTempFile());
		
	}
	
	/**
	 * This function will delete all cronjobs
	 * 
	**/
	static function clearAllCronJobs(){
		shell_exec('crontab -r');
	}
	
	/**
	 * This function will return the current cronjob list.
	 * 
	 * @return cronjob list.
	**/
	static function getAllCronJobs(){
		return shell_exec('crontab -l');
	}
	
	/**
	 * This function will delete all cronjobs related to current item.
	 * It will basically obtain all current jobs, parse them and get all except the 
	 * ones related to the item we want to delete.
	 *
	 */
	function deleteCronjobs() {
		$output="";
		$cronjobs = Cronebay::getAllCronJobs();
		$test = explode("\n",$cronjobs);
		foreach($test as $value){
			if(!preg_match("/".$this->id."/",$value)){
				$output .= $value . "\n";
			}
		}
		
		Cronebay::clearAllCronJobs();
		$f=fopen($this->getTempFile(),"w");
		fwrite($f,$output);
		fclose($f);
		exec('crontab '.$this->getTempFile());
	}
	
	/**
	 * This function generate (string) all cronjobs.
	 *						Interval
	 *	case 1: -> 	>12h.			12h
	 *	case 2: -> 	<=12h & >=6h		1h
	 *	case 3: ->	<=6h & >=2h		30m
	 *	case 4: ->	<=2h			10m
	 * 
	 * @return all cronjobs.
	**/
	private function generateCronJobs(){
		
		$jobs = "";
		
		$tmptime = $this->getUnixTime()-$this->hoursDelay*3600;
		//echo $tmptime . "," . time() . "<hr>";
		$timestart=0;
	
		//Classify the item depending on the time left.
		//Normalized time start.
		if(($tmptime-time())>43200) // >12h
		{
			$type = 1;
			while($tmptime-$timestart-time()>0) $timestart+=43200;
			$timestart-=43200;
		}
		elseif(($tmptime-time())<=43200 && ($tmptime-time())>21600) // <=12h & >=6h
		{
			$type = 2;
			while($tmptime-$timestart-time()>0) $timestart+=21600;
			$timestart-=21600;
		}
		elseif(($tmptime-time())<=21600 && ($tmptime-time())>7200) // <=6h & >=2h
		{
			$type = 3;
			while($tmptime-$timestart-time()>0) $timestart+=7200;
			$timestart-=7200;
		}
		elseif(($tmptime-time())<=7200 && ($tmptime-time())>0) // <=2h
		{
			$type = 4;	
			while($tmptime-$timestart-time()>0) $timestart+=1800;
			$timestart-=1800;
		}
		
		switch($type){
			case 1: // >12h
				while($timestart-43200-12*3600>=0){
					$jobs .= $this->generateCronJobLine($timestart)."\n";
					$timestart-=43200;
				}
				
			case 2: // <=12h & >=6h. 1 cronjob every 6 hours
				while($timestart-6*3600-6*3600>=0){
					$jobs .= $this->generateCronJobLine($timestart)."\n";
					$timestart-=6*3600;
				}
				
			case 3: // <=6h & >=2h. 1 cronjob every 60min
				while($timestart-3600-2*3600>=0){
					$jobs .= $this->generateCronJobLine($timestart)."\n";
					$timestart-=3600;
				}
				
			case 4: // <=2h. 1 cronjob every 30min
				while($timestart-1800-1800>=0){
					$jobs .= $this->generateCronJobLine($timestart)."\n";
					$timestart-=1800;
				}
			default:
				while($timestart-600>=0){
					$jobs .= $this->generateCronJobLine($timestart)."\n";
					$timestart-=600;
				}
		}
	
		return $jobs;
	
	}
	
	/**
	 * This function generates a cronjob line and returns the content. 
	 * It only needs the date when it will be triggered since it uses a pre-establish command.
	 *
	 * @param time when the cronjob will be triggered.
	 * @return a generated cronjob line.
	 */
	private function generateCronJobLine($time){
		$time=$this->getUnixTime()-$time;
		$command = "wget -nv -O /dev/null http://192.185.41.206/~lipman/projects/ebaycron/bot.php?url=".$this->itemURL." >/dev/null 2>&1";
		$month = date("n",$time);
		$day = date("j",$time);
		$hour = date("G",$time);
		$min = date("i",$time);
		
		$line = $min . " " . $hour . " " . $day . " " . $month . " * " . $command;
		
		return $line;
	}
		
	/**
	 * This function obtains the last bid. It is called if the content of $this->bid is empty.
	 * Basically, this function parses the URL in order to obtain the last bid and assigns this
	 * value to the variable $this->bid.
	 */
	private function obtainBid(){
		//Store the content of the web
		$retrievedhtml = $this->getHTML();

		//Pattern to parse the price
		$pattern =  '/itemprop="price">(.*?)<\/span>/';
		
		preg_match_all($pattern, $retrievedhtml, $match);
		
		$this->bid=$match[1][0];
	}
	
	/**
	 * This function obtains item's name. It is called if the content of $this->name is empty.
	 * Basically, this function parses the URL in order to obtain item's name and assigns this
	 * value to the variable $this->name.
	 */
	private function obtainName(){
		//Store the content of the web
		$retrievedhtml = $this->getHTML();
		
		$pattern = "/<title>(.*?)eBay<\/title>/";
		preg_match_all($pattern, $retrievedhtml, $match);
		
		$name = substr($match[1][0],0,strlen($match[1][0])-2);
		
		$this->name=$name;
		
	}
	
	/**
	 * This function returns the content of item's webpage (HTML).
	 *
	 * @return item's webpage.
	*/
	private function getHTML() {
		$ch = curl_init($this->itemURL);
		ob_start();
		curl_exec($ch);
		curl_close($ch);
		$retrievedhtml = ob_get_contents();
		ob_end_clean();
		
		return $retrievedhtml;
	}
	
	
	/**
	 * This function will obtain the time left to an article as well as will set this time to the corresponding variables.
	 * 
	 * @param $url_item Ebay URL of the item.
	**/
	private function obtainUnixTime(){
	
		$months["Jan"] = 1;
		$months["Feb"] = 2;
		$months["Mar"] = 3;
		$months["Apr"] = 4;
		$months["May"] = 5;
		$months["Jun"] = 6;
		$months["Jul"] = 7;
		$months["Aug"] = 8;
		$months["Sep"] = 9;
		$months["Oct"] = 10;
		$months["Nov"] = 11;
		$months["Dec"] = 12;

		//Store the content of the web
		$retrievedhtml = $this->getHTML();
		
		//Let's take the month, day, and year
		$pattern = '/<span>\(([A-Z][a-z][a-z]) ([0-9][0-9]), ([0-9][0-9][0-9][0-9])<\/span>/';
		preg_match_all($pattern, $retrievedhtml, $match);
		$_month = $months[$match[1][0]];
		$_day = $match[2][0];
		$_year = $match[3][0];
		
		//Let's take the hours, min and sec.
		$pattern = '/"endedDate">([0-9][0-9]):([0-9][0-9]):([0-9][0-9])(.*?)<\/span>/';
		preg_match_all($pattern, $retrievedhtml, $match);
		$_hours = $match[1][0];
		$_min = $match[2][0];
		$_sec = $match[3][0];
		
		$this->unixtime = mktime($_hours+$this->hoursDelay,$_min,$_sec,$_month,$_day,$_year);
		
	}
	
	/**
	 * This function returns the last bid. In case this bid is not stored in the variable, the
	 * function itself call a private function to obtain this information and after that is returned.
	 * 
	 * @return last bid.
	 */
	function getName(){
		if($this->name=="") {
			$this->obtainName();
		}
		return $this->name;
	}
	
	/**
	 * This function returns the last bid. In case this bid is not stored in the variable, the
	 * function itself call a private function to obtain this information and after that is returned.
	 * 
	 * @return last bid.
	 */
	function getBid(){
		if($this->bid=="") {
			$this->obtainBid();
		}
		return $this->bid;
	}
	
	/**
	 * This function returns the last bid. In case this bid is not stored in the variable, the
	 * function itself call a private function to obtain this information and after that is returned.
	 * 
	 * @return last bid.
	 */
	function getUnixTime(){
		if($this->unixtime=="") {
			$this->obtainUnixTime();
		}
		return $this->unixtime;
	}
	
	/**
	 * This function returns the URL of the product.
	 *
	 * @return URL of the product.
	 */
	function getUrl() { return $this->itemURL; }
	
	/**
	 * This function returns the name of the temporal file used to make the cronjobs.
	 *
	 * @return name of temporal file.
	 */
	function getTempFile(){ return $this->tmp; }
	
	/**
	 * This function returns the screen name whom tweets will be sent
	 *
	 * @return screen name.
	 */
	function getScreenName(){ return $this->screenname; }
}

?>