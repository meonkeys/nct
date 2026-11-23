<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Tests\Unit\Service;

use OCA\Nct\Model\Message;
use OCA\Nct\Service\GatewayClient;
use Override;

/**
 * Test double: no network, no phone.
 */
final class InMemoryGatewayClient implements GatewayClient {
	/** @var list<array{address: string, body: string}> */
	public array $sent = [];

	/** @var list<Message> */
	public array $toFetch = [];

	#[Override]
	public function send(string $address, string $body): void {
		$this->sent[] = ['address' => $address, 'body' => $body];
	}

	#[Override]
	public function fetchMessages(): array {
		return $this->toFetch;
	}

	#[Override]
	public function registerWebhook(string $callbackUrl): string {
		return 'webhook-id';
	}
}
