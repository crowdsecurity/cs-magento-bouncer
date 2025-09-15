<?php declare(strict_types=1);
/**
 * CrowdSec_Bouncer Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT LICENSE
 * that is bundled with this package in the file LICENSE
 *
 * @category   CrowdSec
 * @package    CrowdSec_Bouncer
 * @copyright  Copyright (c)  2021+ CrowdSec
 * @author     CrowdSec team
 * @see        https://crowdsec.net CrowdSec Official Website
 * @license    MIT LICENSE
 *
 */

/**
 *
 * @category CrowdSec
 * @package  CrowdSec_Bouncer
 * @module   Bouncer
 * @author   CrowdSec team
 *
 */
namespace CrowdSec\Bouncer\Logger;

use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    /**
     * @var MonologLogger The wrapped Monolog logger instance
     */
    private MonologLogger $logger;

    /**
     * Constructor
     *
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(string $name, array $handlers = [], array $processors = [])
    {
        $this->logger = new MonologLogger($name, $handlers, $processors);
    }

    /**
     * System is unusable
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Action must be taken immediately
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Critical conditions
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Normal but significant events
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Interesting events
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Detailed debug information
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Magic method to delegate calls to the wrapped logger
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->logger->$method(...$args);
    }
}
