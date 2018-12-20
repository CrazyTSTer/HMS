<?php
define('CONFIG_PATH', __DIR__ . '/../config');
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
        if (!is_dir(CONFIG_PATH)) {mkdir(CONFIG_PATH);}
        $cfgFullPath = CONFIG_PATH . '/' . $name . '.' . CONFIG_EXT;
        if (file_exists($cfgFullPath)) {
            $this->config = json_decode(file_get_contents($cfgFullPath), true);
            if (!is_array($this->config)) {
                throw new Exception('Wrong config contents.');
            }
        }
        $this->name = $name;
    }

    /**
     * @param $path
     * @param $var
     */
    public function set($path = null, $var)
    {
        if (isset($path)) {
            $path_array = explode('/', $path);
            $last_el = array_pop($path_array);
            $tmp = &$this->config;
            foreach ($path_array as $el) {
                if (isset($tmp[$el]) && is_array($tmp[$el])) {
                    $tmp = &$tmp[$el];
                } else {
                    $tmp[$el] = [];
                    $tmp = &$tmp[$el];
                }

            }
            if (empty($last_el)) {
                $tmp[] = $var;
            } else {
                $tmp[$last_el] = $var;
            }
        } else {
            $this->config = $var;
        }
    }

    /**
     * @param $path
     * @return mixed|null
     */
    public function get($path = null)
    {
        $tmp = &$this->config;
        if (isset($path)) {
            $path_array = explode('/', $path);
            foreach ($path_array as $el) {
                if (isset($tmp[$el])) {
                    $tmp = &$tmp[$el];
                } else {
                    $tmp = null;
                }
            }
        };
        return $tmp;
    }

    /**
     * @param $path
     */
    public function drop($path = null)
    {
        if (isset($path)) {
            $path_array = explode('/', $path);
            $tmp = &$this->config;
            $last_el = array_pop($path_array);
            foreach ($path_array as $el) {
                if (!isset($tmp[$el])) return;
                $tmp = &$tmp[$el];
            }
            if (!isset($tmp[$last_el])) {
                return;
            }
            unset($tmp[$last_el]);
        } else {
            $this->config = [];
        }
    }

    /**
     *
     */
    public function save()
    {
        file_put_contents(
            CONFIG_PATH . '/' . $this->name . '.' . CONFIG_EXT,
            json_encode($this->config, JSON_PRETTY_PRINT)
        );
    }

    /**
     *
     */
    public function dump()
    {
        var_export($this->config);
        echo ("\n");
    }
}
