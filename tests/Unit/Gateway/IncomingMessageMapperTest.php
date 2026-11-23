<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Tests\Unit\Gateway;

use AndroidSmsGateway\Domain\IncomingMessage;
use OCA\Nct\Gateway\IncomingMessageMapper;
use OCA\Nct\Model\Direction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class IncomingMessageMapperTest extends TestCase {
	public function testMapsAWellFormedMessage(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('error');
		$mapper = new IncomingMessageMapper($logger);

		$message = $mapper->toMessage(new IncomingMessage(
			id: 'abc',
			type: 'SMS',
			sender: '(555) 555-0199',
			contentPreview: 'hi',
			createdAt: '2026-11-18T10:00:00Z',
		));

		$this->assertSame('+15555550199', $message->address);
		$this->assertSame('hi', $message->body);
		$this->assertSame(Direction::Incoming, $message->direction);
		$this->assertSame(strtotime('2026-11-18T10:00:00Z'), $message->sentAt);
	}

	public function testReplacesAnUnparseableDateWithAnErrorStubAndLogsIt(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with(
			$this->stringContains('unparseable date'),
			$this->callback(fn (array $context) => $context['createdAt'] === 'not-a-date'),
		);
		$mapper = new IncomingMessageMapper($logger);

		$message = $mapper->toMessage(new IncomingMessage(
			id: 'abc',
			type: 'SMS',
			sender: '+999999999999',
			contentPreview: 'hi',
			createdAt: 'not-a-date',
		));

		$this->assertSame('+999999999999', $message->address);
		$this->assertStringContainsString('error retrieving message', $message->body);
		$this->assertSame(Direction::Incoming, $message->direction);
	}

	public function testTwoFetchesOfTheSameBadMessageProduceIdenticalStubs(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$mapper = new IncomingMessageMapper($logger);
		$incoming = new IncomingMessage(
			id: 'abc',
			type: 'SMS',
			sender: '+999999999999',
			contentPreview: 'hi',
			createdAt: 'not-a-date',
		);

		$first = $mapper->toMessage($incoming);
		$second = $mapper->toMessage($incoming);

		// Same address/body/sentAt lets MessageService's dedup logic recognize
		// re-fetches of the same bad record instead of piling up duplicates.
		$this->assertEquals($first, $second);
	}
}
