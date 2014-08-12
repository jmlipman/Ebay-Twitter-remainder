<?

class Cronebay {
	
	private $itemURL;
	private $tmp = "tmp";
	private $crontime;
	private $year;
	private $month;
	private $day;
	private $hour;
	private $min;
	private $sec; //probablemente no lo use
	private $unixtimeitem;
	private $cronjobfile;
	private $bid;
	private $name;
	

	function __construct($url){
		
		preg_match_all("/\/([0-9]*)/",$url,$match);
		$url = "www.ebay.com/itm/".$match[1][count($match[1])-1];
		$this->itemURL = $url;
		
	}
	
	/**
	 * This function will append a new cronjob to the rest.
	 * 
	 * @param $job to be appened.
	**/
	function append($job){ //void
		$output = $this->listjobs();
		file_put_contents($this->tmp, $output.$job.PHP_EOL);
		echo exec('crontab '.$this->tmp);
	}
	
	/**
	 * This function will return the current cronjob list.
	 * 
	 * @return cronjob list.
	**/
	function listjobs(){
		return shell_exec('crontab -l');
	}
	
	function clearCronJobs(){
		shell_exec('crontab -r');
	}
	
	/**
	 * This function is the same as the one above, parsed.
	 * 
	 * @return cronjob list.
	**/
	function listjobsparsed(){
		$output = shell_exec('crontab -l');
		$output = substr($output, 1, strlen($output));
		$output = str_replace('
', "<br>",$output);
		return $output;
	}
	
	
	/**
	 * This function will obtain the time left to an article as well as will set this time to the corresponding variables.
	 * 
	 * @param $url_item Ebay URL of the item.
	**/
	function obtainFinishTime(){
	
		$months["Jan"] = 0;
		$months["Feb"] = 1;
		$months["Mar"] = 2;
		$months["Apr"] = 3;
		$months["May"] = 4;
		$months["Jun"] = 5;
		$months["Jul"] = 6;
		$months["Aug"] = 7;
		$months["Sep"] = 8;
		$months["Oct"] = 9;
		$months["Nov"] = 10;
		$months["Dec"] = 11;
		//echo "URL ".$this->itemURL;
		$ch = curl_init($this->itemURL);
		ob_start();
		curl_exec($ch);
		curl_close($ch);
		$retrievedhtml = ob_get_contents();
		ob_end_clean();
		
		//$pattern = "/<span class=\"vi-is1-tml\">(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>/";
		//$pattern = "/<span class=\"vi-is1-tml\">(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>/";
		$pattern = "/vi-is1-tml(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>/";
		//$pattern = "/vi-is1-tml(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)/";
		
		preg_match_all($pattern, $retrievedhtml, $match);
		//print_r($match);
		$pattern = "/<(.*?)>/";
		$result = preg_replace($pattern, "", $match[0][0]);
		//print_r($match);
		//echo "Resultado: " . $result;
		
		//Comprobamos si hay dias
		//Sumamos +2 horas por problemas.
		//Asumo que el problema está en las horas que recojo PERO PUEDE SER que sea en la hora del sistema
		//Entonces, habria que modificar en 2 las horas del sistema.
		if(preg_match("/day/",$result) || preg_match("/día/",$result)){ //Queda un dia
			$tmp = explode(" ", $result);
			
			//print_r($tmp);
			$this->month = $months[substr($tmp[4],1,strlen($tmp[4]))];
			$this->day = (int)substr($tmp[5],0,strlen($tmp[5])-1);
			$this->year = (int)substr($tmp[6],0,4);
			$this->hour = (int)substr($tmp[6],4,6)+2;
			$this->min = (int)substr($tmp[6],7,9);
			$this->sec = (int)substr($tmp[6],10,12);
			
		} elseif(preg_match("/d/",$result)){
			$tmp = explode(" ", $result);
			
			//print_r($tmp);
			$this->month = $months[substr($tmp[2],1,strlen($tmp[2]))];
			$this->day = (int)substr($tmp[3],0,strlen($tmp[3])-1);
			$this->year = (int)substr($tmp[4],0,4);
			$this->hour = (int)substr($tmp[4],4,6)+2;
			$this->min = (int)substr($tmp[4],7,9);
			$this->sec = (int)substr($tmp[4],10,12);
			
		} else { //Quedan horas
			$tmp = explode(" ", $result);
			
			
			$this->month = $months[substr($tmp[3],1,strlen($tmp[3]))];
			$this->day = (int)substr($tmp[4],0,strlen($tmp[4])-1);
			$this->year = (int)substr($tmp[5],0,4);
			$this->hour = (int)substr($tmp[5],4,6)+2;
			$this->min = (int)substr($tmp[5],7,9);
			$this->sec = (int)substr($tmp[5],10,12);
		}
		
		
		$this->unixtimeitem = mktime($this->hour,$this->min,$this->sec,$this->month+1,$this->day,$this->year);
		//echo $this->getFinishTime();
	}
	
	/**
	 * This function sets the crontime and stores it into the corresponding variable.
	 * 
	**/
	function setCronTime(){
	//$crontime
	
		//conjunto de ifs, para setear a dónde ir en los case's.
		//					INTERVALO
		//case 1: -> 	>12h.			12h
		//case 2: -> 	<=12h & >=6h		1h
		//case 3: ->	<=6h & >=2h		30m
		//case 4: ->	<=2h			10m
		$tmptime = $this->unixtimeitem;
	
	
		if(($tmptime-time())>43200) //Más de 12 horas
			$type = 1;
		elseif(($tmptime-time())<=43200 && ($tmptime-time())>21600) //Entre 12 y 6 horas.
			$type = 2;
		elseif(($tmptime-time())<=21600 && ($tmptime-time())>7200) //Entre 6 y 2 horas.
			$type = 3;
		elseif(($tmptime-time())<=7200 && ($tmptime-time())>0) //Menos de 2 horas.
			$type = 4;	
	
		
		//echo "tipo: ".$type;
		
		$hora_actual = time();
		$limite = time();
		
		switch($type){
			case 1: //Más de 12 horas
				$tmptime = $this->unixtimeitem;
				//Aqui ponemos que cada 12 horas avise.
				$tmptime-=43200;
				while($tmptime>$hora_actual){
					//echo "Entro porque tmptime es $tmptime y time es ".time()."<br>";
					//$this->printTime($tmptime);
					$this->setNewLineCronJob($tmptime);
					$tmptime-=43200;
				}
				$limite = $this->unixtimeitem-(3600*12);
				//echo "<hr>";
				
			case 2: //Entre 12 y 6 horas. Poner una a cada hora.
				$tmptime = $this->unixtimeitem;
				//Aqui ponemos que cada 12 horas avise.
				$tmptime-=(3600*6);
				//El límite NO es hora_actual.
				//echo "<b>limite</b>:";
				//$this->printTime($tmptime); 
				while($tmptime>$limite){
					//echo "Entro porque tmptime es $tmptime y time es ".time()."<br>";
					//$this->printTime($tmptime);
					$this->setNewLineCronJob($tmptime);
					$tmptime-=3600;
				}
				$limite = $this->unixtimeitem-(3600*6);
				//echo "<hr>";
				
			case 3: //Entre 6 y 2 horas. Poner una a cada media hora.
				$tmptime = $this->unixtimeitem;
				//Aqui ponemos que cada 12 horas avise.
				$tmptime-=(3600*2);
				//El límite NO es hora_actual.
				//echo "<b>limite</b>:";
				//$this->printTime($tmptime); 
				while($tmptime>$limite){
					//echo "Entro porque tmptime es $tmptime y time es ".time()."<br>";
					//$this->printTime($tmptime);
					$this->setNewLineCronJob($tmptime);
					$tmptime-=1800;
				}
				$limite = $this->unixtimeitem-(3600*2);
				//echo "<hr>";
				
			case 4: //Menos de dos horas. Poner una a cada 10 minutos.
				$tmptime = $this->unixtimeitem;
				//Aqui ponemos que cada 12 horas avise.
				$tmptime-=600;
				//El límite NO es hora_actual.
				//echo "<b>limite</b>:";
				//$this->printTime($tmptime); 
				while($tmptime>$limite){
					//echo "Entro porque tmptime es $tmptime y time es ".time()."<br>";
					//$this->printTime($tmptime);
					$this->setNewLineCronJob($tmptime);
					$tmptime-=600;
				}
		}
	
		//$this->getCronJob();
	
	}
	
	function obtainBid(){
	//<span id="v4-33"
		if((($this->unixtimeitem)-time())<900) //Si quedan menos de 15 min, se borran los cronjobs.
			$this->deleteOwnCronJobs();
	
		$ch = curl_init($this->itemURL);
		ob_start();
		curl_exec($ch);
		curl_close($ch);
		$retrievedhtml = ob_get_contents();
		ob_end_clean();
		
		//$pattern = "/<span class=\"vi-is1-tml\">(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>(.*?)<\/span>/";
		//$pattern = "/itemprop=\"price(.*?)<\/span>/";
		$pattern = "/class=\"vi-is1-prcp-eu\"(.*?)<\/span>/";
		
		preg_match_all($pattern, $retrievedhtml, $match);
		//print_r($match);
		$pattern = "/<\/font> (.*?)<\/span>/";
		preg_match_all($pattern, $match[0][0], $match);
		
		$this->bid=$match[1][0] . "€";
	}

	/**
	 * This function will return a String with the time the article finish.
	**/	
	function getFinishTime(){
		return "Year: ".$this->year. " Mes: ".$this->month. " Dia: ".$this->day . " Hora: ".$this->hour." Minutos: ".$this->min." Segundos: ".$this->sec;
	}
	
	function printTime($time){
		echo "Con $time <br>";
		echo date('\Y\e\a\r: Y. \M\o\n\t\h: m. \D\a\y: d. \H\o\u\r: H. \M\i\n\u\t\e\s: i. \S\e\c\o\n\d\s: s',$time) . "<br>";
	}
	
	function setNewLineCronJob($time){
		$command = "wget -nv -O /dev/null http://74.54.191.131/~lipman/ebaycron/bot.php?url=".$this->itemURL." >/dev/null 2>&1";
		//$year = date("Y",$time);
		$month = date("n",$time);
		$day = date("j",$time);
		$hour = date("G",$time);
		$min = date("i",$time);
		
		$linea = $min . " " . $hour . " " . $day . " " . $month . " * " . $command;
		
		$this->append($linea);
	}

	function newLineCronJob($line){
		$this->cronjobfile.="
$line";
	
	}
	
	function getCronJob(){ 
		return substr($this->cronjobfile, 1, strlen($this->cronjobfile));
	}
	
	function obtainName(){
				
		$ch = curl_init($this->itemURL);
		ob_start();
		curl_exec($ch);
		curl_close($ch);
		$retrievedhtml = ob_get_contents();
		ob_end_clean();
		
		$pattern = "/<title>(.*?)eBay<\/title>/";
		preg_match_all($pattern, $retrievedhtml, $match);
		
		$name = substr($match[1][0],0,strlen($match[1][0])-2);
		
		$this->name=$name;
		
	}
	
	function getTimeLeft(){
		$var = (($this->unixtimeitem)-time()); //Quitamos una hora para que de bien.
		
		$hours = explode(".",$var/3600);
		$mins = explode(".",($var%3600)/60);
		$secs = (($var%3600)%60);
		$dias = $hours[0]/24;
		$dias = substr($dias, 0, 3);
		
		$this->obtainBid();
		$this->obtainName();
		
		$string = ". Faltan $hours[0] horas (".$dias." días), $mins[0] minutos y $secs segundos. Precio: ".$this->bid.". Item: ";
		
		$tamano = strlen($string)+26; //URL
		$restante = 140-$tamano;
		
		$name = substr($this->name,0,$restante-1);
		
		$string = $name . $string . $this->itemURL;
		
		
		return $string;

	}
	
	function getBid(){
			
		return $this->bid;
	}
	
	function deleteOwnCronJobs(){

		$cronjobs = $this->listjobs();
		$id_ebay = $this->itemURL;
		
		preg_match_all("/([0-9]*)$/",$id_ebay,$match);
		$id_ebay = $match[0][0];
		
		preg_match_all("/(.*)www\.ebay\.com\/itm\/(?!".$id_ebay.")(.*)/", $cronjobs, $match);
		foreach($match[0] as $valor)
			$final_list .= "
".$valor;

		$final_list = substr($final_list, 1, strlen($final_list));
		//limpio
		$this->clearCronJobs();
		//añado
		file_put_contents($this->tmp, $final_list.PHP_EOL);
		echo exec('crontab '.$this->tmp);
	}
	

}

?>