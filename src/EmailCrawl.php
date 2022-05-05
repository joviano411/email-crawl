<?php
namespace Peterujah\NanoBlock;
use \Peterujah\NanoBlock\CrawlResponse;
class EmailCrawl{
  public const VERIFY_HOST = 0;
  public const SSL_VERIFY_PEER = false;
  private $crawLink;
  private $crawLevel = 0;
  private $crawMax = 50;
  private $crawMails = array();
  private $crawUrls = array();
  private $crawContents;
  private $options = array(
    'http' => array(
        'method'=>"GET",
        'header'=>"Content-Type: text/html; charset=utf-8"
    )
  );
  private $crawOutput;

	
  /**
   * Constructor
   * @param string $arg1, int $arg2, int $arg3, string $arg4
   */
  public function __construct($url, $max = 50, $level = 0, $mails = array()) {
      $this->init($url, $max, $level, $mails);
  }
  
  private function init($url, $max, $level, $mails) {
      if(!$this->isCli()){ 
        //trigger_error('Please use php-cli!.', E_USER_ERROR);
      }
      $this->crawLink = $url;
      $this->crawLevel = $level;
      $this->crawMax = $max;
      $this->crawMails = $mails;
  }
 
  /**
   * Check if you use the php command line to run this script
   * @return boolean
   */
  private function isCli() {
      return php_sapi_name()==="cli";
  }

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
   * Get the content of the current page ($this->hp)
   * @return string
   */
  public function getContent() {
      if (!function_exists('curl_init')){
          $this->crawContents = file_get_contents($this->crawLink, false, $this->getContext());
      } else {
        $this->crawContents = $this->curl_get_contents($this->crawLink);
      }
      return $this;
  }
  
  /**
   * Get the context to open the FGC socket
   * @return stream_context_resource
   */
  private function getContext() {
      return stream_context_create($this->options);
  }
	
  /**
   * Use the content to create an email array
   * Make sure we don't save the same email address multiple times
   * @return array
   */
  public function getEmailArray() {
      $pattern1 = "(([-_.\w]+@[a-zA-Z0-9_]+?\.[a-zA-Z0-9]{2,6}))";
      $pattern2 = "(\w[-._\w]*\w@\w[-._\w]*\w\.\w{2,3})";
      preg_match_all($pattern1, $this->crawContents, $match1, PREG_PATTERN_ORDER);
      preg_match_all($pattern2, $this->crawContents, $match2, PREG_PATTERN_ORDER);
      $emails = $this->array_unique_deep(array_merge($match1, $match2));
      return $emails;
  }
 
  /**
   * Deletes duplicate values on multi dimensional arrays
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
   * Fetch URLs from the current site to use them later (recursion)
   * Make sure to delete duplicate entries
   * @return array
   */
  public function getURLArray() {
      $pattern = '((\:href=\"|(http(s?))\://){1}\S+)';
      //$pattern = '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#';
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
   * A little function to set www/http prefixes
   * @param URL-Array $array
   * @return array
   */
  private function setURLPrefix($array) {
      $prefix_array=array(); $i=0;
      foreach ($array as $part) {
          if(preg_match('/^(www\.)/', $part)) $prefix_array[$i]='http://'.$part;
          else $prefix_array[$i]=$part;
          $i++;
      }
      return $prefix_array;
  }
	
  /**
   * Temporarily function to print the result
   */
  public function printResult($data) {
    print_r($data);
  }

    public function getResponse() {
        return new \CrawlResponse($this->crawMails, 1+count($this->crawUrls));
    }

    public function getEmails() {
        return $this->crawMails;
    }

    public function getURL() {
        return $this->crawUrls;
    }

  
  public function craw() {
    $this->execute();
    return $this;
  }
  /**
   * Start function with recursion
   * Creates new instances depending on recursion depth
   * Merges the obtained email addresses and returns them
   * @return mails
   */
  public function execute() {
    if($this->crawLevel < $this->crawMax) {
       $mails = $this->getContent()->getEmailArray();
       $this->crawUrls = $this->getURLArray();
       if(!empty($this->crawUrls)){
           foreach($this->crawUrls as $url) {
               $deep = new EmailCrawl(
                   $url,
                   $this->crawMax, 
                   $this->crawLevel+1, 
                   $this->crawMails
                );
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
