<?php


namespace PieceofScript\Services\Config;


use PieceofScript\Services\Errors\InternalError;
use PieceofScript\Services\Errors\RuntimeError;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class Config
{
    const INPUT_OPTIONS_MAP = [
        'storage' => 'storage_name',
        'skip-assertions' => 'skip_assertions'
    ];


    protected $endpoints_file = './endpoints.yaml';

    protected $endpoints_dir = './endpoints';

    protected $generators_file = './generators.yaml';

    protected $generators_dir = './generators';

    protected $cache_dir = null;

    protected $http_connect_timeout = 0;

    protected $http_timeout = 0;

    protected $http_max_redirects = 0;

    protected $current_timestamp = null;

    protected $default_date_format = DATE_ISO8601;

    protected $default_timezone = null;

    protected $json_max_depth = 512;

    protected $random_seed = null;

    protected $faker_locale = 'en_US';

    protected $storage_name = null;

    protected $skip_assertions = true;

    protected static $instance;

    /**
     * @return string
     */
    public function getEndpointsFile(): string
    {
        return $this->endpoints_file;
    }

    /**
     * @param string $endpoints_file
     */
    protected function setEndpointsFile($endpoints_file)
    {
        $this->endpoints_file = (string) $endpoints_file;
    }

    /**
     * @return string
     */
    public function getEndpointsDir(): string
    {
        return $this->endpoints_dir;
    }

    /**
     * @param string $endpoints_dir
     */
    protected function setEndpointsDir($endpoints_dir)
    {
        $this->endpoints_dir = (string) $endpoints_dir;
    }

    /**
     * @return string
     */
    public function getGeneratorsFile(): string
    {
        return $this->generators_file;
    }

    /**
     * @param string $generators_file
     */
    protected function setGeneratorsFile(string $generators_file)
    {
        $this->generators_file = $generators_file;
    }

    /**
     * @return string
     */
    public function getGeneratorsDir(): string
    {
        return $this->generators_dir;
    }

    /**
     * @param string $generators_dir
     */
    protected function setGeneratorsDir($generators_dir)
    {
        $this->generators_dir = (string) $generators_dir;
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cache_dir;
    }

    /**
     * @param string $dir
     * @throws InternalError
     */
    protected function setCacheDir($dir = null)
    {
        if (null === $dir) {
            $dir = sys_get_temp_dir();
        }
        $dir = (string) $dir;
        if (!is_writable($dir)) {
            throw new InternalError('Cache directory is not writable');
        }
        if (!is_readable($dir)) {
            throw new InternalError('Cache directory is not readable');
        }
        $this->cache_dir = $dir;
    }

    /**
     * @return int
     */
    public function getHttpConnectTimeout(): int
    {
        return $this->http_connect_timeout;
    }

    /**
     * @param float $http_timeout
     */
    protected function setHttpConnectTimeout($http_timeout)
    {
        $this->http_connect_timeout = (float) $http_timeout;
    }

    /**
     * @return float
     */
    public function getHttpTimeout(): float
    {
        return $this->http_timeout;
    }

    /**
     * @param float|null $http_timeout
     */
    protected function setHttpReadTimeout($http_timeout = null)
    {
        if (null === $http_timeout) {
            $http_timeout = 0;
        }
        $this->http_timeout = (float) $http_timeout;
    }

    /**
     * @return int
     */
    public function getHttpMaxRedirects(): int
    {
        return $this->http_max_redirects;
    }

    /**
     * @param int $http_max_redirects
     */
    protected function setHttpMaxRedirects(int $http_max_redirects)
    {
        $this->http_max_redirects = (int) $http_max_redirects;
    }

    /**
     * @return int
     */
    public function getCurrentTimestamp(): int
    {
        return $this->current_timestamp;
    }

    /**
     * @param null|int|string $current_timestamp
     * @throws \Exception
     */
    protected function setCurrentTimestamp($current_timestamp = null)
    {
        if ($current_timestamp == null) {
            $current_timestamp = time();
        }

        if (is_numeric($current_timestamp)) {
            $this->current_timestamp = (int) $current_timestamp;
        } else {
            throw new InternalError('Cannot set current timestamp');
        }
    }

    /**
     * @return string
     */
    public function getDefaultDateFormat(): string
    {
        return $this->default_date_format;
    }

    /**
     * @param string $default_date_format
     */
    protected function setDefaultDateFormat(string $default_date_format)
    {
        $this->default_date_format = (string) $default_date_format;
    }

    /**
     * @return \DateTimeZone
     */
    public function getDefaultTimezone(): \DateTimeZone
    {
        return $this->default_timezone;
    }

    /**
     * @param null|string $default_timezone
     */
    protected function setDefaultTimezone($default_timezone = null)
    {
        if (null === $default_timezone) {
            $this->default_timezone = new \DateTimeZone(date_default_timezone_get());
        } else {
            date_default_timezone_set($default_timezone);
            $this->default_timezone = new \DateTimeZone($default_timezone);
        };
    }

    /**
     * @return int
     */
    public function getJsonMaxDepth(): int
    {
        return $this->json_max_depth;
    }

    /**
     * @param int $json_max_depth
     */
    protected function setJsonMaxDepth($json_max_depth)
    {
        $this->json_max_depth = (int) $json_max_depth;
    }

    /**
     * @return int
     */
    public function getRandomSeed(): int
    {
        return $this->random_seed;
    }

    /**
     * @param int $random_seed
     */
    protected function setRandomSeed($random_seed = null)
    {
        if (null === $random_seed) {
            $random_seed = mt_rand();
        }
        $this->random_seed = (int) $random_seed;
        mt_srand($this->random_seed);
    }

    /**
     * @return string
     */
    public function getFakerLocale(): string
    {
        return $this->faker_locale ?? 'en_US';
    }

    /**
     * @param string $locale
     */
    protected function setFakerLocale($locale)
    {
        $this->faker_locale = $locale;
    }

    /**
     * @return string
     */
    public function getStorageName()
    {
        return $this->storage_name;
    }

    /**
     * @param string $name
     */
    protected function setStorageName($name)
    {
        $this->storage_name = $name;
    }

    /**
     * @return bool
     */
    public function getSkipAssertions(): bool
    {
        return $this->skip_assertions;
    }

    /**
     * @param bool $skip_assertions
     */
    public function setSkipAssertions(bool $skip_assertions)
    {
        $this->skip_assertions = $skip_assertions;
    }

    /**
     * @return array
     */
    public function export(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param string $filename
     * @param bool $requireFile
     * @throws RuntimeError
     */
    public static function loadFromFile(string $filename, bool $requireFile)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            if ($requireFile) {
                throw new RuntimeError('Cannot find configuration file ' . $filename);
            } else {
                return;
            }
        }

        $config = Yaml::parseFile($filename);
        foreach ($config as $item => $value) {
            $value = trim($value, '\'"');
            $method = 'set' . self::toCamelCase($item);
            if (method_exists(self::get(), $method)) {
                self::get()->$method($value);
            }
        }
    }

    /**
     * @param InputInterface $input
     * @throws \Exception
     */
    public static function loadInput(InputInterface $input)
    {
        foreach (static::INPUT_OPTIONS_MAP as $optionName => $fieldName) {
            $value = $input->getOption($optionName);
            if (null !== $value) {
                $method = 'set' . self::toCamelCase($fieldName);
                if (method_exists(self::get(), $method)) {
                    self::get()->$method($value);
                }
            }
        }
    }

    private function __construct()
    {
        $this->setCacheDir($this->cache_dir);
        $this->setCurrentTimestamp($this->current_timestamp);
        $this->setDefaultTimezone($this->default_timezone);
        $this->setRandomSeed($this->random_seed);
    }

    public static function get(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __clone() {}

    private function __wakeup() {}

    private static function toCamelCase($str)
    {
        $string_parts = preg_split('/_+/', $str);

        if (!is_array($string_parts) || (sizeof($string_parts) < 1)) {
            throw new InternalError('Unable to read config.yaml value ' . $str);
        }
        foreach ($string_parts as $key => $string_part) {
            $string_parts[$key] = ucfirst(strtolower($string_part));
        }
        return implode('', $string_parts);
    }

}