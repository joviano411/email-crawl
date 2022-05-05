<?php
namespace Peterujah\NanoBlock;
class CrawlResponse{
    private $emailContent;
    private $urls;
	
  /**
   * Constructor
   * @param string $arg1, int $arg2, int $arg3, string $arg4
   */
  public function __construct($data, $count = 1) {
    $this->emailContent = $data;
    $this->urls = $count;
  }

  public function body() {
    return $this->emailContent;
  }

  public function asArray() {
    return $this->body();
  }

  public function inLine() {
      $line = "";
      foreach($this->emailContent as $email){
        $line .= "{$email}\n";
      }
      return $line;
  }

  public function withComma() {
    $comma = "";
      foreach($this->emailContent as $email){
        $comma .= "{$email},";
      }
      return $comma;
  }
  
  public function printCommandResult($data) {
    echo "==================================================\r\n";
    echo " NanoBlock PHP EmailCraw v1.0\r\n";
    echo " Extraction complete " . count($this->emailContent) . " email was found\r\n";
    echo " Total Scanned URL's {$this->urls}\r\n";
    echo "==================================================\r\n\r\n";
    print_r($data ."\r\n");
    return $this;
  }

  public function save($filepath) {
    return $this->saveAs($filepath, $this->emailContent);
  }

  public function saveAs($filepath, $data) {
     $filename = uniqid() . ".txt";
     if(!is_dir($filepath)){
          mkdir($filepath, 0777, true);
          chmod($filepath, 0755); 
      }

      if(file_exists($filepath . $filename)){
          unlink($filepath . $filename);
      }

       if(is_array($data)){
        //$data = serialize($data);
        $data = json_encode($data);
       }

      $fp = fopen($filepath . $filename, 'w');
      fwrite($fp, $data);
      fclose($fp);
      return $this;
  }
}
