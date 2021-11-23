<?php

use Spatie\PdfToText\Pdf;
require_once(SRC_PATH . 'BaseParser.php');
require_once(SRC_PATH . 'Parser.php');

class MedikalParser extends BaseParser implements Parser
{
    public function getBaseLineArray($path)
    {

        $pdf = new Pdf(PDF_TO_TEXT_PATH);
        try {
            $pdf->setPdf($path);
        } catch (\Spatie\PdfToText\Exceptions\PdfNotFound $e) {
            throw new InvalidArgumentException();
        }

        $pdf->setOptions(['table', '-enc UTF-8', '-l 1']);
        $lineArray = explode("\n", $pdf->text());

        return array_values(array_filter($lineArray, array($this, 'removeBlanks')));
    }

    public function parse($path)
    {
        $lineArray = $this->getBaseLineArray($path);
        $cut_index = $this->containsSearch('sıra', $lineArray);
        $colonIndex = $this->containsSearch(':', $lineArray);

        // Remove the headers
        $lineArray = array_slice($lineArray, $colonIndex, $cut_index-$colonIndex);
        var_dump($lineArray);
        exit;
        return $this->parseDictionary($lineArray, array());
    }
}