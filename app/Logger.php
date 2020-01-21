<?php

namespace App\Logger;

use DateTime;
use Exception;
use RuntimeException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use App\Traits\ApiResponser;
use Illuminate\Http\Response;


class Logger extends AbstractLogger
{

    use ApiResponser;

    /**
     * log file path
     * @var string
     */
    private $logFilePath;

    /**
     * Threshold
     * @var integer
     */
    protected $logLevelThreshold = LogLevel::DEBUG;

    /**
     * The number of lines logged
     * @var int
     */
    private $logLineCount = 0;

    /**
     * Log Levels
     * @var array
     */
    protected $logLevels = array(
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7
    );

    /**
     * Logger options
     *
     *  Core options include the log file path and the log threshold these options can be overridden from the .env files also   
     *
     * @var array
     */
    protected $options = array(
        'extension' => 'txt',
        'dateFormat' => 'Y-m-d G:i:s.u',
        'filename' => false,
        'flushFrequency' => false,
        'prefix' => 'log_',
        'logFormat' => false,
        'appendContext' => true,
    );
    
  

    /**
     * This holds the file handle for this instance's log file
     * @var resource
     */
    private $fileHandle;

    /**
     * This holds the last line logged to the logger
     *  Used for unit tests
     * @var string
     */
    private $lastLine = '';

    /**
     * Default permissions of the log file
     * @var integer
     */
    private $defaultPermissions = 0777;

    /**
     * Class constructor
     *
     * @param string $logDirectory      File path to the logging directory
     * @param string $logLevelThreshold The LogLevel Threshold
     * @param array  $options
     *
     * @internal param string $logFilePrefix The prefix for the log file name
     * @internal param string $logFileExt The extension for the log file
     */
    public function __construct($logDirectory, $logLevelThreshold = LogLevel::DEBUG, array $options = array())
    {
        $this->logLevelThreshold = $logLevelThreshold;
        $this->options = array_merge($this->options, $options);

        $logDirectory = rtrim($logDirectory, DIRECTORY_SEPARATOR);
        if (!file_exists($logDirectory)) {
            mkdir($logDirectory, $this->defaultPermissions, true);
        }

        if (strpos($logDirectory, 'php://') === 0) {
            $this->setLogToStdOut($logDirectory);
            $this->setFileHandle('w+');
        } else {
            $this->setLogFilePath($logDirectory);
            if (file_exists($this->logFilePath) && !is_writable($this->logFilePath)) {
                throw new RuntimeException("The file at location [{$this->logFilePath }] could not be written to. Check that appropriate permissions have been set.", Response::HTTP_UNAUTHORIZED);
            }
            $this->setFileHandle('a');
        }

        if (!$this->fileHandle) {
            throw new RuntimeException('The file could not be opened. Check permissions.', Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @param string $stdOutPath
     */
    public function setLogToStdOut($stdOutPath)
    {
        $this->logFilePath = $stdOutPath;
    }

    /**
     * @param string $logDirectory
     */
    public function setLogFilePath($logDirectory)
    {
        if ($this->options['filename']) {
            if (strpos($this->options['filename'], '.log') !== false || strpos($this->options['filename'], '.txt') !== false) {
                $this->logFilePath = $logDirectory . DIRECTORY_SEPARATOR . $this->options['filename'];
            } else {
                $this->logFilePath = $logDirectory . DIRECTORY_SEPARATOR . $this->options['filename'] . '.' . $this->options['extension'];
            }
        } else {
            $this->logFilePath = $logDirectory . DIRECTORY_SEPARATOR . $this->options['prefix'] . date('Y-m-d') . '.' . $this->options['extension'];
        }
    }

    /**
     * @param $writeMode
     *
     * @internal param resource $fileHandle
     */
    public function setFileHandle($writeMode)
    {
        try {
            $this->fileHandle = fopen($this->logFilePath, $writeMode);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }

    /**
     * Sets the date format used by all instances of Logger
     *
     * @param string $dateFormat Valid format string for date()
     */
    public function setDateFormat($dateFormat)
    {
        $this->options['dateFormat'] = $dateFormat;
    }

    /**
     * Sets the Log Level Threshold
     *
     * @param string $logLevelThreshold The log level threshold
     */
    public function setLogLevelThreshold($logLevelThreshold)
    {
        $this->logLevelThreshold = $logLevelThreshold;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if ($this->logLevels[$this->logLevelThreshold] < $this->logLevels[$level]) {
            return;
        }
        $message = $this->formatMessage($level, $message, $context);

        return $this->write($message);
    }

    /**
     * Writes a line to the log without prepending a status or timestamp
     *
     * @param string $message Line to write to the log
     * @return void
     */
    public function write($message)
    {

        if (null !== $this->fileHandle) {
            if (fwrite($this->fileHandle, $message) === false) {
                throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.', Response::HTTP_UNAUTHORIZED);
            } else {
                $this->lastLine = trim($message);
                $this->logLineCount++;

                if ($this->options['flushFrequency'] && $this->logLineCount % $this->options['flushFrequency'] === 0) {
                    fflush($this->fileHandle);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Get the file path that the log is currently writing to
     *
     * @return string
     */
    public function getLogFilePath()
    {
        return $this->logFilePath;
    }

    /**
     * Get the last line logged to the log file
     *
     * @return string
     */
    public function getLastLogLine()
    {
        return $this->lastLine;
    }

    /**
     * Formats the message for logging.
     *
     * @param  string $level   The Log Level of the message
     * @param  string $message The message to log
     * @param  array  $context The context
     * @return string
     */
    protected function formatMessage($level, $message, $context)
    {
        if ($this->options['logFormat']) {
            $parts = array(
                'date' => $this->getTimestamp(),
                'level' => strtoupper($level),
                'level-padding' => str_repeat(' ', 9 - strlen($level)),
                'priority' => $this->logLevels[$level],
                'message' => $message,
                'context' => json_encode($context),
            );
            // log format like $logFormat = "[{date}]\t[{level}]\t{message}";
            $message = $this->options['logFormat'];
            foreach ($parts as $part => $value) {
                $message = str_replace('{' . $part . '}', $value, $message);
            }
        } else {
            $message = "[{$this->getTimestamp()}] [{$level}] {$message}";
        }

        if ($this->options['appendContext'] && !empty($context)) {
            $message .= PHP_EOL . $this->indent($this->contextToString($context));
        }

        return $message . PHP_EOL;
    }

    /**
     * Gets the correctly formatted Date/Time for the log entry.
     *
     * PHP DateTime trickery to get microseconds
     * to work correctly
     *
     * @return string
     */
    private function getTimestamp()
    {
        $originalTime = microtime(true);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $originalTime));

        return $date->format($this->options['dateFormat']);
    }

    /**
     * Takes the given context and coverts it to a string.
     *
     * @param  array $context The Context
     * @return string
     */
    protected function contextToString($context)
    {
        $export = '';
        foreach ($context as $key => $value) {
            $export .= "{$key}: ";
            $export .= preg_replace(array(
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m'
                    ), array(
                '=> $1',
                'array()',
                '    '
                    ), str_replace('array (', 'array(', var_export($value, true)));
            $export .= PHP_EOL;
        }
        return str_replace(array('\\\\', '\\\''), array('\\', '\''), rtrim($export));
    }

    // Overiding the

    /**
     * Indents the given string with the given indent.
     *
     * @param  string $string The string to indent
     * @param  string $indent What to use as the indent.
     * @return string
     */
    protected function indent($string, $indent = '    ')
    {
        return $indent . str_replace("\n", "\n" . $indent, $string);
    }


    /**
     * Overriding AbstractLogger class for return values
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    
    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        return $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        return $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        return $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        return $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        return $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        return $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        return $this->log(LogLevel::DEBUG, $message, $context);
    }

}
