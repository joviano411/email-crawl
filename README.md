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


Star email crawling and return response instance

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
