<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Service;

use OCA\Nct\Model\Message;

interface MessageRepository {
	public function save(Message $message): void;

	/** @return list<Message> */
	public function findAll(): array;
}
