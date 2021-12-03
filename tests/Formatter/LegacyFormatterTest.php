<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Formatter;

use BrowscapPHP\Formatter\LegacyFormatter;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \BrowscapPHP\Formatter\LegacyFormatter
 */
final class LegacyFormatterTest extends TestCase
{
    /**
     * @return array[][]|stdClass[][]
     * @phpstan-return array<array{0: array{lowercase?: bool}, 1: stdClass}>
     */
    public function formatterOptionsProvider(): array
    {
        return [
            [
                [],
                (object) [
                    'Browser' => 'test',
                    'Comment' => 'TestComment',
                ],
            ],
            [
                ['lowercase' => true],
                (object) [
                    'browser' => 'test',
                    'comment' => 'TestComment',
                ],
            ],
        ];
    }

    /**
     * @param bool[] $options
     * @phpstan-param array{lowercase?: bool} $options
     *
     * @dataProvider formatterOptionsProvider
     */
    public function testSetGetData(array $options, stdClass $expectedResult): void
    {
        $data = [
            'Browser' => 'test',
            'Comment' => 'TestComment',
        ];

        $formatter = new LegacyFormatter($options);
        $formatter->setData($data);
        $return = $formatter->getData();
        self::assertEquals($expectedResult, $return);
    }
}
