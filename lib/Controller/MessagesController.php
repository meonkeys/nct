<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Controller;

use OCA\Nct\Service\MessageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use RuntimeException;

final class MessagesController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly MessageService $service,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/messages')]
	public function create(string $address, string $body): DataResponse {
		if ($address === '' || $body === '') {
			return new DataResponse(['error' => 'address and body are required'], Http::STATUS_BAD_REQUEST);
		}

		try {
			return new DataResponse($this->service->sendMessage($address, $body));
		} catch (RuntimeException $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_SERVICE_UNAVAILABLE);
		}
	}
}
