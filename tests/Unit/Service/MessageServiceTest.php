<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Tests\Unit\Service;

use OCA\Nct\Model\Direction;
use OCA\Nct\Model\Message;
use OCA\Nct\Service\MessageService;
use PHPUnit\Framework\TestCase;

final class MessageServiceTest extends TestCase {
	public function testSendingPersistsAndReturnsAnOutgoingMessage(): void {
		$repository = new InMemoryMessageRepository();
		$gateway = new InMemoryGatewayClient();
		$service = new MessageService($repository, $gateway);

		$sent = $service->sendMessage('+15551234567', 'hi');

		$this->assertSame('+15551234567', $sent->address);
		$this->assertSame('hi', $sent->body);
		$this->assertSame(Direction::Outgoing, $sent->direction);
		$this->assertSame([$sent], $repository->messages);
		$this->assertSame([['address' => '+15551234567', 'body' => 'hi']], $gateway->sent);
	}

	public function testSendingNormalizesTheAddress(): void {
		$repository = new InMemoryMessageRepository();
		$gateway = new InMemoryGatewayClient();
		$service = new MessageService($repository, $gateway);

		$sent = $service->sendMessage('(555) 123-4567', 'hi');

		$this->assertSame('+15551234567', $sent->address);
		$this->assertSame([['address' => '+15551234567', 'body' => 'hi']], $gateway->sent);
	}

	public function testListingGroupsWhatTheRepositoryHoldsWithoutTouchingTheGateway(): void {
		$repository = new InMemoryMessageRepository();
		$repository->messages = [
			new Message('+15551234567', 'hi', Direction::Incoming, 100),
			new Message('+15551234567', 'hello', Direction::Outgoing, 200),
		];
		$gateway = new InMemoryGatewayClient();
		$gateway->toFetch = [new Message('+19999999999', 'should not appear', Direction::Incoming, 999)];
		$service = new MessageService($repository, $gateway);

		$conversations = $service->listConversations();

		$this->assertCount(1, $conversations);
		$this->assertSame('+15551234567', $conversations[0]->address);
		$this->assertCount(2, $conversations[0]->messages);
	}

	public function testSyncingPullsNewMessagesFromTheGatewayAndPersistsThem(): void {
		$repository = new InMemoryMessageRepository();
		$gateway = new InMemoryGatewayClient();
		$gateway->toFetch = [new Message('+15551234567', 'incoming', Direction::Incoming, 300)];
		$service = new MessageService($repository, $gateway);

		$synced = $service->syncInbox();

		$this->assertSame(1, $synced);
		$this->assertSame($gateway->toFetch, $repository->messages);
	}

	public function testSyncingDoesNotDuplicateMessagesAlreadyKnown(): void {
		$repository = new InMemoryMessageRepository();
		$known = new Message('+15551234567', 'already here', Direction::Incoming, 300);
		$repository->messages = [$known];
		$gateway = new InMemoryGatewayClient();
		$gateway->toFetch = [$known];
		$service = new MessageService($repository, $gateway);

		$synced = $service->syncInbox();

		$this->assertSame(0, $synced);
		$this->assertSame([$known], $repository->messages);
	}

	public function testReceivingANewMessagePersistsIt(): void {
		$repository = new InMemoryMessageRepository();
		$service = new MessageService($repository, new InMemoryGatewayClient());
		$message = new Message('+15551234567', 'pushed via webhook', Direction::Incoming, 400);

		$service->receiveMessage($message);

		$this->assertSame([$message], $repository->messages);
	}

	public function testReceivingAnAlreadyKnownMessageDoesNotDuplicateIt(): void {
		$repository = new InMemoryMessageRepository();
		$known = new Message('+15551234567', 'already here', Direction::Incoming, 300);
		$repository->messages = [$known];
		$service = new MessageService($repository, new InMemoryGatewayClient());

		$service->receiveMessage($known);

		$this->assertSame([$known], $repository->messages);
	}
}
