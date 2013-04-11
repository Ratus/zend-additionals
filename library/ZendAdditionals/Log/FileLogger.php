<?php
namespace ZendAdditionals\Log;

use Zend\Stdlib\ErrorHandler;
use ZendAdditionals\Stdlib\StringUtils;

/**
 * @category    ZendAdditionals
 * @package     Log
 */
class FileLogger implements \Zend\Log\LoggerInterface
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * @var array<stream>
     */
    protected $streams = array();

    /**
     * @var string
     */
    protected $logPath;

    /**
     * @var string
     */
    protected $logPrefix;

    /**
     * @param mixed $logPath    The directory where the logs should be stored
     * @param mixed $logPrefix  The prefix of the file (<prefix>-<level>-<Ymd>.log)
     */
    public function __construct($logPath, $logPrefix = 'general')
    {
        if (empty($logPrefix)) {
            throw new Exception\InvalidArgumentException("Logprefix cannot be empty!");
        }

        if (is_writable($logPath) === false) {
            throw new Exception\RuntimeException(
                "Could not log in directory {$logPath}."
            );
        }

        // Add trailing DS and normalize DS in the path
        $this->logPath = rtrim(StringUtils::normalizeDirectorySeparator(
            $logPath,
            DIRECTORY_SEPARATOR
        ), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->logPrefix = $logPrefix;
    }

    /**
     * Close all streams
     */
    public function __destruct()
    {
        // Close open streams
        foreach ($this->streams as $stream) {
            fclose($stream);
        }
    }

    /**
     * Log the entry to the file
     *
     * @param string $level
     * @param string $message
     * @param array $extra
     */
    protected function log($level, $message, $extra = array())
    {
        $file = $this->logPrefix . '-' . $level . - date('Ymd') . '.log';

        if (isset($this->streams[$file]) === false) {
            // Open new stream
            ErrorHandler::start();
            $this->streams[$file] = fopen($this->logPath . $file, 'a+');
            ErrorHandler::stop(true);
        }

        $log = '[' . date('H:i:s') . ']';
        foreach ($extra as $key => $value) {
            if (is_numeric($key)) {
                $log .= "[{$value}]";
                continue;
            }

            $log .= "[{$key}:{$value}]";
        }

        $log .= ': ' . $message . PHP_EOL;

        // Write log to file
        ErrorHandler::start();
        fwrite($this->streams[$file], $log);
        ErrorHandler::stop(true);
    }

    /**
     * {@inheritdoc}
     */
    public function emerg($message, $extra = array())
    {
        return $this->log(self::EMERGENCY, $message, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, $extra = array())
    {
        return $this->log(self::ALERT, $message, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function crit($message, $extra = array())
    {
        return $this->log(self::CRITICAL, $message, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function err($message, $extra = array())
    {
        return $this->log(self::ERROR, $message, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function warn($message, $extra = array())
    {
        return $this->log(self::WARNING, $message, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, $extra = array())
    {
        return $this->log(self::NOTICE, $message, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, $extra = array())
    {
        return $this->log(self::INFO, $message, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, $extra = array())
    {
        return $this->log(self::DEBUG, $message, $extra);
    }
}
