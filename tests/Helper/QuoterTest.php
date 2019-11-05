<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Quoter;

final class QuoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \BrowscapPHP\Helper\Quoter
     */
    private $quoter;

    protected function setUp() : void
    {
        $this->quoter = new Quoter();
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testPregQuote() : void
    {
        $pattern = 'Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)';
        $expected = 'Mozilla\/.\.0 \(compatible; Ask Jeeves\/Teoma.*\)';

        static::assertSame($expected, $this->quoter->pregQuote($pattern));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function testPregUnQuoteNoChange() : void
    {
        $pattern = 'DefaultProperties';

        static::assertSame($pattern, $this->quoter->pregUnQuote($pattern));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function testPregUnQuoteChange() : void
    {
        $pattern = '\\x';
        $expected = '\x';

        static::assertSame($expected, $this->quoter->pregUnQuote($pattern));
    }
}
