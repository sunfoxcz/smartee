<?php declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

// configure environment
Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

// create temporary directory
define('TEMP_DIR', __DIR__ . '/tmp/' . lcg_value());
Tester\Helpers::purge(TEMP_DIR);

abstract class TemplateTestCase extends Tester\TestCase
{
    /**
     * @var Smarty
     */
    protected $smarty;

    /**
     * @var Sunfox\Smartee\SmartyEngine
     */
    protected $latte;

    protected function setUp()
    {
        $this->smarty = new Smarty;
        $this->smarty->setTemplateDir(__DIR__ . '/templates');
        $this->smarty->setCompileDir(__DIR__ . '/tmp');

        $this->latte = new Sunfox\Smartee\SmartyEngine;
        $this->latte->setTempDirectory(__DIR__ . '/tmp');
    }

    /**
     * @param string $template
     * @param array  $parameters
     * @return string
     */
    protected function renderSmarty($template, array $parameters)
    {
        $this->smarty->assign($parameters);
        return $this->smarty->fetch($template . '.tpl');
    }

    /**
     * @param string $template
     * @param array  $parameters
     * @return string
     */
    protected function renderLatte($template, array $parameters)
    {
        return $this->latte->renderToString(__DIR__ . '/templates/' . $template . '.tpl', $parameters);
    }
}
