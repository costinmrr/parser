<?php

namespace Costinmrr\Parser\Tests\Unit;

use Costinmrr\Parser\ContentFormat;
use Costinmrr\Parser\ParserFactory;
use Costinmrr\Parser\ParserInterface;
use Costinmrr\Parser\ReturnType;
use PHPUnit\Framework\TestCase;

class ParserFactoryTest extends TestCase
{
    /**
     * @testWith ["json"]
     *           ["xml"]
     *           ["csv"]
     */
    public function testCreateWorks(string $format): void
    {
        $contentFormat = ContentFormat::from($format);
        $parser = (new ParserFactory())->create([], "", $contentFormat);

        $this->assertInstanceOf(ParserInterface::class, $parser);
    }
}
