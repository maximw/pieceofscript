<?php

namespace PieceofScript\Services\Generators\Generators;


use PieceofScript\Services\Generators\Generators\Internal\Append;
use PieceofScript\Services\Generators\Generators\Internal\Choice;
use PieceofScript\Services\Generators\Generators\Internal\DateFormat;
use PieceofScript\Services\Generators\Generators\Internal\DateModify;
use PieceofScript\Services\Generators\Generators\Internal\Explode;
use PieceofScript\Services\Generators\Generators\Internal\FindString;
use PieceofScript\Services\Generators\Generators\Internal\IfGenerator;
use PieceofScript\Services\Generators\Generators\Internal\Implode;
use PieceofScript\Services\Generators\Generators\Internal\Keys;
use PieceofScript\Services\Generators\Generators\Internal\Prepend;
use PieceofScript\Services\Generators\Generators\Internal\ReplaceString;
use PieceofScript\Services\Generators\Generators\Internal\ToLower;
use PieceofScript\Services\Generators\Generators\Internal\ToUpper;
use PieceofScript\Services\Generators\IGeneratorProvider;
use PieceofScript\Services\Generators\Generators\Internal\ArrayGenerator;
use PieceofScript\Services\Generators\Generators\Internal\Identical;
use PieceofScript\Services\Generators\Generators\Internal\Max;
use PieceofScript\Services\Generators\Generators\Internal\Min;
use PieceofScript\Services\Generators\Generators\Internal\Regex;
use PieceofScript\Services\Generators\Generators\Internal\Round;
use PieceofScript\Services\Generators\Generators\Internal\Similar;
use PieceofScript\Services\Generators\Generators\Internal\Size;
use PieceofScript\Services\Generators\Generators\Internal\Slice;
use PieceofScript\Services\Generators\Generators\Internal\ToBool;
use PieceofScript\Services\Generators\Generators\Internal\ToDate;
use PieceofScript\Services\Generators\Generators\Internal\ToNumber;
use PieceofScript\Services\Generators\Generators\Internal\ToString;
use PieceofScript\Services\Generators\Generators\Internal\UrlDecode;
use PieceofScript\Services\Generators\Generators\Internal\UrlEncode;

class InternalProvider implements IGeneratorProvider
{

    public function __construct()
    {
    }

    public function getGenerators(): array
    {
        return [
            new Append(),
            new ArrayGenerator(),
            new Choice(),
            new DateFormat(),
            new DateModify(),
            new Explode(),
            new FindString(),
            new Identical(),
            new IfGenerator(),
            new Implode(),
            new Keys(),
            new Max(),
            new Min(),
            new Prepend(),
            new Regex(),
            new ReplaceString(),
            new Round(),
            new Similar(),
            new Size(),
            new Slice(),
            new ToBool(),
            new ToDate(),
            new ToLower(),
            new ToNumber(),
            new ToString(),
            new ToUpper(),
            new UrlDecode(),
            new UrlEncode(),
        ];
    }
}