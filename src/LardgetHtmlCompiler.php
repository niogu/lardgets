<?php


namespace Niogu\Lardgets;


use DOMDocument;
use Illuminate\Support\Facades\App;
use Peast\Formatter\Compact;
use Peast\Peast;
use Peast\Syntax\Node\ArrayExpression;
use Peast\Syntax\Node\BigIntLiteral;
use Peast\Syntax\Node\CallExpression;
use Peast\Syntax\Node\ExpressionStatement;
use Peast\Syntax\Node\NumericLiteral;
use Peast\Syntax\Node\Program;
use Peast\Syntax\Node\StringLiteral;

class LardgetHtmlCompiler
{

    public static function compile($html, $params = [])
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $modified = false;

        $buttons = $dom->getElementsByTagName('button');
        /** @var \DOMElement $button */
        foreach ($buttons as $button) {
            $onClick = $button->getAttribute('onclick');
            $onClick2 = self::compileCall($onClick, $params);
            if($onClick2 !== $onClick) {
                $button->setAttribute('onclick', $onClick2);
                $modified = true;
            }
        }

        if($modified) {
            $dom->documentElement->setAttribute('id', data_get($params, 'id'));
        }

        return $dom->saveHTML($dom->documentElement);
    }

    public static function compileCall(string $onClick, array $params, $nextCallParams = [])
    {
        $found = false;
        /** @var Program $ast */
        $ast = Peast::latest("function event() { $onClick }", [])->parse();

        $traverser = new \Peast\Traverser;
        $traverser->addFunction(function ($elt) use ($ast, $params, $nextCallParams, &$found) {
            if(!($elt instanceof CallExpression)) {
                return;
            }

            try {
                $callee = $elt->getCallee();
                $calleeName = $callee->getObject()->getName();
            } catch (\Error $e) {
                return;
            }

            if($calleeName !== '$w') {
                return;
            }

            /** @var \Peast\Syntax\Node\MemberExpression $callee */
            $methodName = $callee->getProperty()->getName();

            $placeHolderExprs = [];

            $params2 = array_merge(['method' => $methodName], $params);
            if(count($elt->getArguments()) > 0) {
                $params2['call_params'] = $nextCallParams;
                foreach($elt->getArguments() as $pos => $arg) {
                    if($arg instanceof StringLiteral || $arg instanceof NumericLiteral) {
                        $params2['call_params'] []= $arg->getValue();
                        continue;
                    }
                    $params2['call_params'] []= '_';
                    $params2['placeholder_params'] = $params2['placeholder_params'] ?? [];
                    $params2['placeholder_params'] []= $pos + count($nextCallParams);
                    $placeHolderExprs []= $arg;
                }
            }

            $widgetRunParam = json_encode(self::encrypt($params2), JSON_THROW_ON_ERROR, 512);
            $repl = Peast::latest("widgetRunner.run($widgetRunParam)", [])->parse()->getBody()[0];
            if($placeHolderExprs) {
                $args = $repl->getExpression()->getArguments();
                $expr = new ArrayExpression();
                $expr->setElements($placeHolderExprs);
                $args []= $expr;
                $repl->getExpression()->setArguments($args);
            }
            $found = true;
            return $repl->getExpression();
        });
        $traverser->traverse($ast);

        if(!$found) {
            return $onClick;
        }

        $body = $ast->getBody()[0]->getBody()->getBody();
        $ast->setBody($body);
        return $ast->render(new Compact());
    }

    public static function encrypt(array $params)
    {
        if(App::runningUnitTests()) {
            return serialize($params);
        }
        return encrypt($params);
    }

    public static function decrypt($string)
    {
        if(App::runningUnitTests()) {
            /** @noinspection UnserializeExploitsInspection This is only for unit tests */
            return unserialize($string);
        }
        return decrypt($string);
    }
}
