<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Model;

/**
 * Turns a flat list of messages into conversations.
 * Pure logic: no database, no Nextcloud, so it is quick and easy to test.
 */
final class ConversationGrouper {
	/**
	 * @param list<Message> $messages in any order
	 * @return list<Conversation> most recently active first
	 */
	public static function group(array $messages): array {
		// 1. Sort everything oldest first, so each bucket ends up in order too.
		usort($messages, fn (Message $a, Message $b) => $a->sentAt <=> $b->sentAt);

		// 2. Put each message in a bucket keyed by phone number.
		$byAddress = [];
		foreach ($messages as $message) {
			$byAddress[$message->address][] = $message;
		}

		// 3. One Conversation per bucket.
		$conversations = [];
		foreach ($byAddress as $address => $bucket) {
			// (string) because PHP turns numeric-looking array keys into ints.
			/** @psalm-suppress RedundantCast */
			$conversations[] = new Conversation((string)$address, $bucket);
		}

		// 4. Newest activity on top, like every messaging app.
		usort($conversations, fn (Conversation $a, Conversation $b) => $b->latest()->sentAt <=> $a->latest()->sentAt);

		return $conversations;
	}
}
