<?php

namespace Costinmrr\Parser;

use Costinmrr\Parser\Json\Parser as JsonParser;
use Costinmrr\Parser\Xml\Parser as XmlParser;
use Costinmrr\Parser\Csv\Parser as CsvParser;

class ParserFactory
{
    /**
     * @param array<string, string> $mapping
     */
    public function create(array $mapping, string $content, ContentFormat $format): ParserInterface
    {
        return match ($format) {
            ContentFormat::JSON => new JsonParser($mapping, $content),
            ContentFormat::XML => new XmlParser($mapping, $content),
            ContentFormat::CSV => new CsvParser($mapping, $content),
        };
    }
}
