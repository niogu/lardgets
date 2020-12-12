<?php

namespace Niogu\Lardgets;

use App\Http\Controllers\Controller;
use Niogu\Lardgets\EloquentProxy;
use Niogu\Lardgets\Lardget;
use Niogu\Lardgets\LardgetHtmlCompiler;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LardgetController extends Controller
{
    public function run()
    {
        try {
            try {
                $data = LardgetHtmlCompiler::decrypt(request()->json('data'));
            } catch (\Exception $e) {
                return response()->json(['visible_message' => 'Security problem: cannot decrypt the payload'], 403);
            }

            if(!class_exists($data['class'])) {
                return response()->json(['visible_message' => 'Security problem: class does not exist'], 403);
            }
            $cls = new \ReflectionClass($data['class']);

            if(microtime(true) > $data['expires_at']) {
                return response()->json(['visible_message' => 'The link has expired, please reload the page'], 403);
            }

            $widget = $cls->newInstanceWithoutConstructor();
            if (!($widget instanceof Lardget)) {
                return response()->json(['visible_message' => 'Security problem'], 403);
            }

            foreach ($data['state'] as $k => $v) {
                if($v instanceof EloquentProxy) {
                    $v = $v->rebuildOrFail();
                }
                $property = $cls->getProperty($k);
                if ($property->isProtected()) {
                    $property->setAccessible(true);
                    $property->setValue($widget, $v);
                } else {
                    $widget->{$k} = $v;
                }
            }

            $property = $cls->getProperty('htmlId');
            $property->setAccessible(true);
            $property->setValue($widget, $data['id']);

            $paramsArr = data_get($data, 'call_params', []);
            $methodName = $data['method'];
            if(isset($data['placeholder_params']) && \request()->json('placeholders')) {
                $placeholders = collect(\request()->json('placeholders'));
                foreach($data['placeholder_params'] as $pos) {
                    $paramsArr[$pos] = $placeholders->shift();
                }
            }
            $result = $widget->{$methodName}(...$paramsArr);

            return response($result, 200, ['Content-type', 'text/javascript']);
        } catch (\Throwable $e) {
            report($e);
            return $this->returnException($e);
        }

    }

    public function lardgetRoute($cls)
    {
        return view('lardgets::single', ['lardget' => new $cls()]);
    }

    /**
     * @param \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnException(\Throwable $e): \Illuminate\Http\JsonResponse
    {
        if (config('app.debug')) {
            $trace = collect($e->getTrace())
                ->prepend(['file' => $e->getFile(), 'line' => $e->getLine()])
                ->map(function ($t) {
                    if (isset($t['file'])) {
                        $t['file'] = preg_replace('#^' . base_path() . '/#', '', $t['file']);
                    }
                    return $t;
                })
                ->filter(function ($i) {
                    return !Str::startsWith(data_get($i, 'file'), 'vendor/laravel/framework') &&
                        !Str::endsWith(data_get($i, 'file'), 'LardgetController.php') &&
                        !Str::endsWith(data_get($i, 'file'), 'proxy/src/TrustProxies.php') &&
                        data_get($i, 'file') !== 'public/index.php' &&
                        !Str::endsWith(data_get($i, 'class'), '\\LardgetController') &&
                        data_get($i, 'file') !== 'server.php';
                })->values();
            return response()->json(['exception' => get_class($e), 'message' => $e->getMessage(), 'trace' => $trace], 500);
        }

        return response()->json((object)[], 500);
    }
}
