<?php

/*
 * http://www.fda.gov.tw/MLMS/(S(knoy1cz5iwyfatvvguaez0re))/H0008_01.aspx?Year=2015&Month=06
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
        'header' => "Referer: http://www.fda.gov.tw/MLMS/(S(cmnhdc55ym011jrvaetopgju))/H0008.aspx\r\n",
    )
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
        $reportUrl = "http://www.fda.gov.tw/MLMS/(S(knoy1cz5iwyfatvvguaez0re))/H0008_0{$i}.aspx?Year={$parts[0]}&Month={$parts[1]}&Bigknd=";
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

foreach ($licenses AS $code) {
    getLicense($code, false);
}