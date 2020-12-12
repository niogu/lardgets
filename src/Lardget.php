<?php


namespace Niogu\Lardgets;


use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Class Lardget
 * @package App\Http\Lardgets
 * @property-read SweetAlert2Helpers sweetalert2
 * @property-read ToastrHelpers toastr
 */
abstract class Lardget implements Htmlable
{
    protected $htmlId;
    protected $expiresInSec = 3600;

    abstract public function render();

    public function toHtml()
    {
        return $this->compileHtml($this->render());
    }

    public function compileHtml($html)
    {
        if($html instanceof HtmlString) {
            $html = $html->toHtml();
        }

        if(!$this->htmlId) {
            $this->htmlId = 'id' . Str::random(8);
        }

        $params = $this->getParamsForCompiler();

        return LardgetHtmlCompiler::compile($html, $params);
    }

    public function blade($string, $bindings = [])
    {
        $php = \Blade::compileString($string);
        $__env = \View::shared('__env');

        ob_start();
        extract($bindings);
        try {
            eval('?>' . $php);
            $result = ob_get_contents();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        ob_end_clean();
        return new HtmlString($result);
    }

    public function renderAll()
    {
        return $this->responseReplace($this->render());
    }

    public function responseReplace($html)
    {
        $htmlWithMethods = $this->compileHtml($html);
        return "document.getElementById('{$this->htmlId}').innerHTML = " . json_encode($htmlWithMethods) . ';';
    }

    /** @noinspection MagicMethodsValidityInspection */
    public function __get($name)
    {
        if($name === 'sweetalert2') {
            return new SweetAlert2Helpers($this);
        }
        if($name === 'toastr') {
            return new ToastrHelpers($this);
        }

        throw new \RuntimeException("Property $name is not defined");
    }

    /**
     * @return array
     */
    public function getParamsForCompiler(): array
    {
        return [
            'id' => $this->htmlId,
            'class' => get_class($this),
            'state' => $this->getState(),
            'expires_at' => microtime(true) + $this->expiresInSec,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getState(): \Illuminate\Support\Collection
    {
        return collect(get_object_vars($this))
            ->except(['htmlId', 'expiresInSec'])
            ->map(function($i) {
                if(EloquentProxy::canSerialize($i)) {
                    return EloquentProxy::from($i);
                }
                return $i;
            });
    }

    protected function view(string $view, array $data)
    {
        $data['w'] = $this;
        return new HtmlString(view($view, $data)->render());
    }

}
