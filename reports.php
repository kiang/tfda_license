<?php

/*
 * http://www.fda.gov.tw/MLMS/H0008_01.aspx?Year=2015&Month=06
 */
include __DIR__ . '/H0001D.php';

$now = time();

$target = __DIR__ . '/licenses';

$cache = __DIR__ . '/cache/reports/data';
if (!file_exists($cache)) {
    mkdir($cache, 0777, true);
}

$opts = array(
    'http' => array(
        'method' => "GET",
        'header' => "Referer: https://www.fda.gov.tw/MLMS/H0008.aspx\r\n",
    ),
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
    ),
);

$context = stream_context_create($opts);

$licenses = array();

for ($m = 0; $m < 2; $m ++) {
    if ($m === 0) {
        $mTime = $now;
    } else {
        $mTime = strtotime("-1 month", $mTime);
    }
    $parts = explode('/', date('Y/m', $mTime));
    for ($i = 1; $i <= 7; $i ++) {
        $reportUrl = "https://www.fda.gov.tw/MLMS/H0008_0{$i}.aspx?Year={$parts[0]}&Month={$parts[1]}&Bigknd=";
        $page = file_get_contents($reportUrl, false, $context);
        $lines = explode('</tr>', $page);
        array_shift($lines);
        foreach ($lines AS $line) {
            $cols = explode('&amp;LicId=', $line);
            if (count($cols) === 2) {
                $licenseId = substr($cols[1], 0, strpos($cols[1], '"'));
                $licenses[$licenseId] = $licenseId;
            }
        }
    }
}

$count = count($licenses);
$n = 0;

foreach ($licenses AS $code) {
    if(++$n % 50 === 0) {
        echo "getting {$n} / {$count}\n";
    }
    getLicense($code, false);
}
