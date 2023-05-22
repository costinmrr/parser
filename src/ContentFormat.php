<?php

namespace Costinmrr\Parser;

enum ContentFormat: string
{
    case JSON = 'json';
    case XML = 'xml';
    case CSV = 'csv';
}
