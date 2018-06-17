<?php


namespace SzuniSoft\Azure\Laravel\Test\Storage;


class TestCase extends \SzuniSoft\Azure\Laravel\Test\TestCase {

    protected function setUp()
    {
        parent::setUp();
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