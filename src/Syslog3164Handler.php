<?php

namespace Dalee\Monolog\Handler;

use DateTime;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\SyslogUdp\UdpSocket;
use Monolog\Formatter\LineFormatter;

/**
 * Class Syslog3164Handler.
 *
 * @package Dalee\Monolog\Handler
 */
class Syslog3164Handler extends AbstractProcessingHandler {

	/**
	 * @var int
	 */
	const FACILITY_KERNEL = 0;

	/**
	 * @var int
	 */
	const FACILITY_USER = 1;

	/**
	 * @var int
	 */
	const FACILITY_MAIL = 2;

	/**
	 * @var int
	 */
	const FACILITY_SYSTEM = 3;

	/**
	 * @var int
	 */
	const FACILITY_SECURITY0 = 4;

	/**
	 * @var int
	 */
	const FACILITY_SECURITY = 4;

	/**
	 * @var int
	 */
	const FACILITY_SYSLOG = 5;

	/**
	 * @var int
	 */
	const FACILITY_PRINTER = 6;

	/**
	 * @var int
	 */
	const FACILITY_NEWS = 7;

	/**
	 * @var int
	 */
	const FACILITY_UUCP = 8;

	/**
	 * @var int
	 */
	const FACILITY_CLOCK0 = 9;

	/**
	 * @var int
	 */
	const FACILITY_CLOCK = 9;

	/**
	 * @var int
	 */
	const FACILITY_SECURITY1 = 10;

	/**
	 * @var int
	 */
	const FACILITY_FTP = 11;

	/**
	 * @var int
	 */
	const FACILITY_NTP = 12;

	/**
	 * @var int
	 */
	const FACILITY_AUDIT = 13;

	/**
	 * @var int
	 */
	const FACILITY_ALERT = 14;

	/**
	 * @var int
	 */
	const FACILITY_CLOCK1 = 15;

	/**
	 * @var int
	 */
	const FACILITY_LOCAL0 = 16;

	/**
	 * @var int
	 */
	const FACILITY_LOCAL1 = 17;

	/**
	 * @var int
	 */
	const FACILITY_LOCAL2 = 18;

	/**
	 * @var int
	 */
	const FACILITY_LOCAL3 = 19;

	/**
	 * @var int
	 */
	const FACILITY_LOCAL4 = 20;

	/**
	 * @var int
	 */
	const FACILITY_LOCAL5 = 21;

	/**
	 * @var int
	 */
	const FACILITY_LOCAL6 = 22;

	/**
	 * @var int
	 */
	const FACILITY_LOCAL7 = 23;

	/**
	 * @var array
	 */
	protected $logLevels = [
		Logger::DEBUG => LOG_DEBUG,
		Logger::INFO => LOG_INFO,
		Logger::NOTICE => LOG_NOTICE,
		Logger::WARNING => LOG_WARNING,
		Logger::ERROR => LOG_ERR,
		Logger::CRITICAL => LOG_CRIT,
		Logger::ALERT => LOG_ALERT,
		Logger::EMERGENCY => LOG_EMERG
	];

	/**
	 * @var int
	 */
	protected $facility;

	/**
	 * @var UdpSocket
	 */
	protected $socket;

	/**
	 * @var string
	 */
	protected $hostname = '-';

	/**
	 * @var string
	 */
	protected $application = 'php';

	/**
	 * Syslog3164Handler constructor.
	 *
	 * @param string $host
	 * @param int $port
	 * @param int $facility
	 * @param int $level
	 * @param bool $bubble
	 */
	public function __construct($host = '127.0.0.1', $port = 514, $facility = self::FACILITY_USER, $level = Logger::DEBUG, $bubble = true) {
		parent::__construct($level, $bubble);

		$this->socket = new UdpSocket($host, $port);
		$this->facility = $facility;

		if ($hostname = gethostname()) {
			$this->hostname = $hostname;
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function write(array $record) {
		$formatted = $record['formatted'];
		$severity = $this->logLevels[$record['level']];

		$header = $this->makeHeader($severity);
		$this->socket->write($formatted, $header);
	}

	/**
	 * @param int $severity
	 * @return string
	 */
	protected function makeHeader($severity) {
		$priority = $this->facility * 8 + $severity;

		$now = DateTime::createFromFormat('U.u', microtime(true));
		$date = $now->format('M j H:m:s.u');
		$date = substr($date, 0, strlen($date) - 3);

		return sprintf('<%d>%s %s %s', $priority, $date, $this->hostname, $this->application);
	}

	/**
	 * Closes UDP-socket.
	 */
	public function close() {
		$this->socket->close();
	}

	/**
	 * Gets syslog formatter without extra.
	 *
	 * @return \Monolog\Formatter\FormatterInterface
	 */
	protected function getDefaultFormatter() {
		return new LineFormatter(': %message% %context%');
	}

}
