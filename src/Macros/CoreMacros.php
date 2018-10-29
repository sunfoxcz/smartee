<?php

namespace Sunfox\Smartee\Macros;

use Latte;
use Latte\CompileException;
use Latte\Engine;
use Latte\MacroNode;
use Latte\PhpWriter;
use Sunfox\Smartee\Compiler\Compiler;
use Sunfox\Smartee\Helpers;

/**
 * Basic macros for Smarty.
 *
 * - {if ?} ... {elseif ?} ... {else} ... {/if}
 * - {for ?} ... {/for}
 * - {foreach ?} ... {/foreach}
 * - {$variable}
 * - {=expression} echo
 * - {php expression} evaluate PHP statement
 * - {attr ?} HTML element attributes
 * - {capture ?} ... {/capture} capture block to parameter
 * - {spaceless} ... {/spaceless} compress whitespaces
 * - {var var => value} set template parameter
 * - {default var => value} set default template parameter
 * - {dump $var}
 * - {debugbreak}
 * - {ldelim} {rdelim} to display { }
 */
class CoreMacros extends MacroSet
{
    /**
     * @var array
     */
    private $overwrittenVars;

    /**
     * @var array
     */
    private static $ifAdditionalQualifiers = [
        'eq' => '==',
        'ne' => '!=',
        'neq' => '!=',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'ge' => '>=',
        'lte' => '<=',
        'le' => '<=',
        'not' => '!',
        'mod' => '%',
        // 'is div by' => '$a % $b == 0',
        // 'is not div by' => '$a % $b != 0',
        // 'is even' => '$a % 2 == 0',
        // 'is not even' => '$a % 2 != 0',
        // 'is even by' => '($a / $b) % 2 == 0',
        // 'is not even by' => '($a / $b) % 2 != 0',
        // 'is odd' => '$a % 2 != 0',
        // 'is not odd' => '$a % 2 == 0',
        // 'is odd by' => '($a / $b) % 2 != 0',
        // 'is not odd by' => '($a / $b) % 2 == 0',
    ];

    public static function install(Compiler $compiler)
    {
        $me = new static($compiler);

        $me->addMacro('if', [$me, 'macroIf'], [$me, 'macroEndIf']);
        $me->addMacro('elseif', [$me, 'macroElseIf']);
        $me->addMacro('else', [$me, 'macroElse']);

        $me->addMacro('foreach', [$me, 'macroForeach'], [$me, 'macroEndForeach']);
        $me->addMacro('foreachelse', [$me, 'macroForeachElse']);
        $me->addMacro('for', 'for (%node.args) {', '}');
        $me->addMacro('while', [$me, 'macroWhile'], [$me, 'macroEndWhile']);

        $me->addMacro('var', [$me, 'macroVar']);
        $me->addMacro('default', [$me, 'macroVar']);
        $me->addMacro('dump', [$me, 'macroDump']);
        $me->addMacro('debugbreak', [$me, 'macroDebugbreak']);
        $me->addMacro('ldelim', '?>{<?php');
        $me->addMacro('rdelim', '?>}<?php');
        $me->addMacro('literal', '', [$me, 'macroLiteral']);

        $me->addMacro('=', [$me, 'macroExpr']);

        $me->addMacro('capture', [$me, 'macroCapture'], [$me, 'macroEndCapture']);
        $me->addMacro('spaceless', [$me, 'macroSpaceless'], [$me, 'macroSpaceless']);
        $me->addMacro('include', [$me, 'macroInclude']);
    }

    /**
     * Initializes before template parsing.
     * @return void
     */
    public function initialize()
    {
        $this->overwrittenVars = [];
    }

    /**
     * Finishes template parsing.
     * @return array|null [prolog, epilog]
     */
    public function finalize()
    {
        $code = '';
        foreach ($this->overwrittenVars as $var => $lines) {
            $s = var_export($var, true);
            $code .= 'if (isset($this->params[' . var_export($var, true)
                . "])) trigger_error('Variable $" . addcslashes($var, "'") . ' overwritten in foreach on line ' . implode(', ', $lines) . "'); ";
        }
        return [$code];
    }

    /********************* macros ****************d*g**/

    /**
     * {if ...}
     */
    public function macroIf(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }

        $args = str_replace(array_keys(self::$ifAdditionalQualifiers), array_values(self::$ifAdditionalQualifiers), $node->args);

        return $writer->write('if (' . $args . ') {');
    }

    /**
     * {elseif ...}
     */
    public function macroElseIf(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }

        $args = str_replace(array_keys(self::$ifAdditionalQualifiers), array_values(self::$ifAdditionalQualifiers), $node->args);

        return $writer->write('} elseif (' . $args . ') {');
    }

    /**
     * {else}
     */
    public function macroElse(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        } elseif ($node->args) {
            $hint = Helpers::startsWith($node->args, 'if') ? ', did you mean {elseif}?' : '';
            throw new CompileException('Arguments are not allowed in ' . $node->getNotation() . $hint);
        }
        $ifNode = $node->parentNode;
        return '} else {';
    }

    /**
     * {/if ...}
     */
    public function macroEndIf(MacroNode $node, PhpWriter $writer)
    {
        return '}';
    }

    /**
     * {include file="file" assign=variable [params]}
     */
    public function macroInclude(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }

        $node->replaced = false;

        $params = Helpers::parseMacroNodeArgs($node->args);
        if (!array_key_exists('file', $params)) {
            throw new CompileException('include attribute file is required in ' . $node->getNotation());
        }

        $destination = $params['file'];
        $assign = isset($params['assign']) ? $params['assign'] : null;
        unset($params['file'], $params['assign']);

        $tplParams = [];
        foreach ($params as $key => $value) {
            $tplParams[] = "'$key' => " . (Helpers::startsWith($value, '$') ? $value : "'$value'");
        }

        return $writer->write(
            "/* line {$node->startLine} */
            \$_it = \$this->createTemplate('{$destination}', [" . implode(',', $tplParams) . "] + \$this->params, 'include');" .
            ($assign ? "\$$assign = \$_it->renderToString()" : "\$_it->render()")
        );
    }

    /**
     * {capture $variable}
     */
    public function macroCapture(MacroNode $node, PhpWriter $writer)
    {
        $params = Helpers::parseMacroNodeArgs($node->args, ['name', 'assign']);

        $node->data->name = isset($params['name']) ? $params['name'] : 'default';
        $node->data->assign = isset($params['assign']) ? $params['assign'] : NULL;
        return 'ob_start(function () {})';
    }

    /**
     * {/capture}
     */
    public function macroEndCapture(MacroNode $node, PhpWriter $writer)
    {
        $body = in_array($node->context[0], [Engine::CONTENT_HTML, Engine::CONTENT_XHTML], true)
            ? 'ob_get_length() ? new LR\\Html(ob_get_clean()) : ob_get_clean()'
            : 'ob_get_clean()';
        return $writer->write(
            "\$_fi = new LR\\FilterInfo(%var); " . ($node->data->assign ? "\${$node->data->assign} = " : '')
                . " \$smarty['capture']['%raw'] = %modifyContent($body);",
            $node->context[0],
            $node->data->name
        );
    }

    /**
     * {spaceless} ... {/spaceless}
     */
    public function macroSpaceless(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers || $node->args) {
            throw new CompileException('Modifiers and arguments are not allowed in ' . $node->getNotation());
        }
        $node->openingCode = in_array($node->context[0], [Engine::CONTENT_HTML, Engine::CONTENT_XHTML], true)
            ? '<?php ob_start(function ($s, $phase) { static $strip = true; return LR\Filters::spacelessHtml($s, $phase, $strip); }, 4096); ?>'
            : "<?php ob_start('Latte\\Runtime\\Filters::spacelessText', 4096); ?>";
        $node->closingCode = '<?php ob_end_flush(); ?>';
    }

    /**
     * {while ...}
     */
    public function macroWhile(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }
        if ($node->data->do = ($node->args === '')) {
            return 'do {';
        }
        return $writer->write('while (%node.args) {');
    }

    /**
     * {/while ...}
     */
    public function macroEndWhile(MacroNode $node, PhpWriter $writer)
    {
        if ($node->data->do) {
            if ($node->args === '') {
                throw new CompileException('Missing condition in {while} macro.');
            }
            return $writer->write('} while (%node.args);');
        }
        return '}';
    }

    /**
     * {foreach ...}
     */
    public function macroForeach(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }

        $params = Helpers::parseMacroNodeArgs($node->args, ['from', 'item', 'key', 'name']);
        foreach ($params as $k => $var) {
            if (in_array($var, ['item', 'key'])) {
                $this->overwrittenVars["\${$var}"][] = $node->startLine;
            }
        }

        if (!array_key_exists('from', $params) || !array_key_exists('item', $params)) {
            throw new CompileException('foreach attributes from and item are required in ' . $node->getNotation());
        }

        $node->data->from = $params['from'];

        $from = Helpers::expandDottedVar($params['from']);
        $as = isset($params['key']) ? "\${$params['key']} => \${$params['item']}" : "\${$params['item']}";
        if (isset($params['name'])) {
            return "foreach (\$smarty['foreach']['{$params['name']}'] = new LSR\CachingIterator(" . $from . ") as {$as}) {";
        }

        return 'foreach (' . $from . ' as ' . $as . ') {';
    }

    /**
     * {foreachelse}
     */
    public function macroForeachElse(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }
        if ($node->args) {
            throw new CompileException('Arguments are not allowed in ' . $node->getNotation());
        }

        $foreachNode = $node->parentNode;
        if ($foreachNode && $foreachNode->name === 'foreach') {
            if (isset($foreachNode->data->foreachelse)) {
                throw new CompileException('Macro {foreach} supports only one {foreachelse}.');
            }
            $foreachNode->data->foreachelse = true;
        }
        return '} if (!count(' . $foreachNode->data->from . ')) {';
    }

    /**
     * {/foreach ...}
     */
    public function macroEndForeach(MacroNode $node, PhpWriter $writer)
    {
        return '}';
    }

    /**
     * {dump ...}
     */
    public function macroDump(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }
        $args = $writer->formatArgs();
        return $writer->write(
            'Tracy\Debugger::barDump(' . ($args ? "($args)" : 'get_defined_vars()') . ', %var);',
            $args ?: 'variables'
        );
    }

    /**
     * {debugbreak ...}
     */
    public function macroDebugbreak(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }
        if (function_exists($func = 'debugbreak') || function_exists($func = 'xdebug_break')) {
            return $writer->write($node->args == null ? "$func()" : "if (%node.args) $func();");
        }
    }

    /**
     * {var ...}
     * {default ...}
     */
    public function macroVar(MacroNode $node, PhpWriter $writer)
    {
        if ($node->modifiers) {
            throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
        }
        if ($node->args === '' && $node->parentNode && $node->parentNode->name === 'switch') {
            return '} else {';
        }

        $var = true;
        $tokens = $writer->preprocess();
        $res = new Latte\MacroTokens;
        while ($tokens->nextToken()) {
            if ($var && $tokens->isCurrent($tokens::T_SYMBOL, $tokens::T_VARIABLE)) {
                if ($node->name === 'default') {
                    $res->append("'" . ltrim($tokens->currentValue(), '$') . "'");
                } else {
                    $res->append('$' . ltrim($tokens->currentValue(), '$'));
                }
                $var = null;

            } elseif ($tokens->isCurrent('=', '=>') && $tokens->depth === 0) {
                $res->append($node->name === 'default' ? '=>' : '=');
                $var = false;

            } elseif ($tokens->isCurrent(',') && $tokens->depth === 0) {
                if ($var === null) {
                    $res->append($node->name === 'default' ? '=>NULL' : '=NULL');
                }
                $res->append($node->name === 'default' ? ',' : ';');
                $var = true;

            } elseif ($var === null && $node->name === 'default' && !$tokens->isCurrent($tokens::T_WHITESPACE)) {
                throw new CompileException("Unexpected '{$tokens->currentValue()}' in {default $node->args}");

            } else {
                $res->append($tokens->currentToken());
            }
        }
        if ($var === null) {
            $res->append($node->name === 'default' ? '=>NULL' : '=NULL');
        }
        $out = $writer->quotingPass($res)->joinAll();
        return $node->name === 'default' ? "extract([$out], EXTR_SKIP)" : "$out;";
    }

    /**
     * {literal} ... {/literal}
     */
    public function macroLiteral(MacroNode $node, PhpWriter $writer)
    {
        return '';
    }

    /**
     * {= ...}
     * {php ...}
     */
    public function macroExpr(MacroNode $node, PhpWriter $writer)
    {
        if ($node->name === '=') {
            $var = Helpers::expandDottedVar($node->args);
            return $writer->write("echo %modify({$var}) /* line $node->startLine */");
        }

        return $writer->write('%modify(%node.args);');
    }
}
