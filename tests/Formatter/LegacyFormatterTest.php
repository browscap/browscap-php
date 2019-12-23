<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Formatter;

use BrowscapPHP\Formatter\LegacyFormatter;

final class LegacyFormatterTest extends \PHPUnit\Framework\TestCase
{
    public function formatterOptionsProvider() : array
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
     * @dataProvider formatterOptionsProvider
     *
     * @param array     $options
     * @param \stdClass $expectedResult
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testSetGetData(array $options, \stdClass $expectedResult) : void
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
