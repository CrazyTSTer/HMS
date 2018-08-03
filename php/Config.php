<?php
define('CONFIG_PATH', __DIR__ . '/config');
define('CONFIG_EXT', 'cfg');

class Config
{
    protected static $instances = null;
    protected $config = [];
    protected $name = null;

    /**
     * @param string $name
     * @return Settings
     * @throws Exception
     */
    public static function getConfig(string $name)
    {
        return self::$instances[$name] = self::$instances[$name] ?? new static($name);
    }

    public function __construct(string $name)
    {
        if (!preg_match('/^[0-9a-zA-Z_]+(?:\/[0-9a-zA-Z_]+)*$/', $name)) {
            throw new Exception('Wrong config name.');
        }
        $cfgFullPath = CONFIG_PATH . '/' . $name . '.' . CONFIG_EXT;
        if (file_exists($cfgFullPath)) {
            $this->config = require($cfgFullPath);
            if (!is_array($this->config)) {
                throw new Exception('Wrong config contents.');
            }
        }
        $this->name = $name;
    }

    /**
     *
     */
    public function dump()
    {
        var_export($this->config);
        echo("\n");
    }

    /**
     * @param $path
     * @param $var
     */
    public function set($path = null, $var)
    {
        $path_array = array_reverse(explode('/', $path));
        isset($path) ? $tmp = [$path_array[0] => $var] : $tmp = $var;
        for ($i = 1; $i < count($path_array); $i++) {
            $tmp = [$path_array[$i] => $tmp];
        }
        $this->config = array_replace_recursive($this->config, $tmp);
    }

    /**
     * @param $path
     */
    public function drop($path)
    {
        $path_array = explode('/', $path);
        $tmp = &$this->config;
        $last_el = array_pop($path_array);
        foreach ($path_array as $el) {
            if (!isset($tmp[$el])) return;
            $tmp = &$tmp[$el];
        }
        unset($tmp[$last_el]);
    }

    /**
     * @param $path
     * @return mixed|null
     */
    public function get($path = null)
    {
        if (!isset($path)) {
            return $this->config;
        }

        $path_array = explode('/', $path);
        $tmp = &$this->config;
        foreach ($path_array as $el) {
            $tmp = &$tmp[$el] ?? null;
        }

        return $tmp;
    }

    /**
     *
     */
    public function save()
    {
        file_put_contents(
            CONFIG_PATH . '/' . $this->name . '.' . CONFIG_EXT,
            '<?php return ' . var_export($this->config, true) . ';'
        );
    }
}