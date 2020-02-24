<?php

function getLicense($code, $toCache = true) {
    global $target, $cache;
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

    $url = 'https://www.fda.gov.tw/MLMS/H0001D.aspx?Type=Lic&LicId=' . $code;
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
    $lines = explode('</tr>', $p);
    foreach($lines AS $k => $v) {
        if($k > 5) {
            $posBegin = strpos($v, '<tr');
            $lines[$k] = substr($v, $posBegin);
        }
    }
    $linesCount = count($lines);
    $lineNo = 0;
    $data = array(
        'code' => $code,
        'url' => $url,
        'time' => date('Y-m-d H:i:s', filemtime($cacheFile)),
    );
    if (false !== strpos($p, '醫器規格')) {
        foreach ($lines AS $line) {
            ++$lineNo;
            $cols = explode('</td>', $line);
            switch ($lineNo) {
                case 2:
                    $part1 = explode('</caption>', $cols[0]);
                    $part2 = explode('<span id="lblLicName">', $part1[0]);
                    $data['許可證字號'] = substr($part2[1], 0, strpos($part2[1], '<'));
                    $part3 = explode('</th>', $part1[1]);
                    $data[trim(strip_tags($part3[0]))] = trim(strip_tags($part3[1]));
                    $part4 = explode('</th>', $cols[1]);
                    $data[trim(strip_tags($part4[0]))] = trim(strip_tags($part4[1]));
                    break;
                case 3:
                case 5:
                case 7:
                case 8:
                case 9:
                case 10:
                case 11:
                case 16:
                case 18:
                case 19:
                    $part1 = explode('</th>', $cols[0]);
                    $data[trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    break;
                case 4:
                case 6:
                case 12:
                case 13:
                case 14:
                case 15:
                    $part1 = explode('</th>', $cols[0]);
                    $data[trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    $part2 = explode('</th>', $cols[1]);
                    $data[trim(strip_tags($part2[0]))] = trim(strip_tags($part2[1]));
                    break;
                case 17:
                    $part1 = explode('</th>', $cols[0]);
                    $data[trim(strip_tags($part1[0]))] = explode('[|]', str_replace(array(' ', '　'), '', trim(strip_tags(str_replace('<BR>', '[|]', $part1[1])))));
                    break;
                case 20:
                    $data['主製造廠'] = array();
                    break;
                case 21:
                case 22:
                case 23:
                    $part1 = explode('</th>', $cols[0]);
                    $data['主製造廠'][trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    break;
                case 24:
                    $part1 = explode('</th>', $cols[0]);
                    $data['主製造廠'][trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    $part2 = explode('</th>', $cols[1]);
                    $data['主製造廠'][trim(strip_tags($part2[0]))] = trim(strip_tags($part2[1]));
                    break;
                default:
                    if ($linesCount - $lineNo === 1 || $linesCount - $lineNo === 2) {
                        $part1 = explode('</th>', $cols[0]);
                        $data[trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    }
            }
        }
    } else {
        foreach ($lines AS $line) {
            ++$lineNo;
            $cols = explode('</td>', $line);
            switch ($lineNo) {
                case 2:
                    $part1 = explode('</caption>', $cols[0]);
                    $part2 = explode('<span id="lblLicName">', $part1[0]);
                    $data['許可證字號'] = substr($part2[1], 0, strpos($part2[1], '<'));
                    $part3 = explode('</th>', $part1[1]);
                    $data[trim(strip_tags($part3[0]))] = trim(strip_tags($part3[1]));
                    $part4 = explode('</th>', $cols[1]);
                    $data[trim(strip_tags($part4[0]))] = trim(strip_tags($part4[1]));
                    break;
                case 3:
                case 5:
                case 7:
                case 8:
                case 9:
                case 10:
                case 13:
                case 15:
                case 16:
                    $part1 = explode('</th>', $cols[0]);
                    if (isset($part1[1])) {
                        $data[trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    }
                    break;
                case 4:
                case 6:
                case 11:
                case 12:
                    $part1 = explode('</th>', $cols[0]);
                    $data[trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    $part2 = explode('</th>', $cols[1]);
                    if (isset($part2[1])) {
                        $data[trim(strip_tags($part2[0]))] = trim(strip_tags($part2[1]));
                    }
                    break;
                case 14:
                    $part1 = explode('</th>', $cols[0]);
                    $data[trim(strip_tags($part1[0]))] = explode('[|]', str_replace(array(' ', '　'), '', trim(strip_tags(str_replace('<BR>', '[|]', $part1[1])))));
                    break;
                case 17:
                    $data['主製造廠'] = array();
                    break;
                case 18:
                case 19:
                case 20:
                    $part1 = explode('</th>', $cols[0]);
                    if (isset($part1[1])) {
                        $data['主製造廠'][trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    }
                    break;
                case 21:
                    $part1 = explode('</th>', $cols[0]);
                    $data['主製造廠'][trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                    if(isset($cols[1])) {
                        $part2 = explode('</th>', $cols[1]);
                        if(isset($part2[1])) {
                          $data['主製造廠'][trim(strip_tags($part2[0]))] = trim(strip_tags($part2[1]));
                        }    
                    }
                    break;
                default:
                    if ($linesCount - $lineNo === 1 || $linesCount - $lineNo === 2) {
                        $part1 = explode('</th>', $cols[0]);
                        if(isset($part1[1])) {
                            $data[trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                        }
                    }
            }
        }
        if(isset($data[''])) {
            unset($data['']);
        }
        $fPos = strpos($p, '<div id="pnlFact2">');
        if (false !== $fPos) {
            $fPart = substr($p, $fPos);
            $blocks = explode('</table>', $fPart);
            $blockIndex = -1;
            foreach ($blocks AS $block) {
                $lines = explode('</tr>', $block);
                $linesCount = count($lines);
                if ($linesCount > 5) {
                    if ($linesCount === 7) {
                        array_shift($lines);
                    }
                    ++$blockIndex;
                    if (!isset($data['次製造廠'])) {
                        $data['次製造廠'] = array();
                    }
                    $data['次製造廠'][$blockIndex] = array();
                    $lineNo = 0;
                    foreach ($lines AS $line) {
                        ++$lineNo;
                        $cols = explode('</td>', $line);
                        switch ($lineNo) {
                            case 2:
                            case 3:
                            case 4:
                                $part1 = explode('</th>', $cols[0]);
                                $data['次製造廠'][$blockIndex][trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                                break;
                            case 5:
                                $part1 = explode('</th>', $cols[0]);
                                $data['次製造廠'][$blockIndex][trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                                $part2 = explode('</th>', $cols[1]);
                                $data['次製造廠'][$blockIndex][trim(strip_tags($part2[0]))] = trim(strip_tags($part2[1]));
                                break;
                        }
                    }
                }
            }
        }
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
