<?php

include __DIR__ . '/H0001D.php';

$target = __DIR__ . '/licenses';

$cache = __DIR__ . '/cache/full';
if (!file_exists($cache)) {
    mkdir($cache, 0777, true);
}

foreach(glob($target . '/*/*.json') AS $jsonFile) {
    $p = pathinfo($jsonFile);
    error_log("processing {$p['filename']}");
    getLicense($p['filename']);
}