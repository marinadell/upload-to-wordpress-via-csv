<?php

class WP_User {}

require_once(__DIR__ . '/../wordpress_stubs.php');
require_once(__DIR__ . '/../../../../timber-library/vendor/autoload.php');


class ErrorEmailTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }


    protected function _after()
    {
    }


    // tests
    public function testConstuction()
    {
        $errors = [];
        $email  = new \FsrImporter\Errors\ErrorEmail($errors);
    }
}