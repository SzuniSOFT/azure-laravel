<?php


namespace SzuniSoft\Azure\Laravel\Test;


use Mockery;

class TestCase extends \Orchestra\Testbench\TestCase {

    protected function tearDown()
    {
        parent::tearDown();

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
    }


}