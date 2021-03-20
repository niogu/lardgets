<?php

namespace Niogu\Lardgets;

use Niogu\Lardgets\Lardget;
use Niogu\Lardgets\LardgetHtmlCompiler;

class SweetAlert2Helpers
{
    /**
     * @var Lardget
     */
    private $widget;

    public function __construct(Lardget $widget)
    {
        $this->widget = $widget;
    }

    public function swalInputText($text, $to, $params = [], $options = [])
    {
        $paramsForCompiler = $this->widget->getParamsForCompiler();
        $call = LardgetHtmlCompiler::compileCall("\$w.{$to}(value)", $paramsForCompiler, $params);

        $param = [
            'title' => $text,
            'input' => $options['input'] ?? 'text',
            'inputPlaceholder' => '',
            'inputValue' => $options['value'] ?? null,
            'html' => $options['html'] ?? null,
        ];

        return '
            window.widgetRunner.swalFire(' . json_encode($param) . ').then(function (o) {
                if(!o.dismiss) {
                    var value = o.value;
                    ' . $call . ';
                }
            });
        ';
    }

    public function confirm($text, $to, $params = [], $options = [])
    {
        $call = LardgetHtmlCompiler::compileCall("\$w.{$to}(value)", $this->widget->getParamsForCompiler());

        $param = [
            'title' => $text,
            'input' => $options['input'] ?? null,
            'inputPlaceholder' => '',
            'inputValue' => $options['value'] ?? null,
            'html' => $options['html'] ?? null,
            'showCancelButton' => true,
        ];

        return '
            window.widgetRunner.swalFire(' . json_encode($param) . ').then(function (o) {
                if(!o.dismiss) {
                    var value = o.value;
                    ' . $call . ';
                }
            });
        ';
    }

    public function swalHtml($html, $options = [], $showCancel = false)
    {

        $param = [
            'title' => data_get($options, 'title'),
            'html' => $html,
            'showCancelButton' => $showCancel,
        ];

        return '
            window.widgetRunner.swalFire(' . json_encode($param) . ');
        ';
    }

    public function swalText($text, $options = [], $showCancel = false)
    {
        $param = [
            'title' => data_get($options, 'title'),
            'html' => e($text),
            'showCancelButton' => $showCancel,
        ];

        return '
            window.widgetRunner.swalFire(' . json_encode($param) . ');
        ';
    }

}
