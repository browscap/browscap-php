<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Quoter;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/** @covers \BrowscapPHP\Helper\Quoter */
final class QuoterTest extends TestCase
{
    private Quoter $quoter;

    /** @throws void */
    protected function setUp(): void
    {
        $this->quoter = new Quoter();
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testPregQuote(): void
    {
        $expected = 'Mozilla\/.\.0 \(compatible; Ask Jeeves\/Teoma.*\)';

        self::assertSame($expected, $this->quoter->pregQuote('Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)'));
    }
}
