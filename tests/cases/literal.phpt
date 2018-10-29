<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

final class LiteralTestCase extends TemplateTestCase
{
    public function testLiteral()
    {
        $parameters = [];

        $smartyOutput = $this->renderSmarty('literal', $parameters);
        $latteOutput = $this->renderLatte('literal', $parameters);

        Assert::same(str_replace("\n\n", "\n", trim($smartyOutput)), trim($latteOutput));
    }
}

(new LiteralTestCase)->run();
