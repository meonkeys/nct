<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Tests\Unit\Service;

use OCA\Nct\Model\Message;
use OCA\Nct\Service\MessageRepository;
use Override;

/**
 * Test double: a real list, so service tests don't need a database.
 */
final class InMemoryMessageRepository implements MessageRepository {
	/** @var list<Message> */
	public array $messages = [];

	#[Override]
	public function save(Message $message): void {
		$this->messages[] = $message;
	}

	#[Override]
	public function findAll(): array {
		return $this->messages;
	}
}
