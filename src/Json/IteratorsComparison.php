<?php

namespace Costinmrr\Parser\Json;

class IteratorsComparison
{
    protected mixed $firstValues;

    protected mixed $secondValues;

    /**
     * @var array<int|null>
     */
    protected array $levels = [null];

    /**
     * @param array<int|null> $levels
     */
    public function setLevels(array $levels): void
    {
        $this->levels = $levels;
    }

    public function setFirstValues(mixed $firstValues): void
    {
        $this->firstValues = $firstValues;
    }

    public function setSecondValues(mixed $secondValues): void
    {
        $this->secondValues = $secondValues;
    }

    /**
     * @return array<int|null>
     */
    public function getLevels(): array
    {
        return $this->levels;
    }

    public function getFirstValues(): mixed
    {
        return $this->firstValues;
    }

    public function getSecondValues(): mixed
    {
        return $this->secondValues;
    }
}