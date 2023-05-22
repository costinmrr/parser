<?php

namespace Costinmrr\Parser\Tests\Unit\Json;

use Costinmrr\Parser\Json\Parser;
use Costinmrr\Parser\ReturnType;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     *
     * @param array<string, string> $mapping
     * @param array<string, null|int|string> $expectedDataset
     * @param array<string, null|int|string|array<int, null|int|string>> $expectedIndividualValues
     */
    public function testParse(
        array $mapping,
        string $json,
        array $expectedDataset,
        array $expectedIndividualValues,
    ): void {
        $parser = new Parser($mapping, $json);
        $parsedDataset = $parser->parse(ReturnType::DATASET);
        $parsedIndividualValues = $parser->parse(ReturnType::INDIVIDUAL_VALUES);

        $this->assertEquals($expectedDataset, $parsedDataset);
        $this->assertEquals($expectedIndividualValues, $parsedIndividualValues);
    }

    /**
     * @return array<int, array<int, array<string, string|array<null|int|string>>|string>>
     */
    public static function dataProvider(): array
    {
        return [
            // Data set 1
            [
                [
                    'name' => '$.product.name',
                    'price' => '$.prices[*].price',
                    'currency' => '$.prices[*].currencies[*]',
                    'date' => '$.info.dates[*].date',
                    'sales' => '$.info.dates[*].sales[*]',
                ],
                static::getJsonDataSet1(),
                [
                    // @phpcs:disable
                    'name' => ['chocolate','chocolate','chocolate','chocolate','chocolate','chocolate','chocolate','chocolate'],
                    'price' => [10, 10, 10, null, null, null, 14, 14],
                    'currency' => ['EUR', 'USD', 'AUD', 'EUR', 'USD', 'AUD', 'EUR', 'AUD'],
                    'date' => ['2000-01-01', '2000-01-01', '2000-01-01', '2010-01-01', '2010-01-01', '2010-01-01', '2011-01-01', '2011-01-01'],
                    'sales' => [10, 30, 30, 50, 20, 100, 45, 60],
                    // @phpcs:enable
                ],
                [
                    'name' => 'chocolate',
                    'price' => [10, null, 14],
                    'currency' => ['EUR', 'USD', 'AUD', 'EUR', 'USD', 'AUD', 'EUR', 'AUD'],
                    'date' => ['2000-01-01', '2010-01-01', '2011-01-01'],
                    'sales' => [10, 30, 30, 50, 20, 100, 45, 60],
                ],
            ],
        ];
    }

    protected static function getJsonDataSet1(): string
    {
        return '
{
  "product": {
    "name": "chocolate"
  },
  "prices": [
    { "price": 10, "currencies": ["EUR", "USD", "AUD"] },
    {"currencies": ["EUR", "USD", "AUD"]},
    { "price": 14, "currencies": ["EUR", "AUD"] }
  ],
  "info": {
      "dates": [
        { "date": "2000-01-01", "sales": [10, 30, 30]},
        { "date": "2010-01-01", "sales": [50, 20, 100]},
        { "date": "2011-01-01", "sales": [45, 60]}
      ]
  }
}';
    }

    public function testPainJson(): void
    {
        $json = file_get_contents(__DIR__ . '/pain.json');
        if ($json === false) {
            $this->fail('Could not read file pain.json');
        }
        $mapping = [
            'billing_account_id' => '$.rows[*].f[0].v',
            'service_id' => '$.rows[*].f[1].v',
            'service_description' => '$.rows[*].f[2].v',
            'sku_id' => '$.rows[*].f[3].v',
            'sku_description' => '$.rows[*].f[4].v',
            'labels_key' => '$.rows[*].f[5].v',
            'labels_value' => '$.rows[*].f[6].v',
            'system_key' => '$.rows[*].f[7].v',
            'system_value' => '$.rows[*].f[8].v',
            'location' => '$.rows[*].f[9].v',
            'country' => '$.rows[*].f[10].v',
            'zone' => '$.rows[*].f[11].v',
            'cost' => '$.rows[*].f[12].v',
            'cost_type' => '$.rows[*].f[13].v',
            'currency' => '$.rows[*].f[14].v',
            'currency_conversion_rate' => '$.rows[*].f[15].v',
            'quantity' => '$.rows[*].f[16].v',
            'units' => '$.rows[*].f[17].v',
            'credits' => '$.rows[*].f[18].v',
            'credits_amount' => '$.rows[*].f[19].v',
            'project_id' => '$.rows[*].f[20].v',
            'project_name' => '$.rows[*].f[21].v',
            'invoice' => '$.rows[*].f[22].v',
            'project_label_key' => '$.rows[*].f[23].v',
            'project_label_value' => '$.rows[*].f[24].v',
        ];
        $parser = new Parser($mapping, $json);
        $parsedData = $parser->parse();
        foreach ($parsedData as $values) {
            if (!is_array($values)) {
                $this->fail('Parsed data is not an array');
            }
            $this->assertCount(1238, $values);
        }
    }
}
