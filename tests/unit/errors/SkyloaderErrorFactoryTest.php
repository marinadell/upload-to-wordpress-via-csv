<?php



class SkyloaderErrorFactoryTest extends \Codeception\Test\Unit
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
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory();
    }


    public function testReadingAndFindingError()
    {
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory();
        $factory->readYamlFile();
        $result = $factory->createError(':invalid-password');

        $this->assertEquals('Invalid Credentials', $result->getErrorTitle());
        $this->assertEquals(':invalid-password', $result->getSkyloaderErrorCode());
    }


    public function testReadingAndNotFindingError()
    {
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory();
        $factory->readYamlFile();
        $result = $factory->createError('booya');

        $this->assertNull($result);
    }


    public function testIsReportableErrorFalse()
    {
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory();
        $factory->readYamlFile();
        $result = $factory->isReportableError('booya');

        $this->assertFalse($result);
    }


    public function testIsReportableErrorFalseForNyll()
    {
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory();
        $factory->readYamlFile();
        $result = $factory->isReportableError();

        $this->assertFalse($result);
    }


    public function testIsReportableErrorTrue()
    {
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory();
        $factory->readYamlFile();
        $result = $factory->isReportableError(':account-locked');

        $this->assertTrue($result);
    }
}