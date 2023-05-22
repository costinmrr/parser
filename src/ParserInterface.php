<?php

namespace Costinmrr\Parser;

interface ParserInterface
{
    /**
     * @return mixed[]
     */
    public function parse(ReturnType $returnType = ReturnType::DATASET): array;

    public function validate(): void;
}
