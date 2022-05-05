<?php

class EmailCrawl{
  public const COMMA = ",";
  public const NEWLINE = "\n";
  private $crawLink;
  private $crawLevel;
  private $crawMax;
  private $crawMails;
  private $crawUrls;
  private $crawContents;
	
  /**
   * Constructor
   * @param string $arg1, int $arg2, int $arg3, string $arg4
   */
  public function __construct($url, $level = 0, $max = 50, $mails = array()) {
      $this->init($url, $level, $max, $mails);
  }
  
  private function init($url, $level, $max, $mails) {
      if(!$this->isCli()){ 
        trigger_error("Please use php-cli!");
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
	
  /**
   * Get the content of the current page ($this->hp)
   * @return string
   */
  public function getContent() {
      if (!function_exists('curl_init')){
          $content = file_get_contents($this->crawLink, false, $this->getContext());
      } else {
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $this->crawLink);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $content = curl_exec($ch);
          curl_close($ch);
      }
      return $content;
  }
  
  /**
   * Get the context to open the FGC socket
   * @return stream_context_resource
   */
  private function getContext() {
      $opts = array(
          'http' => array(
              'method'=>"GET",
              'header'=>"Content-Type: text/html; charset=utf-8"
          )
      );
      return stream_context_create($opts);
  }
	
  /**
   * Use the content to create an email array
   * Make sure we don't save the same email address multiple times
   * @return array
   */
  public  function getEmailArray() {
      $email_pattern_normal="(([-_.\w]+@[a-zA-Z0-9_]+?\.[a-zA-Z0-9]{2,6}))";
      $email_pattern_exp1="(\w[-._\w]*\w@\w[-._\w]*\w\.\w{2,3})";
      preg_match_all($email_pattern_normal, $this->crawContents, $result_email_normal, PREG_PATTERN_ORDER);
      preg_match_all($email_pattern_exp1, $this->crawContents, $result_email_exp1, PREG_PATTERN_ORDER);
      $email_array=array_merge($result_email_normal, $result_email_exp1);
      $unique_emails=$this->array_unique_deep($email_array);
      return $unique_emails;
  }
 
  /**
   * Deletes duplicate values on multi dimensional arrays
   * @return array
   */
  private function array_unique_deep($array) {
      $values=array();
      foreach ($array as $part) {
          if (is_array($part)) {
            $values=array_merge($values,$this->array_unique_deep($part));
          } else { 
            $values[]=$part;
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
      $url_pattern='((\:href=\"|(http(s?))\://){1}\S+)';
      preg_match_all($url_pattern, $this->crawContents, $result_url, PREG_PATTERN_ORDER);
      array_walk($result_url[0], function(&$item) { $item = substr($item, 0, strpos($item, '"')); });
      $unique_urls=$this->array_unique_deep($result_url[0]);
      $unique_urls=array_unique($this->setURLPrefix($unique_urls));
      return $unique_urls;
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

  public function saveResult($filepath, $data) {
     $$filename = uniqid() . ".txt";
     if(!is_dir($filepath)){
          mkdir($filepath, 0777, true);
          chmod($filepath, 0755); 
      }

      if(file_exists($filepath . $filename)){
          unlink($filepath . $filename);
      }

      $fp = fopen($filepath . $filename, 'w');
      fwrite($fp, $data);
      fclose($fp);
  }
	
  /**
   * Start function with recursion
   * Creates new instances depending on recursion depth
   * Merges the obtained email addresses and returns them
   * @return mails
   */
  public function run() {
     if($this->crawLevel < $this->crawMax) {
         $this->crawContents = $this->getContent();
         $this->crawUrls = $this->getURLArray();
         $mails = $this->getEmailArray();
         foreach($this->crawUrls as $url) {
            $temp = new EmailCrawl($url, $this->crawLevel+1, $this->crawMax, $this->crawMails);
            $this->crawMails = array_unique(array_merge($temp->run(), $mails));
         }
     }
     return $this->crawMails;
  }
}
