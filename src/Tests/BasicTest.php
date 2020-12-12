<?php

namespace Niogu\Lardgets\Tests;

use Niogu\Lardgets\LardgetController;
use Niogu\Lardgets\Lardget;
use Niogu\Lardgets\LardgetHtmlCompiler;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Counter;
use Tests\TestCase;

class BasicTest extends TestCase
{
    use InteractsWithExceptionHandling;

    /** @test */
    public function dont_touch_raw_html()
    {
        $html = '<div></div>';
        $this->assertEquals($html, LardgetHtmlCompiler::compile($html, ['id' => 'id12345']));
    }

    /** @test */
    public function dont_touch_raw_html_utf8_check()
    {
        $html = '<div>Ümlaut 你好 გამარჯობა</div>';
        $this->assertEquals($html, LardgetHtmlCompiler::compile($html, ['id' => 'id12345']));
    }

    /** @test */
    public function dont_touch_raw_html_1()
    {
        $html = '<div><button onclick="return false;"></button></div>';
        $this->assertEquals($html, LardgetHtmlCompiler::compile($html, ['id' => 'id12345']));
    }

    /** @test */
    public function dont_touch_raw_html_2()
    {
        $html = '<div><button onclick="alert(1); return false;"></button></div>';
        $this->assertEquals($html, LardgetHtmlCompiler::compile($html, ['id' => 'id12345']));
    }

    /** @test */
    public function convert_method_names()
    {
        $html = '<div><button onclick="$w.test()">w</button></div>';

        $params = ['method' => 'test', 'id' => 'id12345'];
        $widgetRunParam = json_encode(LardgetHtmlCompiler::encrypt($params), JSON_THROW_ON_ERROR, 512);
        $expected = '<div id="id12345"><button onclick=\'widgetRunner.run(' . $widgetRunParam . ');\'>w</button></div>';

        $this->assertEquals($expected, LardgetHtmlCompiler::compile($html, ['id' => 'id12345']));
    }

    /** @test */
    public function convert_params_with_placeholders()
    {
        $html = '<div><button onclick="$w.test(\'1\', document.querySelector(\'#x\').value, 1, document.querySelector(\'#y\').value)">w</button></div>';

        $params = [
            'method' => 'test',
            'id' => 'id12345',
            'call_params' => ['1', '_', 1, '_'],
            'placeholder_params' => [1, 3],
        ];
        $widgetRunFirstParam = json_encode(LardgetHtmlCompiler::encrypt($params), JSON_THROW_ON_ERROR, 512);

        $expected = sprintf(
            "<div id=\"id12345\"><button onclick=\"widgetRunner.run(%s,[document.querySelector('#x').value,document.querySelector('#y').value]);\">w</button></div>",
            e($widgetRunFirstParam));

        $this->assertEquals($expected, LardgetHtmlCompiler::compile($html, ['id' => 'id12345']));
    }

    /** @test */
    public function basic_counter()
    {
        $l = new Counter();
        $html = $l->toHtml();
        $this->assertStringContainsString('count:0', $html);
        $d = new \DOMDocument();
        $d->loadHTML($html);
        $onclick = $d->getElementsByTagName('button')[0]->getAttribute('onclick');
        $onclick = preg_replace('#^[^"]*?"#', '', $onclick);
        $onclick = preg_replace('#"[^"]*?$#', '', $onclick);
        $onclick = preg_replace('#\"#', '"', $onclick);
        $params = unserialize(json_decode("\"$onclick\""));

        $this->withoutExceptionHandling();
        $content = $this->postJson('/__widget2', ['data' => LardgetHtmlCompiler::encrypt($params)])->assertOk()->content();
        $this->assertStringContainsString('count:1', $content);
    }

    /** @test */
    public function expiresIn()
    {
        $this->markTestIncomplete('TODO: Implement test expiresIn');
    }

    /** @test */
    public function state_must_be_somehow_anchored_in_the_widget_to_avoid_replay_attack()
    {
        $this->markTestIncomplete('TODO: Implement test state_must_be_somehow_anchored_in_the_widget_to_avoid_replay_attack');
    }
}
