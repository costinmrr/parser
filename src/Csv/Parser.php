<?php

namespace Costinmrr\Parser\Csv;

use Costinmrr\Parser\ParserInterface;
use Costinmrr\Parser\ReturnType;
use Exception;

class Parser implements ParserInterface
{
    /**
     * @var resource|false
     */
    protected static $csvHandler = false;
    protected bool $hasHeaderRow = false;
    /**
     * @var array<string, int>
     */
    protected array $columnIndexes = []; // column_name => column_index("services" => 2)

    /**
     * @param array<string, string|int> $mapping
     * @throws \RuntimeException
     */
    public function __construct(protected array $mapping, protected string $content)
    {
        static::$csvHandler = fopen("php://temp", 'rb+');
        if (static::$csvHandler === false) {
            throw new \RuntimeException('Could not open a temporary file for the csv parser.');
        }
        fwrite(static::$csvHandler, $this->content);
        $this->rewindCsvHandler();

        $this->trimMappingReferences();
        $this->setHasHeaderRow();
        $this->setColumnIndexes();
    }

    /**
     * @param ReturnType $returnType it is irrelevant for the csv parser,
     * because it always returns the same length for each column
     */
    public function parse(ReturnType $returnType = ReturnType::DATASET): array
    {
        $response = [];
        foreach ($this->mapping as $column => $path) {
            $response[$column] = [];
        }
        $this->rewindCsvHandler();
        if ($this->hasHeaderRow) {
            $this->readCsvLine();
        }
        while ($line = $this->readCsvLine()) {
            foreach ($this->mapping as $column => $path) {
                $response[$column][] = $line[$this->columnIndexes[$column]] ?? null;
            }
        }

        if (static::$csvHandler !== false) {
            fclose(static::$csvHandler);
        }

        return $response;
    }

    public function validate(): void
    {
        // TODO: Implement validate() method.
    }

    /**
     * Remove the leading '$[' and trailing ']'.
     */
    public function trimMappingReferences(): void
    {
        foreach ($this->mapping as $column => $path) {
            if (is_string($path)) {
                $path = trim($path);
                // Use substr instead of preg_replace because it's 5 times faster.
                if (str_starts_with($path, '$[')) {
                    $path = substr($path, 2);
                }
                if (str_ends_with($path, ']')) {
                    $path = substr($path, 0, -1);
                }
            }
            $this->mapping[$column] = $path;
        }
    }

    public function hasHeaderRow(): bool
    {
        return $this->hasHeaderRow;
    }

    /**
     * @return array<string, int>
     */
    public function columnIndexes(): array
    {
        return $this->columnIndexes;
    }

    protected function rewindCsvHandler(): void
    {
        if (static::$csvHandler !== false) {
            rewind(static::$csvHandler);
        }
    }

    /**
     * @return mixed[]|false
     */
    public function readCsvLine(): array|false
    {
        if (static::$csvHandler === false) {
            return [];
        }
        return fgetcsv(static::$csvHandler);
    }

    /**
     * Check if the first row of the CSV contains column names.
     */
    public function setHasHeaderRow(): void
    {
        if (count($this->mapping) === 0) {
            return;
        }

        $this->rewindCsvHandler();
        $headerRow = $this->readCsvLine();
        if (empty($headerRow)) {
            return;
        }

        // Copy mapping into a local variable and remove all values found in the first row read from the CSV.
        // If there are any values left in the mapping, the first row is not a heading row.
        $mapping = $this->mapping;
        foreach ($headerRow as $headerValue) {
            foreach ($mapping as $col => $path) {
                if ($headerValue === $path) {
                    unset($mapping[$col]);
                    break;
                }
            }
        }
        $this->hasHeaderRow = empty($mapping);

        // If $mapping is not empty and the original paths are not integers, throw an exception
        if (!$this->hasHeaderRow) {
            foreach ($this->mapping as $path) {
                if (!is_int($path) && !ctype_digit($path)) {
                    throw new \RuntimeException("Header row does not contain all columns in mapping");
                }
            }
        }
    }

    /**
     * Set the column indexes.
     * The column indexes are used to map the CSV columns to the mapping.
     */
    public function setColumnIndexes(): void
    {
        if (count($this->mapping) === 0) {
            return;
        }

        $this->rewindCsvHandler();
        $headerRow = $this->readCsvLine();
        if (empty($headerRow)) {
            return;
        }

        foreach ($this->mapping as $column => $path) {
            if (!$this->hasHeaderRow()) {
                // If there's no header row, use the path converted to int as the index
                $this->columnIndexes[$column] = (int) $path;
            } else {
                // If there's a header row, iterate through the first row and find the index where
                // the value is the same as the path.
                $index = array_search($path, $headerRow, true);
                if ($index !== false && is_int($index)) {
                    $this->columnIndexes[$column] = $index;
                }
            }
        }

        // Check if there are columns without an index.
        foreach ($this->mapping as $column => $path) {
            if (!isset($this->columnIndexes[$column])) {
                throw new \RuntimeException("Column '$column' is not found in the CSV file.");
            }
        }
    }
}
