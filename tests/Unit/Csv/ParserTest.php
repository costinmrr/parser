<?php

namespace Costinmrr\Parser\Tests\Unit\Csv;

use Costinmrr\Parser\Csv\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @dataProvider providerSetCsv
     *
     * @param false|array<int, array<string[]>> $expectedCsv
     */
    public function testReadCsvLine(string $content, false|array $expectedCsv): void
    {
        $parser = new Parser([], $content);
        if ($expectedCsv === false) {
            $this->assertFalse($parser->readCsvLine());
        } else {
            foreach ($expectedCsv as $expectedLine) {
                $this->assertEquals($expectedLine, $parser->readCsvLine());
            }
        }
    }

    /**
     * @return array<array<int, false|string|array<string[]>>>
     */
    public static function providerSetCsv(): array
    {
        return [
            // Test with empty content.
            [
                "",
                false,
            ],
            // Test with 1 row.
            [
                '"a","b","c"',
                [
                    ["a", "b", "c"],
                ],
            ],
            // Test with 2 rows.
            [
                '"a","b","c"' . "\n" . '"d","e","f"',
                [
                    ["a", "b", "c"],
                    ["d", "e", "f"],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForSetHasHeaderRow
     *
     * @param array<string, string> $mapping
     */
    public function testSetHasHeaderRow(
        array $mapping,
        string $content,
        bool $expectedHasHeaderRow,
        bool $expectError = false,
    ): void {
        if ($expectError) {
            $this->expectException(\Exception::class);
        }
        $parser = new Parser($mapping, $content);
        $this->assertEquals($expectedHasHeaderRow, $parser->hasHeaderRow());
    }

    /**
     * @return array<array<array<string, string>|string|bool>>
     */
    public static function providerForSetHasHeaderRow(): array
    {
        return [
            // Test with empty mapping
            [
                [],
                '"a","b","c"',
                false,
            ],
            // Test with empty csv.
            [
                ["col1" => "$[0]"],
                "",
                false,
            ],
            // Test happy path.
            [
                ["col1" => "$[a]", "col2" => "$[b]"],
                '"a","b"' . "\n" . '"c","d"',
                true,
            ],
            // Test less columns in mapping than in csv
            [
                ["col1" => "$[a]", "col2" => "$[b]"],
                '"a","b","c"' . "\n" . '"d","e","f"',
                true,
            ],
            // Test more columns in mapping than in csv and paths not integers - expect error
            [
                ["col1" => "$[a]", "col2" => "$[b]", "col3" => "$[c]"],
                '"a","b"' . "\n" . '"c","d"',
                false,
                true, // Expect error
            ],
        ];
    }

    /**
     * @dataProvider providerForTestSetColumnIndexes
     *
     * @param array<string, string> $mapping
     * @param array<string, int> $expectedColumnIndexes
     */
    public function testSetColumnIndexes(array $mapping, string $content, array $expectedColumnIndexes, bool $expectedError = false): void
    {
        if ($expectedError) {
            $this->expectException(\Exception::class);
        }
        $parser = new Parser($mapping, $content);
        $this->assertEquals($expectedColumnIndexes, $parser->columnIndexes());
    }

    /**
     * @return array<array<array<string, string|int>|string>>
     */
    public static function providerForTestSetColumnIndexes(): array
    {
        return [
            // Test happy path with header row - start from 0
            [
                ["price" => "$[a]", "quantity" => "$[b]", "total" => "$[c]"],
                '"a","b","c"',
                ["price" => 0, "quantity" => 1, "total" => 2],
            ],
            // Test happy path with header row - start from middle
            [
                ["price" => "$[a]", "quantity" => "$[b]", "total" => "$[c]"],
                '"x","a","b","c","d"',
                ["price" => 1, "quantity" => 2, "total" => 3],
            ],
            // Test happy path without header row
            [
                ["price" => "$[0]", "quantity" => "$[2]", "total" => "$[4]"],
                '"a","b","c","d","e"',
                ["price" => 0, "quantity" => 2, "total" => 4],
            ],
            // Test no header row - but missing indexes - don't expect error
            [
                ["price" => "$[0]", "quantity" => "$[2]", "total" => "$[5]"],
                '"a","b","c","d","e"',
                ["price" => 0, "quantity" => 2, "total" => 5],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestParse
     *
     * @param array<string, string> $mapping
     * @param array<string, string[]> $expectedResult
     */
    public function testParse(array $mapping, string $content, array $expectedResult): void
    {
        $parser = new Parser($mapping, $content);
        $this->assertEquals($expectedResult, $parser->parse());
    }

    /**
     * @return array<array<array<string, string>|string|array<string[]>>>
     */
    public static function providerForTestParse(): array
    {
        return [
            // Test parse with no header row
            [
                ["price" => "$[0]", "quantity" => "$[2]", "total" => "$[4]"],
                '"a","b","c","d","e"' .
                "\n" . '"f","g","h","i","j"' .
                "\n" . '"k","l","m","n","o"',
                [
                    "price" => ["a", "f", "k"],
                    "quantity" => ["c", "h", "m"],
                    "total" => ["e", "j", "o"],
                ],
            ],
            // Test parse with header row
            [
                ["price" => "$[a]", "quantity" => "$[b]", "total" => "$[d]"],
                '"a","b","c","d"' .
                "\n" . '"1","2","3","4"' .
                "\n" . '"5","6","7","8"' .
                "\n" . '"9","10","11","12"',
                [
                    "price" => ["1", "5", "9"],
                    "quantity" => ["2", "6", "10"],
                    "total" => ["4", "8", "12"],
                ],
            ],
        ];
    }
}
