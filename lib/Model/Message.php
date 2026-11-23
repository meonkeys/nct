<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Model;

/**
 * One SMS. Immutable: once built, nothing can change it.
 */
final class Message {
	public function __construct(
		/** The other party's phone number (not mine). */
		public readonly string $address,
		public readonly string $body,
		public readonly Direction $direction,
		/** Unix timestamp, seconds. */
		public readonly int $sentAt,
	) {
	}
}
