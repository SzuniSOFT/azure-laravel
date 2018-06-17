<?php


namespace SzuniSoft\Azure\Laravel\Test;


use Mockery;

class TestCase extends \PHPUnit\Framework\TestCase {

    /**
     * @var \Mockery\MockInterface
     */
    protected $app;

    protected function setUp()
    {
        parent::setUp();
        $this->app = Mockery::mock(\Illuminate\Container\Container::class);
    }


    protected function tearDown()
    {
        parent::tearDown();

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
    }


}