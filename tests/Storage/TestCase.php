<?php


namespace SzuniSoft\Azure\Laravel\Test\Storage;


class TestCase extends \SzuniSoft\Azure\Laravel\Test\TestCase {

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \SzuniSoft\Azure\Laravel\Providers\StorageServiceProvider::class
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

    /**
     * @param $string
     * @return bool|resource
     */
    protected function stringToStream($string)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $string);
        rewind($stream);

        return $stream;
    }


}