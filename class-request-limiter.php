<?php


/**
 * Class RequestLimiter
 *
 */
class RequestLimiter{

	private $type;
	private $scriptId;
	private $minRequestInterval;
	private $maxRequestsPerDay;
	private $requestStatsFile;


    /**
     * RequestLimiter constructor.
     *
     * @param string $type                  "ip" for using IP-number filtering, "session" for session filtering.
     * @param string $scriptId              The ID of the calling script, used to identify each script in the stored request data.
     * @param int $minRequestInterval       Minimum time between requests, in seconds.
     * @param int $maxRequestsPerDay        Maximum number of requests per day.
     * @param string $requestStatsFile      A file to store the request data in when using IP-number filtering.
     */
	public function __construct(string $type, string $scriptId, int $minRequestInterval, int $maxRequestsPerDay, string $requestStatsFile = ''){
		$this->type = $type;
		$this->scriptId = $scriptId;
		$this->minRequestInterval = $minRequestInterval;
		$this->maxRequestsPerDay = $maxRequestsPerDay;
		$this->requestStatsFile = $requestStatsFile;
	}


    /**
     * Call this method from the script you want to limit requests to. It will update the stored request data and display a simple error
     * message if the user has exceeded the request limits set in the call to the constructor.
     */
	public function performRequest(){
		if( $this->type == 'ip' ){
			$ip = $_SERVER['REMOTE_ADDR'];
			$requestStats = file_exists($this->requestStatsFile) ? json_decode(file_get_contents($this->requestStatsFile), true) : [];
			if( !isset($requestStats) || !is_array($requestStats) ) $requestStats = [];

			$this->pruneObsoleteRequestStats($requestStats);

			if( !isset($requestStats[$ip]) || !is_array($requestStats[$ip]) ) $requestStats[$ip] = [ 'last_request_timestamp' => 0, 'requests_today_count' => 0];
			$lastRequestTimestamp = &$requestStats[$ip]['last_request_timestamp'];
			$requestsTodayCount = &$requestStats[$ip]['requests_today_count'];	
		}
		else{
			session_start();
			$requestStats = isset($_SESSION['request-limit'][$this->scriptId]) ? $_SESSION['request-limit'][$this->scriptId] : [ 'last_request_timestamp' => 0, 'requests_today_count' => 0];
			$lastRequestTimestamp = &$requestStats['last_request_timestamp'];
			$requestsTodayCount = &$requestStats['requests_today_count'];	
		}


		// Was the previous request within the same day, and is the max requests/day reached?
		if( date('z', $lastRequestTimestamp) == date('z', time()) && $requestsTodayCount >= $this->maxRequestsPerDay ){ 
			die("<html><body><h1>Request rate limit</h1><p>You've reached the limit of maximum {$this->maxRequestsPerDay} requests per day. Please wait until after midnight with your next request.</p></body></html>");
		} 
		// Did enough time pass since the last request?
		elseif( $lastRequestTimestamp > time()-$this->minRequestInterval ){
			die("<html><body><h1>Request rate limit</h1><p>The minimum request interval is {$this->minRequestInterval} seconds. You may make another request in ".($this->minRequestInterval-(time()-$lastRequestTimestamp))." seconds.</p></body></html>");
		} 
		// Was the previous request NOT within the same day? Then reset the max requests/day counter.
		elseif( date('z', $lastRequestTimestamp) != date('z', time()) ){
			$requestsTodayCount = 0;
		}		

		// Update request limit variables
		$lastRequestTimestamp = time();
		$requestsTodayCount = $requestsTodayCount+1;

		if( $this->type == 'ip' ){
			file_put_contents($this->requestStatsFile, json_encode($requestStats));
		}
		else{
			$_SESSION['request-limit'][$this->scriptId] = $requestStats;
		}
	}


    /**
     * Remove request data for all IP numbers whose last request was more than 24 hours ago.
     */
	private function pruneObsoleteRequestStats(array &$requestStats){
		foreach( $requestStats as $ip => $ipStats ){
			if( time()-$ipStats['last_request_timestamp'] > 86400 )
				unset($requestStats[$ip]);
		}
	}


}

