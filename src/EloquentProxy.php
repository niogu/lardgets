<?php


namespace Niogu\Lardgets;


use Illuminate\Database\Eloquent\Model;

class EloquentProxy
{
    private $cls;
    private $id;

    public function __construct($cls, $id)
    {
        $this->cls = $cls;
        $this->id = $id;
    }

    public static function canSerialize($i)
    {
        return $i instanceof Model && $i->id;
    }

    public static function from($i)
    {
        return new EloquentProxy(get_class($i), $i->id);
    }

    public function rebuildOrFail()
    {
        $cls = $this->cls;
        return $cls::findOrFail($this->id);
    }
}
