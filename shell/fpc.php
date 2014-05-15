<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
require_once 'abstract.php';

class Bubble_Shell_FPC extends Mage_Shell_Abstract
{
    const VERSION = '1.2.18';

    public function run()
    {
        try {
            if ($this->getArg('generate')) {
                $start = microtime(true);
                $sitemap = $this->getArg('sitemap');
                if (!$sitemap) {
                    $this->_fault('You must specify sitemap URL with option --sitemap <url>');
                }
                $file = @file_get_contents($sitemap);
                if (!$file) {
                    $this->_fault('Sitemap not found or empty');
                }
                $urls = array();
                $xml = new SimpleXMLElement($file);
                foreach ($xml->url as $url) {
                    $urls[] = (string) $url->loc;
                }
                if (empty($urls)) {
                    $this->_fault('No URL found for cache generation');
                }
                $this->_copyright();
                $urls = array_unique($urls);
                sort($urls);
                foreach ($urls as $url) {
                    $info = $this->_get($url);
                    $this->_echo(sprintf('%d %F %s', $info['http_code'], $info['total_time'], $url));
                }
                $end = microtime(true);
                $this->_echo(sprintf('Duration: %ss', round($end - $start, 2)));
            } else {
                echo $this->usageHelp();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_echo($e->getMessage());
        }
    }

    protected function _get($url, $response = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->getArg('store')) {
            curl_setopt($ch, CURLOPT_COOKIE, 'store=' . $this->getArg('store'));
        }
        if (substr($url, 0, 5) === 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $result = curl_exec($ch);

        if ($response) {
            return $result;
        }

        $info = array();
        if (!curl_errno($ch)) {
            $info = curl_getinfo($ch);
            curl_close($ch);
        }

        return $info;
    }

    protected function _copyright()
    {
        $this->_echo('-------------------------------------------');
        $this->_echo('Bubble Full Page Caching v' . self::VERSION . ' for Magento');
        $this->_echo('(c) 2014 BubbleCode, by Johann Reinke');
        $this->_echo('http://www.bubbleshop.net');
        $this->_echo('-------------------------------------------');

        return $this;
    }

    protected function _fault($str)
    {
        $this->_echo($str);
        exit;
    }

    protected function _echo($str)
    {
        echo $str . PHP_EOL;

        return $this;
    }

    protected function _validate()
    {
        if (!Mage::isInstalled()) {
            exit('Please install magento before running this script.');
        }

        if (!Mage::helper('core')->isDevAllowed()) {
            exit('You are not allowed to run this script.');
        }

        if (!Mage::helper('core')->isModuleEnabled('Bubble_FPC')) {
            exit('Please enable Bubble_FPC module before running this script.');
        }

        if (!extension_loaded('curl')) {
            exit('This script needs cURL.');
        }

        if (!extension_loaded('simplexml')) {
            exit('This script needs SimpleXML.');
        }

        return true;
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f shell/fpc.php -- [options]

  -h                Short alias for help
  --generate        Generate cache
  --sitemap <url>   Sitemap URL
  --store <code>    Store to use
  help              This help

USAGE;
    }
}

$shell = new Bubble_Shell_FPC();
$shell->run();
