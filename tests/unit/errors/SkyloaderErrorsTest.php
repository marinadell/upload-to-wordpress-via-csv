<?php



class SkyloaderErrorTest extends \Codeception\Test\Unit
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
        $library = new \FsrImporter\Errors\SkyloaderError([]);
    }

    public function testGetStatusTextWithDistName()
    {
        $library = new \FsrImporter\Errors\SkyloaderError([
            'skyloaderErrorCode' => ':invalid-password',
            'distributorName'    => 'Shamrock',
            'errorTitle'         => 'password inavlid',
            'statusText'         => 'Change password for {dist-name}',
        ]);

        $result = $library->getStatusText();
        $this->assertEquals('Change password for Shamrock', $result);
    }

    public function testGetStatusTextWithNullDistributor()
    {
        $library = new \FsrImporter\Errors\SkyloaderError([
            'skyloaderErrorCode' => ':invalid-password',
            'errorTitle'         => 'password inavlid',
            'distributorName'    => null,
            'statusText'         => 'Change password for {dist-name}',
        ]);

        $result = $library->getStatusText();
        $this->assertEquals('Change password for ', $result);
    }

    public function testGetStatusTextWithMissingDistributor()
    {
        $library = new \FsrImporter\Errors\SkyloaderError([
            'skyloaderErrorCode' => ':invalid-password',
            'errorTitle'         => 'password inavlid',
            'statusText'         => 'Change password for {dist-name}',
        ]);

        $result = $library->getStatusText();
        $this->assertEquals('Change password for ', $result);
    }
}