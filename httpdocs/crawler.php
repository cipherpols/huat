<?php
require_once __DIR__ . "/vendor/autoload.php";

$config = require_once __DIR__ . "/config.php";

date_default_timezone_set($config['timezone']);

/**
 * Search URL http://www.sgx.com/wps/portal/sgxweb/home/company_disclosure/company_announcements
 * Detail URL http://infopub.sgx.com/Apps?A=COW_CorpAnnouncement_Content&B=AnnouncementToday&F=3BYUBJ7KJRYINPHB&H=8cc51c6b9210cbd50c6dcc4ac3904cb3ea51081ddee084069c44af0de33de0aa
 */
$urlPatternProxy = 'http://www.sgx.com/proxy/SgxDominoHttpProxy?timeout=100&dominoHost=%s';
$urlPattern = 'http://infofeed.sgx.com/Apps?A=COW_CorpAnnouncement_Content&B=%s&S_T=%s&C_T=%s';

set_time_limit(0);

$announcements = [
	'AnnouncementLast5thYear',
	'AnnouncementLast4thYear',
	'AnnouncementLast3rdYear',
	'AnnouncementLast2ndYear',
	'AnnouncementLast1stYear',
	'AnnouncementLast12Months',
	'AnnouncementLast6Months',
	'AnnouncementLast3Months',
	'AnnouncementToday',
];
if (count($argv) < 3) {
	echo "php crawler START INDEX_TO_CRAWL". PHP_EOL;
	echo "START usually 0". PHP_EOL;
	echo "INDEX_TO_CRAWL is one of [" . implode(', ', $announcements) . "]". PHP_EOL;
	die;
}

$start = $argv[1];
$indexToCrawl = $argv[2];

if (!in_array($indexToCrawl, $announcements)) {
	echo "INDEX_TO_CRAWL is one of [" . implode(', ', $announcements) . "]". PHP_EOL;
	die;
}
$itemPerpage = 500;
$valid = true;

$databaseName = $config['database']['name'];
$index = $config['database']['companyTable'];
$collection = (new MongoDB\Client($config['database']['host']))->$databaseName->$index;

echo "Started at " . date('Y-m-d H:i:s') . PHP_EOL;
while ($valid) {
	$startTime = microtime(true);
	$ch = curl_init();
	$startForm = $start == 0 ? '' : (($start * $itemPerpage) + 1);
	$url = sprintf($urlPattern, $indexToCrawl, $startForm, $itemPerpage);
	$url = sprintf($urlPatternProxy, urlencode($url));
	//
	echo PHP_EOL . PHP_EOL . "[x] " . $url . PHP_EOL;
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, '100');
	$content = trim(curl_exec($ch));
	curl_close($ch);
	$content = substr($content, 4);
	$items = json_decode($content, true);
	
	// var_dump(count($items['items']));
	// die;
	if ($items === null) {
		echo '[x] END' . PHP_EOL;
		$valid = false;
		continue;
	}
	$insertedItems = [];
	foreach ($items['items'] as &$item) {
		if (!isset($item['key'])) {
			unset($item);
			continue;
		}
		unset($item['Date']);
		unset($item['Time']);
		$id = explode("&H=", $item['key']);
		$item['_id'] = $id[0];
		$item['Hash'] = $id[1];
		$tmpDateTime = DateTime::createFromFormat('n/d/Y h:i:s A', $item['BroadcastDateTime']);
		$item['DateTime'] = new \MongoDB\BSON\UTCDateTime($tmpDateTime->getTimestamp() * 1000);
		$item['SearchTimeGroup'] = $indexToCrawl;
		$insertedItems[] = $item;
	}
	$totalInsert = 0;
	$totalUpdated = 0;
	try {
		$insertManyResult = $collection->insertMany($insertedItems);
		$totalInsert = $insertManyResult->getInsertedCount();
	} catch (\Exception $ex) {
		echo "ERROR page: " . $url . PHP_EOL;
		echo "Try to upsert" . PHP_EOL;
		$totalInsert = 0;
		$totalUpdated = 0;
		foreach ($insertedItems as $item) {
			$upsertResult = $collection->replaceOne(['_id' => $item['_id']], $item, ['upsert' => true]);
			$totalInsert += $upsertResult->getUpsertedCount();
			$totalUpdated += $upsertResult->getModifiedCount();
		}
	}
	$end = microtime(true) - $startTime;
	echo sprintf('Inserted %d and Updated %d document(s) in %.3f s' . PHP_EOL, $totalInsert, $totalUpdated, $end);
	$start++;
}
echo "Finished at " . date('Y-m-d H:i:s') . PHP_EOL;
die;
