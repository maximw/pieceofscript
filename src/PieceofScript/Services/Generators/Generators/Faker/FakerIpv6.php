<?php


namespace PieceofScript\Services\Generators\Generators\Faker;


use PieceofScript\Services\Generators\Generators\FakerGenerator;
use PieceofScript\Services\Values\Hierarchy\BaseLiteral;
use PieceofScript\Services\Values\StringLiteral;

class FakerIpv6 extends FakerGenerator
{
    const NAME = 'Faker\\ipv6';

    public function run(): BaseLiteral
    {
        return new StringLiteral($this->faker->ipv6);
    }

}