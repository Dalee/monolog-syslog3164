<?php

namespace Dalee\Monolog\Handler\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Monolog\Handler\SyslogUdp\UdpSocket;
use Monolog\Logger;
use Dalee\Monolog\Handler\Syslog3164Handler;

class Syslog3164HandlerTest extends TestCase {

	/**
	 * @var Syslog3164Handler
	 */
	protected $handler;

	public function setUp() {
		$socket = $this->getMockBuilder(UdpSocket::class)
			->setConstructorArgs(['127.0.0.1', 514, Logger::DEBUG, true])
			->setMethods(['close'])
			->getMock();

		$this->handler = new Syslog3164Handler();
		$this->handler->setSocket($socket);
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
		$this->handler->setFacility(Syslog3164Handler::FACILITY_AUDIT);
		$this->assertEquals(Syslog3164Handler::FACILITY_AUDIT, $this->handler->getFacility());
	}

	/**
	 * @param int $facility
	 * @dataProvider facilityInvalidProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetFacilityInvalid($facility) {
		$this->handler->setFacility($facility);
	}

	/**
	 * @param string $tag
	 * @dataProvider tagInvalidProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetTagInvalid($tag) {
		$this->handler->setTag($tag);
	}

	public function testSetTag() {
		$this->handler->setTag('app');
		$this->assertEquals('app', $this->handler->getTag());
	}

	public function testSetHostnameIPv4() {
		$this->handler->setHostname('192.168.55.13');
		$this->assertEquals('192.168.55.13', $this->handler->getHostname());
	}

	public function testSetHostnameIPv6() {
		$this->handler->setHostname('2001:0db8:0000:0042:0000:8a2e:0370:7334');
		$this->assertEquals('2001:0db8:0000:0042:0000:8a2e:0370:7334', $this->handler->getHostname());
	}

	public function testSetHostnameDomain() {
		$this->handler->setHostname('php-app.local');
		$this->assertEquals('php-app.local', $this->handler->getHostname());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetHostnameInvalidEmpty() {
		$this->handler->setHostname('');
	}

	public function testClose() {
		$socket = $this->handler->getSocket();
		$socket->expects($this->once())->method('close');

		$this->handler->close();
	}

}
