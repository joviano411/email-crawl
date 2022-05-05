
<?php 
define("PHP_SHELL_EXECUTION_PATH", "path/to/php");
$crawOptions = array(
    'target' => 'https://example.com',
    'max' => 50,
);
$crawRequest = base64_encode(serialize($crawOptions));
$crawScript =  __DIR__ . "/craw.php";
$crawLogs =  __DIR__ . "/craw_logs.log";
shell_exec(PHP_SHELL_EXECUTION_PATH . " " . $crawScript . " " . $crawRequest ." 'alert' >> " . $crawLogs . " 2>&1");
