<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Quoter;

/**
 * @covers \BrowscapPHP\Helper\Quoter
 */
final class QuoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BrowscapPHP\Helper\Quoter
     */
    private $quoter;

    public function setUp() : void
    {
        $this->quoter = new Quoter();
    }

    public function testPregQuote() : void
    {
        $expected = 'Mozilla\/.\.0 \(compatible; Ask Jeeves\/Teoma.*\)';

        self::assertSame($expected, $this->quoter->pregQuote('Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)'));
    }
}
