<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Formatter;

use BrowscapPHP\Formatter\PhpGetBrowser;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \BrowscapPHP\Formatter\PhpGetBrowser
 */
final class PhpGetBrowserTest extends TestCase
{
    private PhpGetBrowser $object;

    protected function setUp(): void
    {
        $this->object = new PhpGetBrowser();
    }

    public function testSetGetData(): void
    {
        $data = [
            'Browser' => 'test',
            'Comment' => 'TestComment',
        ];

        $this->object->setData($data);
        $return = $this->object->getData();
        self::assertInstanceOf(stdClass::class, $return);
        self::assertSame('test', $return->browser);
        self::assertSame('TestComment', $return->comment);
        self::assertObjectHasAttribute('browser_type', $return);
    }

    public function testPatternIdIsReturned(): void
    {
        $data = [
            'Browser' => 'test',
            'PatternId' => 'test.json::u0::c1',
        ];

        $this->object->setData($data);
        $return = $this->object->getData();

        self::assertObjectHasAttribute('patternid', $return);
        self::assertSame('test.json::u0::c1', $return->patternid);
    }

    public function testPatternIdIsNotReturned(): void
    {
        $data = ['Browser' => 'test'];

        $this->object->setData($data);
        $return = $this->object->getData();

        self::assertObjectNotHasAttribute('patternid', $return);
    }
}
