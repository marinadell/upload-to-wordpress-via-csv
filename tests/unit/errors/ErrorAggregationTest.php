<?php



class ErrorAggregationTest extends \Codeception\Test\Unit
{

    private $aggregator;

    protected function _before()
    {
        $this->aggregator = new \FsrImporter\Errors\ErrorAggregation();
        $this->aggregator->intialize();
    }


    protected function _after()
    {
        $this->aggregator = null;
    }

    public function testCreateSklyloaderError()
    {
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory();
        $factory->readYamlFile();

        $accountHash = [
            'DCN'             => '1234543',
            'distributorName' => 'Shamrock',
            'errorCode'       => ':invalid-password',
            'dcnPostId'       => 12,
        ];

        $error = $this->aggregator->createSklyloaderError($accountHash, $factory);

        $this->assertEquals('1234543', $error->getDCN());
        $this->assertEquals('Shamrock', $error->getDistributorName());
        $this->assertEquals(':invalid-password', $error->getSkyloaderErrorCode());
    }

    public function testCreateErrorClass()
    {
        $aggregator= $this->construct('\FsrImporter\Errors\ErrorAggregation',[], [
            'getDistAccountHash' => function () { return [
                'DCN'             => '1234',
                'distributorName' => 'Shamrock',
                'errorCode'       => ':invalid-password',
                'dcnPostId'       => 12,
            ]; },
        ]);

        $aggregator->intialize();

        $errors = $aggregator->createErrorClass([16134]);
        $this->assertEquals('1234', $errors[0]->getDCN());
        $this->assertEquals('Shamrock', $errors[0]->getDistributorName());
    }
}