<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class ForeachTestCase extends TemplateTestCase
{
    public function testForeach()
    {
        $parameters = [
            'firstArray' => [
                'aaa',
                'bbb',
            ],
            'secondArray' => [
                'ccc' => 'ddd',
                'eee' => 'fff',
            ],
            'thirdArray' => [
                'aaa' => [
                    'one' => 'one',
                    'two' => 'two',
                ],
            ],
            'emptyArray' => [],
        ];

        $smartyOutput = $this->renderSmarty('foreach', $parameters);
        $latteOutput = $this->renderLatte('foreach', $parameters);

        Assert::same(trim($smartyOutput), trim($latteOutput));
    }
}

(new ForeachTestCase)->run();
