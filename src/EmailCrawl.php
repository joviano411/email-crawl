<?php
/**
 * @author      Peter Chigozie(NG) peterujah
 * @copyright   Copyright (c), 2022 Peter(NG) peterujah
 * @license     MIT public license
 */
namespace Peterujah\NanoBlock;
use \Peterujah\NanoBlock\CrawlResponse;

class EmailCrawl{
	/** 
	* Holds CURL host verification state
	* @var string|url 
	*/
	public const VERIFY_HOST = 0;

	/** 
	* Holds CURL ssl peer verification state
	* @var bool
	*/
	public const SSL_VERIFY_PEER = false;

	/** 
	* Holds target website link
	* @var string|url 
	*/
	private $crawTarget;

	/** 
	* Holds the recursion level
	* @var int 
	*/
	private $crawLevel = 0;

	/** 
	* Holds maximum recursion scan
	* @var int 
	*/
	private $crawMax = 50;

	/** 
	* Holds the recursion emails
	* @var array 
	*/
	private $crawMails = array();

	/** 
	* Holds the recursion scanned links
	* @var array 
	*/
	private $crawDeepTargets = array();

	/** 
	* Holds the target webpage content
	* @var string|html 
	*/
	private $crawContents;

	/** 
	* Holds the http request options
	* @var int 
	*/
	private $options = array(
		'http' => array(
			'method'=>"GET",
			'header'=>"Content-Type: text/html; charset=utf-8"
		)
	);
	
	/**
	* Constructor
	* @param string $url the crawl target website
	* @param int $max the maximum recursion
	* @param array $mails extracted emails
	*/
	public function __construct($url, $max = 50, $mails = array()) {
		$this->crawTarget = $url;
		$this->crawMax = $max;
		$this->crawMails = $mails;
	}
 
	/**
	* Get's Request 
	* @param string $url the crawl target website
	* @param array $field optional request parameters
	* @return string|html website content
	*/
	private function curl_get_contents($url, $field = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($this->options["http"]["header"]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, self::VERIFY_HOST);   
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, self::SSL_VERIFY_PEER);
		if(!empty($field)){
			curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($field));
		}
		$result = curl_exec($ch);               
		if ($result === false) {
			trigger_error('ErrorRequestHandler: ' . curl_error($ch));
		}
		curl_close($ch);
		return $result;
	}
	
	/**
	* Gets the content of the target webpage
	* @return string|html
	*/
	public function getContent() {
		if (!function_exists('curl_init')){
			$this->crawContents = file_get_contents($this->crawTarget, false, $this->getContext());
		} else {
			$this->crawContents = $this->curl_get_contents($this->crawTarget);
		}
		return $this;
	}
  
	/**
	* Gets the context to open the FGC socket
	* @return stream_context_resource
	*/
	private function getContext() {
		return stream_context_create($this->options);
	}
	
	/**
	* Extracts emails from target webpage to array
	* Check for duplicate entries
	* @return array
	*/
	public function extractEmailArray() {
		$pattern1 = "(([-_.\w]+@[a-zA-Z0-9_]+?\.[a-zA-Z0-9]{2,6}))";
		$pattern2 = "(\w[-._\w]*\w@\w[-._\w]*\w\.\w{2,3})";
		preg_match_all($pattern1, $this->crawContents, $match1, PREG_PATTERN_ORDER);
		preg_match_all($pattern2, $this->crawContents, $match2, PREG_PATTERN_ORDER);
		$emails = $this->array_unique_deep(array_merge($match1, $match2));
		return $emails;
	}
 
	/**
	* Removes duplicate values on multi dimensional arrays
	* @return array
	*/
	private function array_unique_deep($array) {
		$values = array();
		foreach ($array as $part) {
			if (is_array($part)) {
				$values= array_merge($values, $this->array_unique_deep($part));
			} else { 
				$values[]= $part;
			}
		}
		return array_unique($values);
	}
    
	/**
	* Fetch URLs from the target website to scan extract emails also
	* Check for duplicate entries
	* @return array
	*/
	public function deepURLArray() {
		$pattern = '((\:href=\"|(http(s?))\://){1}\S+)';
		preg_match_all($pattern, $this->crawContents, $match, PREG_PATTERN_ORDER);
		array_walk($match[0], function(&$item) { 
			if (strpos($item, '"') !== false) {
				$item = substr($item, 0, strpos($item, '"')); 
			}
		});
		$links = $this->array_unique_deep($match[0]);
		$links = array_unique($this->setURLPrefix($links));
		return $links;
	}
 
	/**
	* Sets URL prefixes www/http
	* @param URL-Array $array
	* @return array
	*/
	private function setURLPrefix($array) {
		$prefix = array(); 
		$i = 0;
		foreach ($array as $part) {
			if(preg_match('/^(www\.)/', $part)) $prefix[$i]='http://'.$part;
			else $prefix[$i]=$part;
			$i++;
		}
		return $prefix;
	}

	/**
	* Gets response from scanned target
	* @return CrawlResponse|Object
	*/
	public function getResponse() {
		return new CrawlResponse($this->crawMails, 1+count($this->crawDeepTargets));
	}

	/**
	* Gets response emails from scanned target
	* @return array
	*/
	public function getEmails() {
		return $this->crawMails;
	}

	/**
	* Gets scanned target links
	* @return array
	*/
	public function getURL() {
		return $this->crawDeepTargets;
	}

	/**
	* Starts email crawl scan
	* @return EmailCrawl instance of class
	*/
	public function craw() {
		$this->execute();
		return $this;
	}

	/**
	* Executes email crawl function with recursion
	* Creates new instances depending on recursion depth
	* Merges the extracted email addresses and return it
	* @return array|email
	*/
	public function execute() {
		if($this->crawLevel < $this->crawMax) {
		echo "Scanning Link [{$this->crawTarget}]...........\r\n";
		$mails = $this->getContent()->extractEmailArray();
		$this->crawDeepTargets = $this->deepURLArray();
			if(!empty($this->crawDeepTargets)){
				foreach($this->crawDeepTargets as $url) {
					$deep = new EmailCrawl($url, $this->crawMax, $this->crawMails);
					$this->crawLevel += 1;
					$deepEmails = $deep->execute();
					$this->crawMails = array_unique(array_merge($deepEmails, $mails));
				}
			}else{
				$this->crawMails = $mails;
			}
		}
		return $this->crawMails;
	}
}
