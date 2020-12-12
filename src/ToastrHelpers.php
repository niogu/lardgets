<?php

namespace Niogu\Lardgets;

use Niogu\Lardgets\Lardget;

class ToastrHelpers
{
    /**
     * @var Lardget
     */
    private $widget;

    public function __construct(Lardget $widget)
    {
        $this->widget = $widget;
    }

    public function flashError($message)
    {
        return 'window.widgetRunner.toastrError('.json_encode($message).')';
    }

    public function flashSuccess($message)
    {
        return 'window.widgetRunner.toastrSuccess('.json_encode($message).')';
    }

}
