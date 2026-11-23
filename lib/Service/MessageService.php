<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Service;

use OCA\Nct\Model\Conversation;
use OCA\Nct\Model\ConversationGrouper;
use OCA\Nct\Model\Direction;
use OCA\Nct\Model\Message;
use OCA\Nct\Model\PhoneNumber;

final class MessageService {
	public function __construct(
		private readonly MessageRepository $repository,
		private readonly GatewayClient $gateway,
	) {
	}

	/** @return list<Conversation> */
	public function listConversations(): array {
		return ConversationGrouper::group($this->repository->findAll());
	}

	/**
	 * Pulls the gateway's inbox and persists anything new. Used by the
	 * background sync job.
	 *
	 * @return int how many new messages were persisted
	 */
	public function syncInbox(): int {
		$known = $this->dedupeKeysOfKnownMessages();
		$synced = 0;
		foreach ($this->gateway->fetchMessages() as $incoming) {
			if ($this->receiveIfNew($incoming, $known)) {
				$synced++;
			}
		}

		return $synced;
	}

	/**
	 * Persists a single already-built message, e.g. one pushed by the
	 * webhook receiver. Dedupes the same way syncInbox() does.
	 */
	public function receiveMessage(Message $message): void {
		$known = $this->dedupeKeysOfKnownMessages();
		$this->receiveIfNew($message, $known);
	}

	public function sendMessage(string $address, string $body): Message {
		$address = PhoneNumber::normalize($address);
		$this->gateway->send($address, $body);
		$message = new Message($address, $body, Direction::Outgoing, time());
		$this->repository->save($message);
		return $message;
	}

	/** @return array<string, true> */
	private function dedupeKeysOfKnownMessages(): array {
		$known = [];
		foreach ($this->repository->findAll() as $message) {
			$known[$this->dedupeKey($message)] = true;
		}

		return $known;
	}

	/**
	 * @param array<string, true> $known keyed by dedupeKey(), updated in place
	 */
	private function receiveIfNew(Message $message, array &$known): bool {
		$key = $this->dedupeKey($message);
		if (isset($known[$key])) {
			return false;
		}

		$this->repository->save($message);
		$known[$key] = true;
		return true;
	}

	private function dedupeKey(Message $message): string {
		return "$message->address|$message->sentAt|$message->body";
	}
}
