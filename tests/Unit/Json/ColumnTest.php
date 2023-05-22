<?php

namespace Costinmrr\Parser\Tests\Unit\Json;

use Costinmrr\Parser\Json\Column;
use Exception;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    /**
     * @dataProvider dataProviderExpectedException
     */
    public function testCompareExpectedException(
        string $path1,
        mixed $iterators1,
        string $path2,
        mixed $iterators2,
        string $expectedError,
    ): void {
        $column1 = new Column();
        $column1->setPath($path1);
        $column1->setIterators($iterators1);
        $column2 = new Column();
        $column2->setPath($path2);
        $column2->setIterators($iterators2);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedError);
        $column1->compare($column2);
    }

    /**
     * @return mixed[]
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public static function dataProviderExpectedException(): array
    {
        return [
            // Data set 1 - integer iterators, different values
            [
                'path1' => '$.foo.bar[]',
                'iterators1' => 2,
                'path2' => '$foo.baz[*]',
                'iterators2' => 3,
                'expectedErrorMessage' => 'Incorrect JSON. Number of elements in $.foo.bar[] (2) and $foo.baz[*] (3) do not match.',
            ],

            // Data set 2 - integer & array iterators, different values on level 1
            [
                'iterator1Name' => '$.foo.bar[]',
                'iterator1' => 2,
                'iterator2Name' => '$foo.baz[*].qux[*]',
                'iterator2' => [3, 4, 5],
                'expectedErrorMessage' => 'Incorrect JSON. Number of elements in $.foo.bar[] (2) and $foo.baz[*] (3) do not match.',
            ],

            // Data set 3 - array & integer iterators, different values on level 1
            [
                'iterator1Name' => '$.foo.bar[].baz[*]',
                'iterator1' => [2, 3],
                'iterator2Name' => '$.foo.qux[]',
                'iterator2' => 3,
                'expectedErrorMessage' => 'Incorrect JSON. Number of elements in $.foo.bar[] (2) and $.foo.qux[] (3) do not match.',
            ],

            // Data set 4 - array with different depth, different values on level 2
            [
                'iterator1Name' => '$.foo.bar[].baz[*]',
                'iterator1' => [3],
                'iterator2Name' => '$.foo.qux[].quux[*]',
                'iterator2' => [[2, 3]],
                'expectedErrorMessage' => 'Incorrect JSON. Number of elements in $.foo.bar[0].baz[*] (3) and $.foo.qux[0].quux[*] (2) do not match.',
            ],

            // Data set 5 - array with different integer values on level 2
            [
                'iterator1Name' => '$.foo.bar[].baz[*]',
                'iterator1' => [2, 3],
                'iterator2Name' => '$.foo.qux[].quux[*]',
                'iterator2' => [2, 4],
                'expectedErrorMessage' => 'Incorrect JSON. Number of elements in $.foo.bar[1].baz[*] (3) and $.foo.qux[1].quux[*] (4) do not match.',
            ],

            // Data set 6 - array with different integer values on level 2
            [
                'iterator1Name' => '$.foo.bar[].baz[].qux[]',
                'iterator1' => [[1, 2], [], [2, 10]],
                'iterator2Name' => '$.foo.quux[*].quuz[*].quuux[*]',
                'iterator2' => [[1, 2], [], [2, 11]],
                'expectedErrorMessage' => 'Incorrect JSON. Number of elements in $.foo.bar[2].baz[1].qux[] (10) and $.foo.quux[2].quuz[1].quuux[*] (11) do not match.',
            ],
        ];
    }
    // phpcs:enable
}
