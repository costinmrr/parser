<?php

namespace Costinmrr\Parser\Tests\Unit\Xml;

use Costinmrr\Parser\Xml\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     *
     * @param array<string, string> $mapping
     * @param array<string, null|int|string> $expected
     */
    public function testParse(array $mapping, string $xml, array $expected): void
    {
        $parser = new Parser($mapping, $xml);
        $parsedData = $parser->parse();
        $this->assertEquals($expected, $parsedData);
    }

    /**
     * @return mixed[]
     */
    public static function dataProvider(): array
    {
        return [
            // Data set 1
            [
                [
                    'name' => '$.xml.product.name.&text',
                    'price' => '$.xml.prices.price[*].@value',
                    'currency' => '$.xml.prices.price[*].currency[*].&text',
                    'date' => '$.xml.info.dates.date[*].&text',
                    'sales' => '$.xml.info.dates.date[*].sale[*].&text',
                ],
                static::getXmlDataSet1(),
                [
                    // @phpcs:disable
                    'name' => ['chocolate','chocolate','chocolate','chocolate','chocolate','chocolate','chocolate','chocolate'],
                    'price' => [10, 10, 10, null, null, null, 14, 14],
                    'currency' => ['EUR', 'USD', 'AUD', 'EUR', 'USD', 'AUD', 'EUR', 'AUD'],
                    'date' => ['2000-01-01', '2000-01-01', '2000-01-01', '2010-01-01', '2010-01-01', '2010-01-01', '2011-01-01', '2011-01-01'],
                    'sales' => [10, 30, 30, 50, 20, 100, 45, 60],
                    // @phpcs:enable
                ],
            ],
        ];
    }

    public static function getXmlDataSet1(): string
    {
        return '
<xml>
    <product>
        <name>chocolate</name>
    </product>
    <prices>
        <price value="10">
            <currency>EUR</currency>
            <currency>USD</currency>
            <currency>AUD</currency>
        </price>
        <price>
            <currency>EUR</currency>
            <currency>USD</currency>
            <currency>AUD</currency>
        </price>
        <price value="14">
            <currency>EUR</currency>
            <currency>AUD</currency>
        </price>
    </prices>
    <info>
        <dates>
            <date>
                2000-01-01
                <sale>10</sale>
                <sale>30</sale>
                <sale>30</sale>
            </date>
            <date>
                2010-01-01
                <sale>50</sale>
                <sale>20</sale>
                <sale>100</sale>
            </date>
            <date>
                2011-01-01
                <sale>45</sale>
                <sale>60</sale>
            </date>
        </dates>
    </info>
</xml>
        ';
    }
}
