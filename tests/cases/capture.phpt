<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class CaptureTestCase extends TemplateTestCase
{
    public function testDottedVarsExpanding()
    {
        $parameters = [
            'aaa' => 'aaa',
            'bbb' => 'bbb',
            'ccc' => 'ccc',
        ];

        $smartyOutput = $this->renderSmarty('capture', $parameters);
        $latteOutput = $this->renderLatte('capture', $parameters);

        Assert::same(trim($smartyOutput), trim($latteOutput));
    }
}

(new CaptureTestCase)->run();
