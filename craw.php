<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/plugins/autoload.php';
use \DarkScript\EmailCrawl;
$target = "https://default.com/contact";
$limit = 50;
if(!empty($argv[1])){
    if(filter_var($argv[1], FILTER_VALIDATE_URL)){
        $target = $argv[1];
        $limit = $argv[2]??50;
    }else{
        $req = unserialize(base64_decode($argv[1]));
        $target = $req["target"];
        $limit = $req["max"]??50;
    }
}
$craw = new EmailCrawl($target, $limit);
$resInstance = $craw->craw()->getResponse();
$data = $resInstance->inLine();
$resInstance->printCommandResult($data)->saveAs(__DIR__ . "/craw/", $data);
