<?php

namespace Sunfox\Smartee\Runtime;

use Latte;
use Latte\Runtime\FilterExecutor;
use Latte\Runtime\Filters;
use Latte\Runtime\SnippetDriver;
use Sunfox\Smartee\SmartyEngine as Engine;

class Template
{
    use Latte\Strict;

    /**
     * @var \stdClass global accumulators for intermediate results
     */
    public $global;

    /**
     * @var string
     * @internal
     */
    protected $contentType = Engine::CONTENT_HTML;

    /**
     * @var array
     * @internal
     */
    protected $params = [];

    /**
     * @var FilterExecutor
     */
    protected $filters;

    /**
     * @var array [name => method]
     * @internal
     */
    protected $blocks = [];

    /**
     * @var string|null|false
     * @internal
     */
    protected $parentName;

    /**
     * @var [name => [callbacks]]
     * @internal
     */
    protected $blockQueue = [];

    /**
     * @var [name => type]
     * @internal
     */
    protected $blockTypes = [];

    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Template|null
     * @internal
     */
    private $referringTemplate;

    /**
     * @var string|null
     * @internal
     */
    private $referenceType;

    public function __construct(Engine $engine, array $params, FilterExecutor $filters, array $providers, $name)
    {
        $this->engine = $engine;
        $this->params = $params;
        $this->filters = $filters;
        $this->name = $name;
        $this->global = (object) $providers;
        foreach ($this->blocks as $nm => $method) {
            $this->blockQueue[$nm][] = [$this, $method];
        }
        $this->params['template'] = $this; // back compatibility
    }

    /**
     * @return Engine
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns array of all parameters.
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Returns parameter.
     * @return mixed
     */
    public function getParameter($name)
    {
        if (!array_key_exists($name, $this->params)) {
            trigger_error("The variable '$name' does not exist in template.", E_USER_NOTICE);
        }
        return $this->params[$name];
    }

    /**
     * Adds parameter.
     * @param string $name
     * @param mixed $value
     */
    public function assign($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return string|null
     */
    public function getParentName()
    {
        return $this->parentName ?: null;
    }

    /**
     * @return Template|null
     */
    public function getReferringTemplate()
    {
        return $this->referringTemplate;
    }

    /**
     * @return string|null
     */
    public function getReferenceType()
    {
        return $this->referenceType;
    }

    /**
     * Renders template.
     * @return void
     * @internal
     */
    public function render()
    {
        $this->prepare();

        if ($this->parentName === null && isset($this->global->coreParentFinder)) {
            $this->parentName = call_user_func($this->global->coreParentFinder, $this);
        }
        if (isset($this->global->snippetBridge) && !isset($this->global->snippetDriver)) {
            $this->global->snippetDriver = new SnippetDriver($this->global->snippetBridge);
        }
        Filters::$xhtml = (bool) preg_match('#xml|xhtml#', $this->contentType);

        if ($this->referenceType === 'import') {
            if ($this->parentName) {
                $this->createTemplate($this->parentName, [], 'import')->render();
            }
            return;

        } elseif ($this->parentName) { // extends
            ob_start(function () {});
            $params = $this->main();
            ob_end_clean();
            $this->createTemplate($this->parentName, $params, 'extends')->render();
            return;

        }

        $this->params['smarty'] = [
            'get' => $_GET,
            'post' => $_POST,
            'cookies' => $_COOKIE,
            'server' => $_SERVER,
            'env' => $_ENV,
            'session' => isset($_SESSION) ? $_SESSION : [],
            'request' => $_REQUEST,

            'capture' => [],
            'foreach' => [],
        ];

        $this->main();
    }

    /**
     * Renders template to string.
     * @return string
     * @internal
     */
    public function renderToString()
    {
        return $this->capture([$this, 'render']);
    }

    /**
     * Renders template.
     * @return Template
     * @internal
     */
    protected function createTemplate($name, array $params, $referenceType)
    {
        $name = $this->engine->getLoader()->getReferredName($name, $this->name);
        $child = $this->engine->createTemplate($name, $params);
        $child->referringTemplate = $this;
        $child->referenceType = $referenceType;
        $child->global = $this->global;
        return $child;
    }

    /**
     * @return void
     * @internal
     */
    public function prepare()
    {
    }

    /********************* blocks ****************d*g**/

    /**
     * Renders block.
     * @param  string
     * @param  array
     * @param  string|\Closure content-type name or modifier closure
     * @return void
     * @internal
     */
    protected function renderBlock($name, array $params, $mod = null)
    {
        if (empty($this->blockQueue[$name])) {
            $hint = isset($this->blockQueue) && ($t = Latte\Helpers::getSuggestion(array_keys($this->blockQueue), $name)) ? ", did you mean '$t'?" : '.';
            throw new \RuntimeException("Cannot include undefined block '$name'$hint");
        }

        $block = reset($this->blockQueue[$name]);
        if ($mod && $mod !== ($blockType = $this->blockTypes[$name])) {
            if ($filter = (is_string($mod) ? Filters::getConvertor($blockType, $mod) : $mod)) {
                echo $filter($this->capture(function () use ($block, $params) { $block($params); }), $blockType);
                return;
            }
            trigger_error("Including block $name with content type " . strtoupper($blockType) . ' into incompatible type ' . strtoupper($mod) . '.', E_USER_WARNING);
        }
        $block($params);
    }

    /**
     * Renders parent block.
     * @return void
     * @internal
     */
    protected function renderBlockParent($name, array $params)
    {
        if (empty($this->blockQueue[$name]) || ($block = next($this->blockQueue[$name])) === false) {
            throw new \RuntimeException("Cannot include undefined parent block '$name'.");
        }
        $block($params);
        prev($this->blockQueue[$name]);
    }

    /**
     * @return void
     * @internal
     */
    protected function checkBlockContentType($current, $name)
    {
        $expected = &$this->blockTypes[$name];
        if ($expected === null) {
            $expected = $current;
        } elseif ($expected !== $current) {
            trigger_error("Overridden block $name with content type " . strtoupper($current) . ' by incompatible type ' . strtoupper($expected) . '.', E_USER_WARNING);
        }
    }

    /**
     * Captures output to string.
     * @return string
     * @internal
     */
    public function capture(callable $function)
    {
        ob_start(function () {});
        try {
            $this->global->coreCaptured = true;
            $function();
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }
        $this->global->coreCaptured = false;
        if (isset($e)) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }
}