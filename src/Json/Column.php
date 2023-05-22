<?php

namespace Costinmrr\Parser\Json;

use stdClass;

class Column
{
    protected string $name;
    protected string $path;
    /**
     * @var stdClass|mixed[]
     */
    protected stdClass|array $structure;
    /**
     * @var int|mixed[]|null
     */
    protected null|int|array $iterators;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return stdClass|mixed[]
     */
    public function getStructure(): stdClass|array
    {
        return $this->structure;
    }

    /**
     * @param stdClass|mixed[] $structure
     */
    public function setStructure(stdClass|array $structure): void
    {
        $this->structure = $structure;
    }

    /**
     * @return null|int|mixed[]
     */
    public function getIterators(): null|int|array
    {
        return $this->iterators;
    }

    /**
     * @param null|int|mixed[] $iterators
     */
    public function setIterators(null|int|array $iterators): void
    {
        $this->iterators = $iterators;
    }

    public function compare(Column $otherColumn): bool
    {
        $comparison = static::compareIterators($this->getIterators(), $otherColumn->getIterators());
        if (is_bool($comparison)) {
            return $comparison;
        }

        // Compose an error message if the comparison returned an array
        $firstPath = $this->getPath();
        $secondPath = $otherColumn->getPath();
        foreach ($comparison->getLevels() as $level) {
            if (is_null($level)) {
                // We need to remove from first/secondPath everything after the first [] or [*]
                // '/' => $.foo[1].bar[*].baz[*] becomes $.foo[1].bar[*]
                $firstPath = preg_replace('/(\[[\*]{0,1}\])(.*$)/', "$1", $firstPath ?? '');
                $secondPath = preg_replace('/(\[[\*]{0,1}\])(.*$)/', "$1", $secondPath ?? '');
                break;
            }
            $firstPath = preg_replace('/\[[\*]{0,1}\]/', '[' . $level . ']', $firstPath ?? '', 1);
            $secondPath = preg_replace('/\[[\*]{0,1}\]/', '[' . $level . ']', $secondPath ?? '', 1);
        }
        throw new \RuntimeException('Incorrect JSON. Number of elements in ' . $firstPath .
            ' (' . $comparison->getFirstValues() . ') and ' .
            $secondPath . ' (' . $comparison->getSecondValues() . ') do not match.');
    }

    /**
     * @return IteratorsComparison|bool Bool in case of a working comparison, true if the first
     * is greater than the second, false otherwise
     */
    protected static function compareIterators(
        mixed $iterators1,
        mixed $iterators2,
        IteratorsComparison $iteratorsComparison = null,
    ): IteratorsComparison|bool {
        if (!is_null($iterators1) && is_null($iterators2)) {
            return true;
        }

        if (is_null($iteratorsComparison)) {
            $iteratorsComparison = new IteratorsComparison();
        }

        if (is_int($iterators1) && is_int($iterators2)) {
            if ($iterators1 !== $iterators2) {
                $iteratorsComparison->setFirstValues($iterators1);
                $iteratorsComparison->setSecondValues($iterators2);
                return $iteratorsComparison;
            }
            return false;
        }

        if (is_int($iterators1) && is_array($iterators2)) {
            if (count($iterators2) !== $iterators1) {
                $iteratorsComparison->setFirstValues($iterators1);
                $iteratorsComparison->setSecondValues(count($iterators2));
                return $iteratorsComparison;
            }
            return false;
        }

        if (is_array($iterators1) && is_int($iterators2)) {
            if (count($iterators1) !== $iterators2) {
                $iteratorsComparison->setFirstValues(count($iterators1));
                $iteratorsComparison->setSecondValues($iterators2);
                return $iteratorsComparison;
            }
            return true;
        }

        if (is_array($iterators1) && is_array($iterators2)) {
            if (count($iterators1) !== count($iterators2)) {
                $iteratorsComparison->setFirstValues(count($iterators1));
                $iteratorsComparison->setSecondValues(count($iterators2));
                return $iteratorsComparison;
            }

            $initialLevels = $iteratorsComparison->getLevels();
            foreach ($iterators1 as $key => $value) {
                // replace last value in array with the current key and add an extra null value to the end of the array
                $currentLevels = $initialLevels;
                $currentLevels[array_key_last($currentLevels)] = (int)$key;
                $currentLevels[] = null;
                $iteratorsComparison->setLevels($currentLevels);
                // $iteratorComparison->levels becomes something like [2,4,null] which means
                // the element at $.foo[2].bar[4].baz[*] is where we are now

                if (! array_key_exists($key, $iterators2)) {
                    $iteratorsComparison->setFirstValues(count($iterators1));
                    $iteratorsComparison->setSecondValues(null);
                    return $iteratorsComparison;
                }

                $comparison = static::compareIterators($value, $iterators2[$key], $iteratorsComparison);
                if (is_bool($comparison) && $comparison === true) {
                    return true;
                }

                if ($comparison instanceof IteratorsComparison) {
                    return $comparison;
                }
            }

            return false;
        }

        return false;
    }
}
