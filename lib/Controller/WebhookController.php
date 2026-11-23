<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Controller;

use AndroidSmsGateway\Domain\IncomingMessage;
use OCA\Nct\Gateway\IncomingMessageMapper;
use OCA\Nct\Service\MessageService;
use OCA\Nct\Webhook\WebhookSignature;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Receives SMSGate's push notifications (registered via `occ
 * nct:register-webhook`). No Nextcloud session involved -- the phone calls
 * this directly, authenticated only by the HMAC signature.
 */
final class WebhookController extends Controller {
	private const string APP_ID = 'nct';

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IAppConfig $config,
		private readonly MessageService $service,
		private readonly IncomingMessageMapper $mapper,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'POST', url: '/webhook')]
	public function receive(): DataResponse {
		$rawBody = file_get_contents('php://input');
		$signingKey = $this->config->getValueString(self::APP_ID, 'webhook_signing_key');

		if (!WebhookSignature::verify(
			$rawBody,
			$this->request->getHeader('X-Timestamp'),
			$this->request->getHeader('X-Signature'),
			$signingKey,
		)) {
			$this->logger->warning('nct webhook: rejected a request with an invalid signature', ['app' => self::APP_ID]);
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		$event = json_decode($rawBody);
		if (!is_object($event) || ($event->event ?? null) !== 'sms:received') {
			// Ack anything we didn't register for (e.g. system:ping) so the
			// gateway doesn't keep retrying it.
			return new DataResponse([]);
		}

		$payload = $event->payload;
		$incoming = new IncomingMessage(
			$payload->messageId,
			'SMS',
			$payload->sender,
			$payload->message,
			$payload->receivedAt,
		);
		$this->service->receiveMessage($this->mapper->toMessage($incoming));

		return new DataResponse([]);
	}
}
