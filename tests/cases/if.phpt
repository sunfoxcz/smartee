<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class IfTestCase extends TemplateTestCase
{
    public function testIf()
    {
        $parameters = [
            'name' => 'Fred',
            'amount' => 2000,
            'volume' => 50,
            'array' => ['a', 'b'],
        ];

        $smartyOutput = $this->renderSmarty('if', $parameters);
        $latteOutput = $this->renderLatte('if', $parameters);

        Assert::same(trim($smartyOutput), trim($latteOutput));
    }
}

(new IfTestCase)->run();
