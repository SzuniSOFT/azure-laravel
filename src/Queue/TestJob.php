<?php


namespace SzuniSoft\Azure\Laravel\Queue;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TestJob implements ShouldQueue {

    use Dispatchable, Queueable;

    public function handle()
    {
        echo "lel";
    }

}