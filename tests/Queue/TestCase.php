<?php


namespace SzuniSoft\Azure\Laravel\Test\Queue;


use Mockery;

class TestCase extends \SzuniSoft\Azure\Laravel\Test\TestCase {

    /**
     * @var \Illuminate\Container\Container
     */
    protected $app;

    protected function setUp()
    {
        parent::setUp();

        $this->app = Mockery::mock(\Illuminate\Container\Container::class);
    }


}