<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class VarTestCase extends TemplateTestCase
{
    public function testVar()
    {
        $parameters = [
            'array' => [
                'one' => [
                    'two' => '2',
                ],
            ],
        ];

        $smartyOutput = $this->renderSmarty('var', $parameters);
        $latteOutput = $this->renderLatte('var', $parameters);

        Assert::same(trim($smartyOutput), trim($latteOutput));
    }
}

(new VarTestCase)->run();
