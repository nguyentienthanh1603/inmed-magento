<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
define('FPC_ENABLED', true);
define('FPC_LAYOUTS_DIR', 'layout');
define('FPC_BLOCKS_DIR', 'block');
define('FPC_FILE_EXTENSION', '.php');
define('FPC_DISABLE_FILE_EXTENSION', '.disabled');
define('FPC_DISABLE_FLAG_FILE', 'global' . FPC_DISABLE_FILE_EXTENSION);
define('FPC_DEFAULT_HASH_LENGTH', 7);
define('FPC_REQUEST_HASH_LENGTH', 3);
if (!defined('FPC_HOME_FILENAME')) {
    define('FPC_HOME_FILENAME', 'index');
}
if (!defined('FPC_DEFAULT_STORE')) {
    define('FPC_DEFAULT_STORE', 'default');
}

class Bubble_FPC
{
    protected $_cacheDir = null;

    protected $_store = FPC_DEFAULT_STORE;

    protected $_canUseCache = true;

    public function __construct()
    {
        $DS = DIRECTORY_SEPARATOR;
        $this->_cacheDir = dirname(__FILE__) . $DS . 'var' . $DS . 'fpc';
        if (isset($_COOKIE['store'])) {
            $this->_store = $_COOKIE['store'];
        } elseif (isset($_SERVER['MAGE_RUN_CODE']) && isset($_SERVER['MAGE_RUN_TYPE']) && $_SERVER['MAGE_RUN_TYPE'] == 'store') {
            $this->_store = $_SERVER['MAGE_RUN_CODE'];
        }
    }

    public function getBlockHtml($package, $theme, $categoryId, $pageId, $blockName, $hash)
    {
        $cookieName = str_replace('.', '_', $blockName);
        if (isset($_COOKIE[$cookieName])) {
            $hash = $_COOKIE[$cookieName];
        }
        $store = $this->_store;
        $DS = DIRECTORY_SEPARATOR;
        $dirs = str_split($hash, 1);
        $dirs[] = $package;
        $dirs[] = $theme;
        if ('' !== $categoryId || '' !== $pageId) {
            $dirs[] = $categoryId;
            $dirs[] = $pageId;
        }
        $dir = implode($DS, $dirs);
        $file = implode($DS, array(
            $this->_cacheDir,
            $store,
            FPC_BLOCKS_DIR,
            $dir,
            $blockName . FPC_FILE_EXTENSION,
        ));
        if (file_exists($file) && is_file($file)) {
            include $file;
        } else {
            $this->_canUseCache = false;
        }
    }

    public function canRun()
    {
        return empty($_POST) &&
            empty($_FILES) &&
            isset($_SERVER['HTTP_HOST']) &&
            (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') &&
            (!isset($_COOKIE['fpc']) || $_COOKIE['fpc']) &&
            !file_exists($this->_cacheDir . DIRECTORY_SEPARATOR . FPC_DISABLE_FLAG_FILE) &&
            !file_exists($this->_cacheDir . DIRECTORY_SEPARATOR . $this->_store . FPC_DISABLE_FILE_EXTENSION);
    }

    public function run()
    {
        $query = $_SERVER['QUERY_STRING'];
        $requestDirs = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        $file = array_pop($requestDirs);
        if (!$file) {
            $file = FPC_HOME_FILENAME;
        }
        $file = pathinfo($file, PATHINFO_FILENAME);
        if ($query) {
            $file .= '?' . urlencode($query);
        }
        $file .= FPC_FILE_EXTENSION;
        $layoutDirs = array();
        $hash = isset($_COOKIE['layout']) ? $_COOKIE['layout'] : '';
        $hash .= self::crypt(trim($_SERVER['REQUEST_URI'], '/'), FPC_REQUEST_HASH_LENGTH);
        if ($hash) {
            $layoutDirs = str_split($hash, 1);
        }
        $layoutDirs[] = $_SERVER['HTTP_HOST'];
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
            $layoutDirs[] = 'https';
        }
        $layoutDirs = array_merge($layoutDirs, $requestDirs);
        array_unshift($layoutDirs, FPC_LAYOUTS_DIR);
        array_unshift($layoutDirs, $this->_store);
        $DS = DIRECTORY_SEPARATOR;
        $filepath = $this->_cacheDir . $DS . implode($DS, $layoutDirs) . $DS . $file;
        if (file_exists($filepath) && is_file($filepath)) {
            ob_start();
            include $filepath;
            if ($this->_canUseCache) {
                header('Content-Type: text/html; charset=UTF-8');
                header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                header('Pragma: no-cache');
                header('Bubble-FPC: HIT');
                ob_end_flush();
                exit;
            }
            ob_clean();
        }
    }

    static public function crypt($value, $length = FPC_DEFAULT_HASH_LENGTH)
    {
        if (!is_scalar($value)) {
            $value = serialize($value);
        }

        return substr(sha1($value), 0, $length);
    }
}

$fpc = new Bubble_FPC();
if ($fpc->canRun()) {
    $fpc->run();
}