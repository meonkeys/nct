<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Service;

use OCA\Nct\Model\Message;

interface GatewayClient {
	public function send(string $address, string $body): void;

	/** @return list<Message> incoming messages */
	public function fetchMessages(): array;

	/**
	 * Tells the phone to push "sms:received" events to $callbackUrl.
	 *
	 * @return string the gateway-assigned webhook ID
	 */
	public function registerWebhook(string $callbackUrl): string;
}
