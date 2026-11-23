<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Model;

/**
 * All messages exchanged with one phone number, oldest first.
 */
final class Conversation {
	/**
	 * @param non-empty-list<Message> $messages already sorted oldest first
	 */
	public function __construct(
		public readonly string $address,
		public readonly array $messages,
	) {
	}

	public function latest(): Message {
		return $this->messages[array_key_last($this->messages)];
	}
}
