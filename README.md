# email-crawl
 PHP Email web crawler. using curl or command line. 

## Installation

Installation is super-easy via Composer:
```md
composer require peterujah/email-crawl
```

## Basic Usage

Initalize email crawl instance
```php
$craw = new EmailCrawl("https://example.com", 200);
```


Star email crawling scan

```php
$craw->craw()
```

Get scanned response and return CrawlResponse instance

```php
$craw->craw()
$response = $craw->getResponse();
```

Get response emails separate in a new line

```php
$data = $response->inLine();
```

Get response emails separate with a comma

```php
$data = $response->withComma();
```

Get response emails as an array
```php
$data = $response->asArray();
```

Print response email 
```php
$response->printCommandResult($data);
```

Save response emails to file. This will save result as json string
```php
$response->save("/path/save/craw/");
```

Save response emails to file. If string data is passed it will save it, els it will save result as json string
```php
$response->saveAs("/path/save/craw/", $data);
```

Example

Create a file name it craw.php, inside the file add this example code.
With this example you can run your craw directly from `command line, browser or php shell_exec`.

```php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/plugins/autoload.php';
use Peterujah\NanoBlock\EmailCrawl;
$target = "https://example.com/contact";
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
$response = $craw->craw()->getResponse();
$data = $response->inLine();
$response->printCommandResult($data)->saveAs(__DIR__ . "/craw/", $data);
```

Execute craw through command line interface, run the below command
```cli
php craw.php https://google.com 50
```

Execute craw through php shell_exec, create a file call exec.php and add below example script.
Note: change `PHP_SHELL_EXECUTION_PATH` to your php executable path.
Once done navigate to https://mycraw.example.com/exec.php
```php
define("PHP_SHELL_EXECUTION_PATH", "path/to/php");
$crawOptions = array(
    'target' => 'https://example.com',
    'max' => 50,
);
$crawRequest = base64_encode(serialize($crawOptions));
$crawScript =  __DIR__ . "/craw.php";
$crawLogs =  __DIR__ . "/craw_logs.log";
shell_exec(PHP_SHELL_EXECUTION_PATH . " " . $crawScript . " " . $crawRequest ." 'alert' >> " . $crawLogs . " 2>&1");
```

# ATTENTION

Is advisable to run this code in command line interface for be better performance.
