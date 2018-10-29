<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class IncludeTestCase extends TemplateTestCase
{
    public function testInclude()
    {
        $parameters = [
            'var' => 'var',
        ];

        $smartyOutput = $this->renderSmarty('include', $parameters);
        $latteOutput = $this->renderLatte('include', $parameters);

        Assert::same(trim($smartyOutput), trim($latteOutput));
    }
}

(new IncludeTestCase)->run();
