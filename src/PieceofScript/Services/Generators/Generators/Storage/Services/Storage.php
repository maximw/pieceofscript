<?php


namespace PieceofScript\Services\Generators\Generators\Storage\Services;


use PieceofScript\Services\Errors\InternalError;
use PieceofScript\Services\Errors\RuntimeError;
use PieceofScript\Services\Values\ArrayLiteral;
use PieceofScript\Services\Values\BoolLiteral;
use PieceofScript\Services\Values\DateLiteral;
use PieceofScript\Services\Values\Hierarchy\BaseLiteral;
use PieceofScript\Services\Values\NullLiteral;
use PieceofScript\Services\Values\NumberLiteral;
use PieceofScript\Services\Values\StringLiteral;
use Symfony\Component\Yaml\Yaml;

class Storage
{
    protected $file;

    protected $cache = [];

    public function __construct(string $file)
    {
        $this->file = $file;
        $this->load();
    }

    public function set(string $key, $value)
    {
        $this->cache[$key] = $this->encode($value);
        $this->flush();
    }

    public function get(string $key)
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->decode($this->cache[$key], $key);
        }
        $this->load();
        if (array_key_exists($key, $this->cache)) {
            return $this->decode($this->cache[$key], $key);
        }
        return false;
    }

    public function keys()
    {
        return array_keys($this->cache);
    }

    protected function load()
    {
        if (!file_exists($this->file)) {
            file_put_contents($this->file, '');
        }
        if (!is_readable($this->file)) {
            throw new InternalError('Local storage error. File is not readable');
        }
        $this->cache = $this->trimQuotes(Yaml::parseFile($this->file));
    }

    protected function flush()
    {
        file_put_contents($this->file, Yaml::dump($this->cache, PHP_INT_MAX));
    }

    protected function decode(array $data, string $key)
    {
        if (!isset($data['type']) || !isset($data['data'])) {
            throw new RuntimeError('Broken structure in storage. Key "' . $key . '" has no data');
        }

        if ($data['type'] === ArrayLiteral::TYPE_NAME) {
            if (!is_array($data['data'])) {
                throw new RuntimeError('Broken structure for in storage. Key "' . $key . '" is not an array');
            }
            $result = [];
            foreach ($data['data'] as $key => $value) {
                $result[$key] = $this->decode($value, $key);
            }
            return new ArrayLiteral($result);
        }

        if ($data['type'] === NullLiteral::TYPE_NAME) {
            return new NullLiteral();
        }

        if ($data['type'] === BoolLiteral::TYPE_NAME) {
            return new BoolLiteral(trim(strtolower($data['data'])) === 'true');
        }

        if ($data['type'] === NumberLiteral::TYPE_NAME) {
            return new NumberLiteral((float) $data['data']);
        }

        if ($data['type'] === StringLiteral::TYPE_NAME) {
            return new StringLiteral($data['data']);
        }

        if ($data['type'] === DateLiteral::TYPE_NAME) {
            return new DateLiteral($data['data']);
        }

        throw new RuntimeError('Broken structure for in storage. Key "' . $key . '" has unknown type ' . $data['type']);
    }

    protected function encode(BaseLiteral $data): array
    {
        $result['type'] = $data::TYPE_NAME;

        if ($data instanceof ArrayLiteral) {
            $array = [];
            foreach ($data->getValue() as $key => $value) {
                $array[$key] = $this->encode($value);
            }
            $result['data'] = $array;
            return $result;
        }

        if ($data instanceof NullLiteral) {
            $result['data'] = 'null';
            return $result;
        }

        if ($data instanceof BoolLiteral) {
            $result['data'] = $data->getValue() ? 'true' : 'false';
            return $result;
        }

        if ($data instanceof NumberLiteral) {
            $result['data'] = (string) $data->getValue();
            return $result;
        }

        if ($data instanceof StringLiteral) {
            $result['data'] = $data->getValue();
            return $result;
        }

        if ($data instanceof DateLiteral) {
            $result['data'] = $data->getValue()->format(DATE_ISO8601);
            return $result;
        }

        throw new RuntimeError('Broken structure for in storage. Unknown type ' . $data::TYPE_NAME);
    }

    protected function trimQuotes($data)
    {
        if (is_array($data)) {
            $array = [];
            foreach ($data as $key => $value) {
                $array[$this->trimQuotes($key)] = $this->trimQuotes($value);
            }
            return $array;
        }
        if (is_string($data) && isset($data[0]) && $data[0] === '"') {
            $data = mb_substr($data, 1, -1, 'UTF-8');
        }
        return $data;
    }
}