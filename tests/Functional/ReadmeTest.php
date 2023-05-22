<?php

namespace Costinmrr\Parser\Tests\Functional;

use Costinmrr\Parser\ContentFormat;
use Costinmrr\Parser\ParserFactory;
use Costinmrr\Parser\ReturnType;
use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase
{
    public function testReadmeExampleJson(): void
    {
        $mapping = [
            "items" => "$.foo[*].bar",
            "prices" => "$.foo[*].price",
            "date" => "$.date",
        ];

        $content = '{
          "foo": [
            {
              "bar": "item1",
              "price": 10
            },
            {
              "bar": "item2",
              "price": 20
            },
            {
              "bar": "item3",
              "price": 30
            }
          ],
          "date": "2020-01-01"
        }';

        $parser = (new ParserFactory())->create($mapping, $content, ContentFormat::JSON);
        $resultDataset = $parser->parse();
        $resultIndividualValues = $parser->parse(ReturnType::INDIVIDUAL_VALUES);

        $this->assertEquals(
            [
                "items" => ["item1", "item2", "item3"],
                "prices" => [10, 20, 30],
                "date" => ["2020-01-01", "2020-01-01", "2020-01-01"],
            ],
            $resultDataset,
        );

        $this->assertEquals(
            [
                "items" => ["item1", "item2", "item3"],
                "prices" => [10, 20, 30],
                "date" => "2020-01-01",
            ],
            $resultIndividualValues,
        );
    }

    public function testReadmeExampleXml(): void
    {
        $mapping = [
            "items" => "$.product.prices.foo[*].@bar",
            "prices" => "$.product.prices.foo[*].&text",
            "date" => "$.product.date.&text",
        ];

        $content = '<?xml version="1.0" encoding="UTF-8"?>
        <product>
          <prices>
            <foo bar="item1">10</foo>
            <foo bar="item2">20</foo>
            <foo bar="item3">30</foo>
          </prices>
          <date>2020-01-01</date>
        </product>';

        $parser = (new ParserFactory())->create($mapping, $content, ContentFormat::XML);
        $resultDataset = $parser->parse();
        $resultIndividualValues = $parser->parse(ReturnType::INDIVIDUAL_VALUES);

        $this->assertEquals(
            [
                "items" => ["item1", "item2", "item3"],
                "prices" => [10, 20, 30],
                "date" => ["2020-01-01", "2020-01-01", "2020-01-01"],
            ],
            $resultDataset,
        );

        $this->assertEquals(
            [
                "items" => ["item1", "item2", "item3"],
                "prices" => [10, 20, 30],
                "date" => "2020-01-01",
            ],
            $resultIndividualValues,
        );
    }

    public function testReadmeExampleCsvNoHeader(): void
    {
        $mappings = [
            "items" => "$[0]",
            "prices" => "$[1]",
            "date" => "$[2]",
        ];

        $content = 'item1,10,2020-01-01
item2,20,2020-01-01
item3,30,2020-01-01';

        $parser = (new ParserFactory())->create($mappings, $content, ContentFormat::CSV);
        $resultDataset = $parser->parse();

        $this->assertEquals(
            [
                "items" => ["item1", "item2", "item3"],
                "prices" => [10, 20, 30],
                "date" => ["2020-01-01", "2020-01-01", "2020-01-01"],
            ],
            $resultDataset,
        );
    }

    public function testReadmeExampleCsvWithHeader(): void
    {
        $mappings = [
            "items" => "$[item]",
            "prices" => "$[price]",
            "date" => "$[date]",
        ];

        $content = 'item,price,date
item1,10,2020-01-01
item2,20,2020-01-01
item3,30,2020-01-01';

        $parser = (new ParserFactory())->create($mappings, $content, ContentFormat::CSV);
        $resultDataset = $parser->parse();

        $this->assertEquals(
            [
                "items" => ["item1", "item2", "item3"],
                "prices" => [10, 20, 30],
                "date" => ["2020-01-01", "2020-01-01", "2020-01-01"],
            ],
            $resultDataset,
        );
    }
}