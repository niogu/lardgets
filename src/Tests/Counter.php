<?php


namespace Niogu\Lardgets\Tests;


use Niogu\Lardgets\Lardget;

class Counter extends Lardget
{

    protected $count = 0;

    public function render()
    {
        return $this->blade('<div>count:{{ $this->count }} <button onclick="$w.increment(1)"></button></div>');
    }

    public function increment()
    {
        $this->count++;
        return $this->renderAll();
    }
}
