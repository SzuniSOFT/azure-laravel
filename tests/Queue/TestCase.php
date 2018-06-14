<?php


namespace SzuniSoft\Azure\Laravel\Test\Queue;


class TestCase extends \SzuniSoft\Azure\Laravel\Test\TestCase {

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \SzuniSoft\Azure\Laravel\Providers\QueueServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [

        ];
    }


}