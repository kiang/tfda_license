<?php

function getLicense($code) {
    global $target, $cache;
    $url = 'http://www.fda.gov.tw/MLMS/(S(knoy1cz5iwyfatvvguaez0re))/H0001D.aspx?Type=Lic&LicId=' . $code;
    $cacheFile = $cache . '/p_' . $code;
    if (!file_exists($cacheFile)) {
        file_put_contents($cacheFile, file_get_contents($url));
    }
    $p = file_get_contents($cacheFile);
    $lines = explode('</tr>', $p);
    $linesCount = count($lines);
    $lineNo = 0;
    $data = array(
        'code' => $code,
    );
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
                $data[trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                break;
            case 4:
            case 6:
            case 11:
            case 12:
                $part1 = explode('</th>', $cols[0]);
                $data[trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                $part2 = explode('</th>', $cols[1]);
                $data[trim(strip_tags($part2[0]))] = trim(strip_tags($part2[1]));
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
                $data['主製造廠'][trim(strip_tags($part1[0]))] = trim(strip_tags($part1[1]));
                break;
            case 21:
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
    $fPos = strpos($p, '<div id="pnlFact2">');
    if (false !== $fPos) {
        $fPart = substr($p, $fPos);
        $blocks = explode('</table>', $fPart);
        $blockIndex = -1;
        foreach ($blocks AS $block) {
            $lines = explode('</tr>', $block);
            $linesCount = count($lines);
            if ($linesCount > 5) {
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

    print_r($data);
}
