<?php

use Spatie\PdfToText\Pdf;

class BaseParser
{

    public function getBaseLineArray($path) {
        $numberOfPages = $this->getNumberOfPages($path);
        $pdf = new Pdf(PDF_TO_TEXT_PATH);
        try {
            $pdf->setPdf($path);
        } catch (\Spatie\PdfToText\Exceptions\PdfNotFound $e) {
            throw new InvalidArgumentException();
        }

        $pdf->setOptions(['simple', '-enc UTF-8', '-f '.$numberOfPages, '-l '.$numberOfPages]);
        $arr = explode("\n", $pdf->text());

        $arr = $this->regulateSpaces($arr);
        return array_values(array_filter($arr, array($this, 'removeBlanks')));
    }


    function containsSearch($keyword, $arrayToSearch){
        foreach($arrayToSearch as $key => $arrayItem){
            if( stristr( $arrayItem, $keyword ) ){
                return $key;
            }
        }
        return -1;
    }

    public function getSection($lineArray, $start, $end, $offsetStart = 0, $offsetEnd = 0) {
        $resultLineArray = array_slice($lineArray, $this->containsSearch($start, $lineArray)+$offsetStart);
        return array_slice($resultLineArray, $offsetEnd, $this->containsSearch($end, $resultLineArray));
    }

    public function parseBaseHeader($lineArray) {
        $xmlMap = array();
        $group = trim(explode('Grubu', $lineArray[$this->containsSearch('Grubu', $lineArray)])[0]);
        $splittedLine = explode('Döküm Numarası:', $lineArray[$this->containsSearch('Döküm Numarası:', $lineArray)]);
        $splittedEczaneBilgi = trim(explode(':', $splittedLine[0])[1]);
        $splittedAdDokumNumarasi = explode('/', $splittedEczaneBilgi);
        $eczaneAdi = trim($splittedAdDokumNumarasi[1]);
        $eczaneSicilNumarasi = trim($splittedAdDokumNumarasi[0]);
        $dokumNumarasi = trim($splittedLine[1]);

        $xmlMap['Grubu'] = $group;
        $xmlMap['DokumNumarasi'] = $dokumNumarasi;
        $xmlMap['EczaneAdi'] = $eczaneAdi;
        $xmlMap['EczaneSicilNumarasi'] = $eczaneSicilNumarasi;
        $xmlMap['Yabanci'] = true;
        return $xmlMap;
    }

    public function parseBaseLineArray($lineArray, $xmlMap) {
        foreach ($lineArray as $entry) {
            // Parse regular entries
            if (substr_count($entry, ':') == 1) {
                $splittedLine = explode(':', $entry);
                $key = $splittedLine[0];
                $key = self::cleanXMLTag($key);
                $value = $splittedLine[1];
                $xmlMap[$key] = $value;
                // Parse, or skip, faulty entries
            } else if (substr_count($entry, ':') == 0) {
                if (substr_count($entry, ' ') > 0) {
                    $splittedLine = explode(' ', $entry);
                    $len = count($splittedLine);
                    $value = $splittedLine[$len-1]; // last element is the value
                    $key = implode(' ', array_slice($splittedLine, 0, $len-1));
                    $key = self::cleanXMLTag($key);
                }
                $xmlMap[$key] = $value;
            }
        }

        $size = count($lineArray);
        // Static parse irregular entries
        $titles = array_map('trim', explode(':', $lineArray[$size-4]));

        for ($i = 0; $i < count($titles); $i++) {
            $titles[$i] = self::cleanXMLTag($titles[$i]);
            $xmlMap[$titles[$i]] = array();
        }

        for ($line_index = $size-3; $line_index < $size; $line_index++) {
            preg_match_all('/([\S]+([\s]+[^\s:]+)*):[\s]*([\S]+)/', $lineArray[$line_index], $entries);
            $keys = $entries[1];
            $values = $entries[3];

            for ($i = 0; $i < count($keys); $i++) {
                $keys[$i] = self::cleanXMLTag($keys[$i]);
            }

            $xmlMap[$titles[0]][$keys[0]] = $values[0]; // elden tahsil
            $xmlMap[$titles[1]][$keys[1]] = $values[1]; // maaştan kesilen
            if (count($keys) >= 3) {
                $xmlMap[$titles[2]][$keys[3]] = $values[3]; // kesilen fatura
                $xmlMap[$keys[2]] = $values[2]; // reçete adedi ve psf miktarı
            }
        }
        return $xmlMap;
    }

    public function getNumberOfPages($pdfDocumentPath) {
        exec(PDF_INFO_PATH.' '.$pdfDocumentPath, $numberOfPages);
        $numberOfPages = preg_grep('/Pages:[\s]+([0-9]+)/', $numberOfPages);
        $numberOfPages = reset($numberOfPages);
        $numberOfPages = preg_match('/[0-9]+/', $numberOfPages, $match);
        $numberOfPages = reset($match);
        return $numberOfPages;
    }

    public function cleanXMLTag($string) {
        $turkish = array("ı", "ğ", "ü", "ş", "ö", "ç", "İ", "Ğ", "Ü", "Ş", "Ö", "Ç");
        $english   = array("i", "g", "u", "s", "o", "c", "I", "G", "U", "S", "O", "C");
        $string = str_replace($turkish, $english, $string);
        $string = preg_replace('/[\s\W]+/', '', $string);
        return $string;
    }

    public function regulateSpaces($arr) {
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = preg_replace('/[\s]+/', ' ', $arr[$i]);
            $arr[$i] = trim($arr[$i]);
        }
        return $arr;
    }

    public function removeBlanks($line)
    {
        if (strcmp(trim($line), '') == 0) {
            return false;
        }
        return true;
    }

    public function contains($needle, $haystack)
    {
        return strpos($haystack, $needle) !== false;
    }

    public function getWords($line) {
        preg_match_all('/[\S]+/', $line, $words);
        $words = $words[0];
        return $words;
    }

    public function getMaxIndex($line) {
        preg_match_all('/[\s]+/', $line, $spaces);
        $spaces = $spaces[0];
        $spaces = array_map('strlen', $spaces);
        return array_keys($spaces, max($spaces))[0];
    }

    public function parseDictionary($lineArray, $xml_map) {
        foreach($lineArray as $line) {
            if (substr_count($line,':') > 1) {
                $words = $this->getWords($line);
                $entries = array();
                $max_index = $this->getMaxIndex($line);
                array_push($entries, join(' ', array_slice($words, 0, $max_index+1)));
                array_push($entries, join(' ', array_slice($words, $max_index+1)));

                foreach ($entries as $entry) {
                    $splittedEntry = explode(':', $entry);
                    $key = $this->cleanXMLTag($splittedEntry[0]);
                    $value = trim($splittedEntry[1]);
                    $xml_map[$key] = $value;
                }

            } else if(substr_count($line,':') == 1) {
                $splittedEntry = explode(':', $line);
                $key = $this->cleanXMLTag($splittedEntry[0]);
                $value = trim($splittedEntry[1]);
                $xml_map[$key] = $value;

            }
        }
        return $xml_map;
    }

    public function insertIntoPosition($array, $index, $val)
    {
        $size = count($array); //because I am going to use this more than one time
        if (!is_int($index) || $index < 0 || $index > $size)
        {
            return -1;
        }
        else
        {
            $temp   = array_slice($array, 0, $index);
            $temp[] = $val;
            return array_merge($temp, array_slice($array, $index, $size));
        }
    }

}