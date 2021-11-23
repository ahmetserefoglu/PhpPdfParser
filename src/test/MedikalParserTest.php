<?php

use PHPUnit\Framework\TestCase;
require_once(SRC_PATH . 'MedikalParser.php');

class MedikalParserTest
{
    public function testCanParsePdf() {
        $parser = new MedikalParser();
        $map = $parser->parse(PDF_TEST_PATH . 'optikMedula.pdf');
        print_r($map);
    }

    public function testCanThrowExceptionOnParseError() {
        $parser = new MedikalParser();
        $map = $parser->parse(PDF_TEST_PATH . 'c.pdf');
    }
}