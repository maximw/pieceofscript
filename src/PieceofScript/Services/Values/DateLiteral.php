<?php


namespace PieceofScript\Services\Values;


use PieceofScript\Services\Config\Config;
use PieceofScript\Services\Errors\Parser\EvaluationError;
use PieceofScript\Services\Errors\TypeErrors\IncompatibleTypesOperationException;
use PieceofScript\Services\Utils\Utils;
use PieceofScript\Services\Values\Hierarchy\BaseLiteral;
use PieceofScript\Services\Values\Hierarchy\IScalarValue;

class DateLiteral extends BaseLiteral implements IScalarValue
{
    const TYPE_NAME = 'Date';

    /** @var \DateTimeImmutable */
    protected $value;

    public function __construct($value = 'now')
    {
        try {
            $now = Utils::getRelativeDateTime();
            set_error_handler(function() {throw new \Exception(); });
            $this->value = $now->modify((string) $value);
            restore_error_handler();
        } catch (\Exception $e) {
            throw new EvaluationError('Cannot parse ' . $value . ' to Date');
        }
    }

    public function toBool(): BoolLiteral
    {
        return new BoolLiteral(true);
    }

    public function toNumber(): NumberLiteral
    {
        return new NumberLiteral((float) $this->value->format('U.u'));
    }

    public function toString(): StringLiteral
    {
        return new StringLiteral($this->value->format(Config::get()->getDefaultDateFormat()));
    }

    public function toDate(): DateLiteral
    {
        return $this;
    }

    public function toPrint(): string
    {
        return $this->value->format(Config::get()->getDefaultDateFormat());
    }

    public function oEqual(BaseLiteral $value): BoolLiteral
    {
        if ($value instanceof NullLiteral) {
            return new BoolLiteral(false);
        }
        if ($value instanceof DateLiteral) {
            return new BoolLiteral($this->getValue() == $value->getValue());
        }

        throw new IncompatibleTypesOperationException('==', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oGreater(BaseLiteral $value): BoolLiteral
    {
        if ($value instanceof DateLiteral) {
            return new BoolLiteral($this->getValue() > $value->getValue());
        }

        throw new IncompatibleTypesOperationException('>', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oLower(BaseLiteral $value): BoolLiteral
    {
        if ($value instanceof DateLiteral) {
            return new BoolLiteral($this->getValue() < $value->getValue());
        }

        throw new IncompatibleTypesOperationException('<', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oNotEqual(BaseLiteral $value): BoolLiteral
    {
        if ($value instanceof NullLiteral) {
            return new BoolLiteral(true);
        }
        if ($value instanceof DateLiteral) {
            return new BoolLiteral($this->getValue() != $value->getValue());
        }

        throw new IncompatibleTypesOperationException('!=', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oGreaterEqual(BaseLiteral $value): BoolLiteral
    {
        if ($value instanceof DateLiteral) {
            return new BoolLiteral($this->getValue() >= $value->getValue());
        }

        throw new IncompatibleTypesOperationException('>=', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oLowerEqual(BaseLiteral $value): BoolLiteral
    {
        if ($value instanceof DateLiteral) {
            return new BoolLiteral($this->getValue() <= $value->getValue());
        }

        throw new IncompatibleTypesOperationException('<=', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oPlus(BaseLiteral $value): BaseLiteral
    {
        throw new IncompatibleTypesOperationException('+', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oMinus(BaseLiteral $value): BaseLiteral
    {
        throw new IncompatibleTypesOperationException('-', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oMultiply(BaseLiteral $value): BaseLiteral
    {
        throw new IncompatibleTypesOperationException('*', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oPositive(): BaseLiteral
    {
        throw new IncompatibleTypesOperationException('+', self::TYPE_NAME);
    }

    public function oNegative(): BaseLiteral
    {
        throw new IncompatibleTypesOperationException('-', self::TYPE_NAME);
    }

    public function oDivide(BaseLiteral $value): BaseLiteral
    {
        throw new IncompatibleTypesOperationException('/', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oDivideMod(BaseLiteral $value): BaseLiteral
    {
        throw new IncompatibleTypesOperationException('%', self::TYPE_NAME, $value::TYPE_NAME);
    }

    public function oPower(BaseLiteral $value): BaseLiteral
    {
        throw new IncompatibleTypesOperationException('^', self::TYPE_NAME, $value::TYPE_NAME);
    }

}