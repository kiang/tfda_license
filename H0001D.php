<?php
$count = 0;
function getLicense($code, $toCache = true) {
    global $target, $cache, $count;
    $opts = array(
        'http' => array(
            'method' => "GET",
            'header' => "Referer: https://info.fda.gov.tw/MLMS/H0008.aspx\r\n",
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
        ),
    );

    $context = stream_context_create($opts);

    $url = 'https://info.fda.gov.tw/MLMS/H0001D.aspx?Type=Lic&LicId=' . $code;
    $cacheFile = $cache . '/p_' . $code;
    if (!file_exists($cacheFile) || false === $toCache) {
        file_put_contents($cacheFile, file_get_contents($url, false, $context));
    }
    $targetFolder = $target . '/' . substr($code, 0, 2);
    if (!file_exists($targetFolder)) {
        mkdir($targetFolder, 0777, true);
    }
    if (filesize($cacheFile) === 0) {
        unlink($cacheFile);
        return false;
    }
    $p = file_get_contents($cacheFile);

    //try to get license id
    $licenseId = '';
    $pos = strpos($p, '<span id="lblLicName">');
    if(false !== $pos) {
        $posEnd = strpos($p, '</span>', $pos);
        $licenseId = trim(strip_tags(substr($p, $pos, $posEnd - $pos)));
    }

    // stop the process and delete existed json file if empty $licenseId found
    if(empty($licenseId)) {
        if(file_exists("{$targetFolder}/{$code}.json")) {
            unlink("{$targetFolder}/{$code}.json");
        }
        return false;
    }

    $pos = strpos($p, '<th');
    $blockKey = false;
    $blockStack = array();
    $data = array(
        'code' => $code,
        'url' => $url,
        'time' => date('Y-m-d H:i:s', filemtime($cacheFile)),
        '許可證字號' => $licenseId,
    );
    while(false !== $pos) {
        $posEnd = strpos($p, '</th>', $pos);
        $field = trim(strip_tags(substr($p, $pos, $posEnd - $pos)));
        $pos = $posEnd;
        $posEnd = strpos($p, '</td>', $pos);
        $value = substr($p, $pos, $posEnd - $pos);
        $valueThPos = strpos($value, '<th');
        if(false !== $valueThPos) {
            $blockKey = $field;
            $valueThPosEnd = strpos($value, '</th>', $valueThPos);
            $field = trim(strip_tags(substr($value, $valueThPos, $valueThPosEnd - $valueThPos)));
            $value = trim(strip_tags(substr($value, $valueThPosEnd)));
            $blockStack[$field] = $value;
        } else {
            $value = trim(strip_tags($value));
            if(false !== $blockKey) {
                $blockStack[$field] = $value;
                if($field === '製程') {
                    if(!isset($data[$blockKey])) {
                        $data[$blockKey] = array();
                    }
                    $data[$blockKey][] = $blockStack;
                    $blockKey = false;
                    $blockStack = array();
                }
            } else {
                $data[$field] = $value;
            }
        }
        
        $pos = strpos($p, '<th', $posEnd);
    }

    /*
     * > 詳細處方成分資料 - H0001D1.aspx?LicId=
     * > 藥物辨識詳細資料 - H0001D2.aspx?LicId=
     * > 仿單/外盒資料 - H0001D3.aspx?LicId=
     * 授權使用 - H0001D4.aspx?LicId=
     * CCC號列 - H0001D5.aspx?LicId=
     * 藥理治療分類 - H0001D6.aspx?LicId=
     * 藥理分類(舊) - H0001D7.aspx?LicId=
     * 健保藥價資料 - H0001D8.aspx?LicId=
     * 專利權資料 - H0001D9.aspx?LicId=
     */
    $url = 'https://www.fda.gov.tw/MLMS/H0001D1.aspx?LicId=' . $code;
    $cacheFile = $cache . '/p1_' . $code;
    if (!file_exists($cacheFile) || false === $toCache) {
        file_put_contents($cacheFile, file_get_contents($url, false, $context));
    }
    $p = file_get_contents($cacheFile);
    $p = str_replace('&nbsp;', '', $p);
    $lines = explode('</tr>', $p);
    $ingredientIndex = 0;
    $data['ingredients'] = array();
    $ingredientSwitch = false;
    foreach ($lines AS $line) {
        $cols = explode('</td>', $line);
        if (count($cols) === 4) {
            foreach ($cols AS $k => $v) {
                $cols[$k] = preg_replace('/\s+/', ' ', trim(strip_tags($v)));
            }
            if (false === $ingredientSwitch) {
                $ingredientSwitch = true;
                $data['ingredients'][$ingredientIndex] = array(
                    '成分類別' => $cols[0],
                    '成分代碼' => $cols[1],
                    '成分名稱' => $cols[2],
                );
            } else {
                $ingredientSwitch = false;
                $data['ingredients'][$ingredientIndex]['含量描述'] = $cols[0];
                $data['ingredients'][$ingredientIndex]['含量'] = $cols[1];
                $data['ingredients'][$ingredientIndex]['單位'] = $cols[2];
                ++$ingredientIndex;
            }
        }
    }

    $url = 'https://www.fda.gov.tw/MLMS/H0001D2.aspx?LicId=' . $code;
    $cacheFile = $cache . '/p2_' . $code;
    if (!file_exists($cacheFile) || false === $toCache) {
        file_put_contents($cacheFile, file_get_contents($url, false, $context));
    }
    $p = file_get_contents($cacheFile);
    if (false === strpos($p, '無藥物辨識資料') && false === strpos($p, '無藥物辨識外觀圖片')) {
        $data['藥物辨識詳細資料'] = array();
        $data['藥物辨識外觀圖片'] = array();
        $lines = explode('</tr>', $p);
        $imageSwitch = false;
        foreach ($lines AS $line) {
            if (false !== strpos($line, '藥物辨識外觀圖片')) {
                $imageSwitch = true;
            }
            if ($imageSwitch) {
                $pos = strpos($line, 'ShowFile.aspx');
                if (false !== $pos) {
                    while (false !== $pos) {
                        $posEnd = strpos($line, '\'', $pos);
                        $currentUrl = 'https://www.fda.gov.tw/MLMS/' . substr($line, $pos, $posEnd - $pos);
                        $pos = strpos($line, '>', $posEnd) + 1;
                        $posEnd = strpos($line, '<', $pos);
                        $title = preg_replace('/\s+/', ' ', trim(strip_tags(substr($line, $pos, $posEnd - $pos))));

                        if (empty($title)) {
                            $title = '圖片';
                        }

                        $data['藥物辨識外觀圖片'][] = array(
                            'title' => $title,
                            'url' => $currentUrl,
                        );

                        $pos = strpos($line, 'ShowFile.aspx', $posEnd);
                    }
                }
            } elseif (false === strpos($line, '文品名')) {
                $cols = explode('</td>', $line);
                foreach ($cols AS $col) {
                    $parts = explode('</th>', $col);
                    if (count($parts) === 2) {
                        foreach ($parts AS $k => $v) {
                            $parts[$k] = preg_replace('/\s+/', ' ', trim(strip_tags($v)));
                        }
                        $data['藥物辨識詳細資料'][$parts[0]] = $parts[1];
                    }
                }
            }
        }
    }

    $url = 'https://www.fda.gov.tw/MLMS/H0001D3.aspx?LicId=' . $code;
    $cacheFile = $cache . '/p3_' . $code;
    if (!file_exists($cacheFile) || false === $toCache) {
        file_put_contents($cacheFile, file_get_contents($url, false, $context));
    }
    $p = file_get_contents($cacheFile);
    $p = str_replace('&nbsp;', '', $p);
    $pos = strpos($p, 'ShowFile.aspx');
    if (false !== $pos) {
        $data['仿單外盒'] = array();
        while (false !== $pos) {
            $posEnd = strpos($p, '\'', $pos);
            $currentUrl = 'https://www.fda.gov.tw/MLMS/' . substr($p, $pos, $posEnd - $pos);
            $pos = strpos($p, '>', $posEnd) + 1;
            $posEnd = strpos($p, '<', $pos);
            $title = preg_replace('/\s+/', ' ', trim(strip_tags(substr($p, $pos, $posEnd - $pos))));
            if (empty($title)) {
                $title = '仿單外盒';
            } else {
                $title = str_replace('圖檔名稱 ', '', $title);
            }
            $data['仿單外盒'][] = array(
                'title' => $title,
                'url' => $currentUrl,
            );
            $pos = strpos($p, 'ShowFile.aspx', $posEnd);
        }
    }

    file_put_contents("{$targetFolder}/{$code}.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
