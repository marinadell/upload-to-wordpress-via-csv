<?php

require_once( __DIR__ . '/wordpress_stubs.php' );
require_once( __DIR__ . '/../../settings-page.php' );

class SettingsPageTest extends \Codeception\Test\Unit
{
    // This test will catch any syntax errors in the distributor-account file.
    public function testCodeHasNoSyntaxErrors()
    {
        $this->assertTrue(true);
    }

    public function testInvalidPassword()
    {
        $result = FsrImporter\has_error_message('2019-08-28 01:38:18 > Received error callback from Skyloader with error: :invalid-password');
        $this->assertTrue($result);
    }

    public function testNoError()
    {
        $result = FsrImporter\has_error_message('2019-08-15 15:26:40 > Credentials Validated with no error');
        $this->assertFalse($result);
    }

    public function testCSVImport()
    {
        $result = FsrImporter\has_error_message('2019-08-14 17:41:35 > Finished processing account from CSV import.');
        $this->assertFalse($result);
    }

    public function testNoResultFromDateRange()
    {
        $result = FsrImporter\has_error_message('2019-08-28 14:21:22 > Received error callback from Skyloader with error: :no-results-for-date-range');
        $this->assertFalse($result);
    }

    public function testErrorValidatingCredentials()
    {
        $result = FsrImporter\has_error_message('2019-09-09 23:11:42 > Error Validating credentials');
        $this->assertTrue($result);
    }

    public function testFirstSwitchFrame()
    {
        $result = FsrImporter\has_error_message('2019-08-12 01:27:17 > Received error callback from Skyloader with error: :first-switch-frame');
        $this->assertFalse($result);
    }

    public function testPasswordResetRequired()
    {
        $result = FsrImporter\has_error_message('2019-08-12 01:27:17 > Received error callback from Skyloader with error: :password-reset-required');
        $this->assertTrue($result);
    }

    public function testClickLocationLink()
    {
        $result = FsrImporter\has_error_message('2019-08-12 01:27:17 > Received error callback from Skyloader with error: :click-location-link');
        $this->assertTrue($result);
    }
}
