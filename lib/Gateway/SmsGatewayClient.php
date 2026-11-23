<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Gateway;

use AndroidSmsGateway\Client;
use AndroidSmsGateway\Domain\MessageBuilder;
use AndroidSmsGateway\Domain\Webhook;
use AndroidSmsGateway\Enums\WebhookEvent;
use OCA\Nct\Model\Message;
use OCA\Nct\Service\GatewayClient;
use OCP\IAppConfig;
use Override;
use Psr\Http\Client\NetworkExceptionInterface;
use RuntimeException;

/**
 * Talks to the phone's SMSGate local-server API (not the cloud API): base
 * URL is just the phone's own address, e.g. http://192.168.1.50:8080, since
 * the SDK appends /messages and /inbox directly to it.
 */
final class SmsGatewayClient implements GatewayClient {
	private const string APP_ID = 'nct';

	private ?Client $client = null;

	public function __construct(
		private readonly IAppConfig $config,
		private readonly IncomingMessageMapper $mapper,
	) {
	}

	#[Override]
	public function send(string $address, string $body): void {
		$message = (new MessageBuilder($body, [$address]))->build();

		try {
			$this->client()->SendMessage($message);
		} catch (NetworkExceptionInterface $e) {
			throw new RuntimeException('SMSGate is unreachable', 0, $e);
		}
	}

	/** @return list<Message> */
	#[Override]
	public function fetchMessages(): array {
		$messages = [];
		foreach ($this->client()->ListInboxMessages() as $incoming) {
			$messages[] = $this->mapper->toMessage($incoming);
		}

		return $messages;
	}

	#[Override]
	public function registerWebhook(string $callbackUrl): string {
		$webhook = $this->client()->RegisterWebhook(new Webhook(WebhookEvent::SMS_RECEIVED(), $callbackUrl));

		return $webhook->ID() ?? '';
	}

	private function client(): Client {
		if ($this->client !== null) {
			return $this->client;
		}

		$url = $this->config->getValueString(self::APP_ID, 'gateway_url');
		$login = $this->config->getValueString(self::APP_ID, 'gateway_login');
		$password = $this->config->getValueString(self::APP_ID, 'gateway_password');

		if ($url === '' || $login === '' || $password === '') {
			throw new RuntimeException('SMSGate is not configured; set gateway_url, gateway_login, gateway_password');
		}

		return $this->client = new Client($login, $password, $url);
	}
}
