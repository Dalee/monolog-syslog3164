<?php

namespace Dalee\Monolog\Handler;

use DateTime;
use InvalidArgumentException;
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
	const MAX_PACKET_LENGTH = 1024;

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
	const FACILITY_SYSLOGD = 5;

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
	protected $tag = 'php';

	/**
	 * @var int
	 */
	protected $pid = 0;

	/**
	 * According to RFC, the total length of the packet must be 1024 or less.
	 * If $strictSize is false the message is sent as is otherwise it's
	 * truncated to 1024.
	 *
	 * N.B. if header is greater than 1024 bytes the message is
	 * dropped with E_NOTICE triggered.
	 *
	 * @var bool
	 */
	protected $strictSize = false;

	/**
	 * Syslog3164Handler constructor.
	 *
	 * @param string $host
	 * @param int $port
	 * @param int $level
	 * @param bool $bubble
	 */
	public function __construct($host = '127.0.0.1', $port = 514, $level = Logger::DEBUG, $bubble = true) {
		parent::__construct($level, $bubble);

		$this->facility = static::FACILITY_USER;
		$this->socket = new UdpSocket($host, $port);

		if ($hostname = gethostname()) {
			$this->hostname = $hostname;
		}
	}

	/**
	 * @return int
	 */
	public function getPid() {
		return $this->pid;
	}

	/**
	 * @param int $pid
	 * @return $this
	 */
	public function setPid($pid) {
		$this->pid = (int)$pid;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getFacility() {
		return $this->facility;
	}

	/**
	 * @param int $facility
	 * @throws InvalidArgumentException on invalid facility
	 * @return $this
	 */
	public function setFacility($facility) {
		if ($facility < static::FACILITY_KERNEL || $facility > static::FACILITY_LOCAL7) {
			throw new InvalidArgumentException("Facility {$facility} is invalid");
		}

		$this->facility = (int)$facility;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHostname() {
		return $this->hostname;
	}

	/**
	 * @param string $hostname
	 * @throws InvalidArgumentException on invalid hostname
	 * @return $this
	 */
	public function setHostname($hostname) {
		$pattern = '/(?=^.{1,254}$)(^(?:(?!\d|-)[a-z0-9\-]{1,63}(?<!-)\.)+(?:[a-z]{2,})$)/i';
		$isValidHostname = !empty($hostname);
		$isValidHostname = $isValidHostname && preg_match($pattern, $hostname);
		$isValidHostname = $isValidHostname || filter_var($hostname, FILTER_VALIDATE_IP);

		if (!$isValidHostname) {
			throw new InvalidArgumentException("Invalid hostname: {$hostname}");
		}

		$this->hostname = $hostname;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTag() {
		return $this->tag;
	}

	/**
	 * @param string $tag
	 * @throws InvalidArgumentException on invalid tag
	 * @return $this
	 */
	public function setTag($tag) {
		if (preg_match('/[^a-z0-9]/i', $tag)) {
			throw new InvalidArgumentException("Invalid tag: {$tag}");
		}

		$this->tag = $tag;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isStrictSize() {
		return $this->strictSize;
	}

	/**
	 * @param bool $strictSize
	 * @return $this
	 */
	public function setStrictSize($strictSize) {
		$this->strictSize = $strictSize;

		return $this;
	}

	/**
	 * @return UdpSocket
	 */
	public function getSocket() {
		return $this->socket;
	}

	/**
	 * @param UdpSocket $socket
	 * @return $this
	 */
	public function setSocket(UdpSocket $socket) {
		$this->socket = $socket;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	protected function write(array $record) {
		$message = $record['formatted'];
		$severity = $this->logLevels[$record['level']];
		$header = $this->makeHeader($severity);

		if ($this->strictSize) {
			$headerLength = strlen($header);
			$messageLength = strlen($message);

			if ($headerLength >= static::MAX_PACKET_LENGTH) {
				trigger_error('Packet length is over maximum size', E_USER_NOTICE);
				return;
			}

			$maxMessageLength = static::MAX_PACKET_LENGTH - $headerLength;

			if ($messageLength > $maxMessageLength) {
				$message = substr($message, 0, $maxMessageLength);
			}
		}

		$this->socket->write($message, $header);
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

		return sprintf(
			'<%d>%s %s %s%s',
			$priority, $date, $this->hostname, $this->tag,
			!empty($this->pid) ? "[{$this->pid}]" : ''
		);
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
