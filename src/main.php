<?php

    chdir(__DIR__);
    require_once(__DIR__ . '/../vendor/autoload.php');
    define('PDF_TO_TEXT_PATH', __DIR__ . '/../sources/binaries/pdftotext.exe');
    define('PDF_INFO_PATH', __DIR__ . '/../sources/binaries/pdfinfo.exe');
    define('PDF_TEST_PATH', __DIR__ . '/../sources/pdf/');
    define('DOCS_TEST_PATH', __DIR__ . '/../sources/docs/');

    define('TEST_PATH', 'test/');
    define('SRC_PATH', 'main/');

    require_once(TEST_PATH . 'MedikalParserTest.php');


    // Handler necessary for error capturing
    function exceptions_error_handler($severity, $message, $filename, $lineno) {
        if (error_reporting() == 0) {
            return;
        }
        if (error_reporting() & $severity) {
            throw new ErrorException($message, 0, $severity, $filename, $lineno);
        }
    }
    set_error_handler('exceptions_error_handler');

    // Test Optik parser
    $medikalParserTest = new MedikalParserTest();
    $medikalParserTest->testCanParsePdf();
    // $medikalParserTest->testCanThrowExceptionOnParseError();
