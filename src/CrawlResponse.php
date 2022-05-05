<?php
/**
 * @author      Peter Chigozie(NG) peterujah
 * @copyright   Copyright (c), 2022 Peter(NG) peterujah
 * @license     MIT public license
 */
namespace Peterujah\NanoBlock;

class CrawlResponse{
	/** 
	* Holds extracted response emails
	* @var array 
	*/
	private $emailContent;

	/** 
	* Holds total count of extracted links
	* @var array 
	*/
	private $urlCount;

	/**
	* Constructor
	* @param array $data extracted email addresses
	* @param int $count total scanned websites
	*/
	public function __construct($data, $count = 1) {
		$this->emailContent = $data;
		$this->urlCount = $count;
	}


	/**
	* Gets
	* @return array|emails
	*/
	public function body() {
		return $this->emailContent;
	}

	/**
	* Gets as array
	* @return array|emails
	*/
	public function asArray() {
		return $this->body();
	}

	/**
	* Gets in a new line
	* @return string|emails
	*/
	public function inLine() {
		return implode(PHP_EOL, $this->emailContent);
	}

	/**
	* Gets in a new comma
	* @return string|emails
	*/
	public function withComma() {
		return implode(",", $this->emailContent);
	}

	/**
	* Prints extracted emails
	* @param array|string $data content to print
	* @return CrawlResponse|Object
	*/
	public function printCommandResult($data) {
		echo "==================================================\r\n";
		echo " NanoBlock PHP EmailCraw v1.0\r\n";
		echo " Extraction complete " . count($this->emailContent) . " email was found\r\n";
		echo " Total Scanned URL's {$this->urlCount}\r\n";
		echo "==================================================\r\n\r\n";
		print_r($data ."\r\n");
		return $this;
	}

	/**
	* Save extracted emails
	* @param string $filepath path to save email
	* @return CrawlResponse|Object
	*/
	public function save($filepath) {
		return $this->saveAs($filepath, $this->emailContent);
	}

	/**
	* Save extracted emails
	* @param string $filepath path to save email
	* @param array|string contents to save
	* @return CrawlResponse|Object
	*/
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
			$data = json_encode($data);
		}

		$fp = fopen($filepath . $filename, 'w');
		fwrite($fp, $data);
		fclose($fp);
		return $this;
	}
}
