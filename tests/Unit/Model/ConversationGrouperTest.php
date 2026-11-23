<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Tests\Unit\Model;

use OCA\Nct\Model\ConversationGrouper;
use OCA\Nct\Model\Direction;
use OCA\Nct\Model\Message;
use PHPUnit\Framework\TestCase;

/**
 * The phone sends us a flat list of messages. The UI wants a list of
 * conversations (one per phone number), most recently active first.
 */
final class ConversationGrouperTest extends TestCase {
	public function testNoMessagesMeansNoConversations(): void {
		$this->assertSame([], ConversationGrouper::group([]));
	}

	public function testMessagesWithTheSameAddressShareAConversation(): void {
		$conversations = ConversationGrouper::group([
			new Message('+15551234567', 'hi', Direction::Incoming, 100),
			new Message('+15551234567', 'hello!', Direction::Outgoing, 200),
		]);

		$this->assertCount(1, $conversations);
		$this->assertSame('+15551234567', $conversations[0]->address);
		$this->assertCount(2, $conversations[0]->messages);
	}

	public function testMessagesInAConversationAreOldestFirst(): void {
		$conversations = ConversationGrouper::group([
			new Message('+15551234567', 'second', Direction::Incoming, 200),
			new Message('+15551234567', 'first', Direction::Outgoing, 100),
		]);

		$bodies = array_map(fn (Message $m) => $m->body, $conversations[0]->messages);
		$this->assertSame(['first', 'second'], $bodies);
	}

	public function testMostRecentlyActiveConversationComesFirst(): void {
		$conversations = ConversationGrouper::group([
			new Message('+15550000001', 'old news', Direction::Incoming, 100),
			new Message('+15550000002', 'fresh', Direction::Incoming, 300),
			new Message('+15550000001', 'still older', Direction::Outgoing, 200),
		]);

		$addresses = array_map(fn ($c) => $c->address, $conversations);
		$this->assertSame(['+15550000002', '+15550000001'], $addresses);
	}

	public function testLatestMessageIsTheNewestOne(): void {
		$conversations = ConversationGrouper::group([
			new Message('+15551234567', 'newest', Direction::Incoming, 300),
			new Message('+15551234567', 'oldest', Direction::Incoming, 100),
		]);

		$this->assertSame('newest', $conversations[0]->latest()->body);
	}
}
