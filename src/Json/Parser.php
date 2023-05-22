<?php

namespace Costinmrr\Parser\Json;

use Costinmrr\Parser\ParserInterface;
use Costinmrr\Parser\ReturnType;
use Illuminate\Support\Arr;
use JsonPath\InvalidJsonException;
use JsonPath\JsonObject;

class Parser implements ParserInterface
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(protected array $mapping, protected string $content)
    {
    }

    /**
     * @throws InvalidJsonException
     */
    public function parse(ReturnType $returnType = ReturnType::DATASET): array
    {
        $jsonObject = (new JsonObject($this->content))->getJsonObjects('$');
        if ($jsonObject === null) {
            throw new \RuntimeException('Could not parse the json content.');
        }
        if (is_array($jsonObject)) {
            $jsonObject = $jsonObject[0]->getValue();
        }

        $structure = [];
        foreach ($this->mapping as $column => $path) {
            $col = new Column();
            $col->setName($column);
            $col->setPath($path);
            $col->setStructure(static::createStructure(trim($path, '$.'), $jsonObject));
            $structure[$column] = $col;
        }

        foreach ($structure as $colStruct) {
            $colStruct->setIterators(static::setColumnIterators($colStruct->getStructure()));
        }

        switch ($returnType) {
            case ReturnType::DATASET:
                // This is where the validation on the number of elements happens
                $deepestColStruct = static::getDeepestColStruct($structure);
                return static::convertToDataset($structure, $deepestColStruct);
            case ReturnType::INDIVIDUAL_VALUES:
                return static::convertToIndividualValues($structure);
        }

        throw new \RuntimeException('Unhandled return type');
    }

    /**
     * @param null|mixed[] $jsonObject
     * @return \stdClass|mixed[]
     */
    protected static function createStructure(string $path, ?array $jsonObject): \stdClass|array
    {
        $key = strstr($path, '.', true) ?: $path;
        $restOfPath = strstr($path, '.');
        if ($restOfPath !== false) {
            $restOfPath = ltrim($restOfPath, '.') ?: false;
        }
        if (str_contains($key, '[]') || str_contains($key, '[*]')) {
            // Array
            $key = str_replace(['[]', '[*]'], '', $key);
            if (isset($jsonObject[$key])) {
                // return an array of values
                $values = [];
                // If jsonObject[key] is not sequential (doesn't have numeric indexes or doesn't start from 0),
                // we need to add the contents as the first and only element of a new array. This happens mostly when
                // the json comes from xml conversion and what was supposed to be an array was converted to an object,
                // because the xml had only one node with the same name.
                if (Arr::isAssoc((array)$jsonObject[$key])) {
                    $jsonObject[$key] = [$jsonObject[$key]];
                }
                if (is_iterable($jsonObject[$key])) {
                    foreach ($jsonObject[$key] as $item) {
                        if ($restOfPath !== false) {
                            $values[] = static::createStructure($restOfPath, (array)$item);
                        } else {
                            $valueObject = new \stdClass();
                            $valueObject->value = is_string($item) ? trim($item) : $item;
                            $values[] = $valueObject;
                        }
                    }
                }
                return $values;
            }

            // return an empty array if the key doesn't exist
            return [];
        }

        // Get the new json object
        preg_match('/^(.*)\[(.*)\]$/', $key, $matches);
        if ($matches) {
            // Get a specific item from an array => e.g. '$jsonObject[items][100]'
            $key = $matches[1];
            $index = $matches[2];
            $newJsonObject = null;
            /** @var array<string, array<string, mixed>> $jsonObject */
            if (isset($jsonObject[$key][$index])) {
                $newJsonObject = $jsonObject[$key][$index];
            }
        } else {
            // Get the value for the key => e.g. $jsonObject['item']
            $newJsonObject = null;
            if (isset($jsonObject[$key])) {
                $newJsonObject = $jsonObject[$key];
            }
        }

        if ($restOfPath) {
            // go to next level in the jsonPath
            /** @var null|mixed[] $newJsonObject */
            return static::createStructure($restOfPath, $newJsonObject);
        }

        // return the actual value for the current element
        $valueObject = new \stdClass();
        $valueObject->value = is_string($newJsonObject) ? trim($newJsonObject) : $newJsonObject;
        return $valueObject;
    }

    /**
     * @return null|int|mixed[]
     */
    protected static function setColumnIterators(mixed $values): null|int|array
    {
        $iterators = [];
        if (is_object($values) && property_exists($values, 'value')) {
            // We are on the last level, stop
            return null;
        }

        if (is_array($values)) {
            if (isset($values[0]) && is_object($values[0]) && property_exists($values[0], 'value')) {
                return count($values);
            }

            foreach ($values as $key => $value) {
                $iterators[$key] = static::setColumnIterators($value);
            }
        }

        return $iterators;
    }

    /**
     * @param Column[] $structure
     */
    protected static function getDeepestColStruct(array $structure): Column|null
    {
        $deepestColStruct = null;
        foreach ($structure as $colStruct) {
            if ($deepestColStruct === null) {
                $deepestColStruct = $colStruct;
            }

            if ($colStruct->getIterators() === null) {
                continue;
            }

            if ($colStruct->compare($deepestColStruct)) {
                $deepestColStruct = $colStruct;
            }
        }

        return $deepestColStruct;
    }

    /**
     * @param Column[] $structure
     * @return mixed[]
     */
    protected static function convertToDataset(array $structure, Column|null $deepestColumn): array
    {
        $parsedData = [];

        foreach ($structure as $column) {
            $parsedData[$column->getName()] = static::createColumnParsedDataset(
                $column->getStructure(),
                $deepestColumn?->getIterators(),
            );
        }

        return $parsedData;
    }

    /**
     * @param \stdClass|mixed[]|null $colStructure
     * @param null|int|mixed[] $deepestLevel
     * @return mixed[]
     */
    protected static function createColumnParsedDataset(
        \stdClass|array|null $colStructure,
        null|int|array $deepestLevel,
        mixed $previousItem = null,
    ): array {
        if ($deepestLevel === null) {
            // Add only one item and that should be the structure->value
            return [$colStructure->value ?? null];
        }

        if (is_int($deepestLevel)) {
            // We are on the deepest level, add all items if array, or previous item if object
            // We should add as many items as the deepest level's value
            $items = [];
            for ($i = 0; $i < $deepestLevel; $i++) {
                if (is_array($colStructure)) {
                    $items[] = $colStructure[$i]->value ?? null;
                } elseif (is_object($colStructure)) {
                    $items[] = $colStructure->value;
                } else {
                    $items[] = $previousItem;
                }
            }
            return $items;
        }

        $arraysToBeMerged = [];
        foreach ($deepestLevel as $key => $value) {
            $newStructure = null;
            if (is_object($colStructure)) {
                $previousItem = $colStructure->value ?? null;
            }
            if (is_array($colStructure) && isset($colStructure[$key])) {
                if (is_object($colStructure[$key]) && property_exists($colStructure[$key], 'value')) {
                    $previousItem = $colStructure[$key]->value;
                }
                $newStructure = $colStructure[$key];
            }
            /** @var mixed[]|int|null $value */
            $arraysToBeMerged[] = static::createColumnParsedDataset($newStructure, $value, $previousItem);
        }

        return array_merge(...$arraysToBeMerged);
    }

    public function validate(): void
    {
        // TODO: Implement validate() method.
    }

    /**
     * @param Column[] $structure
     * @return array<string, mixed>
     */
    protected static function convertToIndividualValues(array $structure): array
    {
        $parsedData = [];
        foreach ($structure as $column) {
            $parsedData[$column->getName()] = static::extractValues(
                static::createColumnParsedIndividualValues($column->getStructure()),
            );
        }

        return $parsedData;
    }

    /**
     * @param \stdClass|null|mixed[] $colStructure
     * @return \stdClass|null|mixed[]
     */
    protected static function createColumnParsedIndividualValues(
        \stdClass|null|array $colStructure,
    ): \stdClass|array|null {
        if (is_object($colStructure)) {
            $item = new \stdClass();
            $item->value = $colStructure->value ?? null;
            return $item;
            //return $colStructure->value ?? null;
        }

        if (is_array($colStructure)) {
            $items = [];
            /** @var mixed[]|\stdClass|null $item */
            foreach ($colStructure as $item) {
                $newItems = static::createColumnParsedIndividualValues($item);
                if (is_object($newItems)) {
                    $items[] = $newItems;
                } elseif (is_array($newItems)) {
                    $items = array_merge($items, $newItems);
                }
            }
            return $items;
        }

        return null;
    }

    /**
     * All values are stored as stdClass objects with a "value" property.
     * This method extracts the values from the object and returns only the values.
     *
     * @param \stdClass|null|mixed[] $values
     */
    protected static function extractValues(\stdClass|array|null $values): mixed
    {
        if (is_object($values)) {
            return $values->value ?? null;
        }

        if (is_array($values)) {
            $extractedValues = [];
            /** @var null|\stdClass|mixed[] $value */
            foreach ($values as $value) {
                $extractedValues[] = static::extractValues($value);
            }
            return $extractedValues;
        }

        return null;
    }
}
