<?php

namespace Dalee\Monolog\Handler\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Monolog\Handler\SyslogUdp\UdpSocket;
use Monolog\Logger;
use Dalee\Monolog\Handler\Syslog3164Handler;

class Syslog3164HandlerTest extends TestCase {

	/**
	 * @param $message
	 * @return array
	 */
	protected function getRecordWithMessage($message) {
		return [
			'message' => $message,
			'level' => Logger::WARNING,
			'context' => [],
			'extra' => [],
			'channel' => 'test'
		];
	}

	/**
	 * @return array
	 */
	public function facilityInvalidProvider() {
		return [[999], [-1]];
	}

	/**
	 * @return array
	 */
	public function tagInvalidProvider() {
		return [['test-'], ['invalid tag']];
	}

	public function testSetFacility() {
		$handler = new Syslog3164Handler();
		$handler->setFacility(Syslog3164Handler::FACILITY_AUDIT);

		$this->assertEquals(Syslog3164Handler::FACILITY_AUDIT, $handler->getFacility());
	}

	/**
	 * @param int $facility
	 * @dataProvider facilityInvalidProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetFacilityInvalid($facility) {
		$handler = new Syslog3164Handler();
		$handler->setFacility($facility);
	}

	/**
	 * @param string $tag
	 * @dataProvider tagInvalidProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetTagInvalid($tag) {
		$handler = new Syslog3164Handler();
		$handler->setTag($tag);
	}

	public function testSetTag() {
		$handler = new Syslog3164Handler();
		$handler->setTag('app');

		$this->assertEquals('app', $handler->getTag());
	}

	public function testSetHostnameIPv4() {
		$handler = new Syslog3164Handler();
		$handler->setHostname('192.168.55.13');

		$this->assertEquals('192.168.55.13', $handler->getHostname());
	}

	public function testSetHostnameIPv6() {
		$handler = new Syslog3164Handler();
		$handler->setHostname('2001:0db8:0000:0042:0000:8a2e:0370:7334');

		$this->assertEquals('2001:0db8:0000:0042:0000:8a2e:0370:7334', $handler->getHostname());
	}

	public function testSetHostnameDomain() {
		$handler = new Syslog3164Handler();
		$handler->setHostname('php-app.local');

		$this->assertEquals('php-app.local', $handler->getHostname());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetHostnameInvalidEmpty() {
		$handler = new Syslog3164Handler();
		$handler->setHostname('');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetHostnameInvalidFormat() {
		$handler = new Syslog3164Handler();
		$handler->setHostname('> invalid');
	}

	public function testWrite() {
		$time = 'Oct  1 20:10:20';
		$host = gethostname();

		$socket = $this->getMockBuilder(UdpSocket::class)
			->setConstructorArgs(['127.0.0.1', 514])
			->setMethods(['write'])
			->getMock();

		$socket->expects($this->once())
			->method('write')
			->with(': some test message []', "<12>$time $host php");

		$handler = $this->getMockBuilder(Syslog3164Handler::class)
			->setMethods(['getDateTime'])
			->getMock();

		$handler->method('getDateTime')
			->willReturn($time);

		$handler->setSocket($socket);
		$handler->handle($this->getRecordWithMessage('some test message'));
	}

	public function testWriteStrictTruncate() {
		$time = 'Oct  1 20:10:20';
		$host = gethostname();
		$tag = 'app';
		$message = str_pad('', Syslog3164Handler::MAX_PACKET_LENGTH * 2, 'A');

		// 2 for space delimiters - time SP hostname SP tag
		$maxMessageLength = Syslog3164Handler::MAX_PACKET_LENGTH - (
			strlen($time) + strlen($host) + strlen($tag) +
			2 + strlen('<12>') + strlen(': ')
		);

		$expectedMessage = substr($message, 0, $maxMessageLength);

		$socket = $this->getMockBuilder(UdpSocket::class)
			->setConstructorArgs(['127.0.0.1', 514])
			->setMethods(['write'])
			->getMock();

		$socket->expects($this->once())
			->method('write')
			->with(": $expectedMessage", "<12>$time $host $tag");

		$handler = $this->getMockBuilder(Syslog3164Handler::class)
			->setMethods(['getDateTime'])
			->getMock();

		$handler->method('getDateTime')
			->willReturn($time);

		$handler->setStrictSize(true)
			->setTag($tag)
			->setSocket($socket)
			->handle($this->getRecordWithMessage($message));
	}

	public function testWriteStrictLongHeader() {
		$socket = $this->getMockBuilder(UdpSocket::class)
			->setConstructorArgs(['127.0.0.1', 514])
			->setMethods(['write'])
			->getMock();

		$socket->expects($this->never())
			->method('write');

		$handler = new Syslog3164Handler();
		$tag = str_pad('', Syslog3164Handler::MAX_PACKET_LENGTH, 'A');

		set_error_handler(function () {}, E_USER_NOTICE);

		$handler->setStrictSize(true)
			->setTag($tag)
			->setSocket($socket)
			->handle($this->getRecordWithMessage('test'));

		restore_error_handler();
	}

	public function testClose() {
		$socket = $this->getMockBuilder(UdpSocket::class)
			->setConstructorArgs(['127.0.0.1', 514])
			->setMethods(['close'])
			->getMock();

		$socket->expects($this->once())
			->method('close');

		$handler = $this->getMockBuilder(Syslog3164Handler::class)
			->enableProxyingToOriginalMethods()
			->setMethods(['setSocket', 'close'])
			->getMock();

		$handler->setSocket($socket);
		$handler->close();
	}

}
