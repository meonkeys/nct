<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Controller;

use OCA\Nct\Model\Conversation;
use OCA\Nct\Service\ContactLookup;
use OCA\Nct\Service\MessageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

final class InboxController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly MessageService $service,
		private readonly ContactLookup $contacts,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/inbox')]
	public function index(): DataResponse {
		$conversations = array_map(
			fn (Conversation $c) => [
				'address' => $c->address,
				'name' => $this->contacts->nameFor($c->address),
				'messages' => $c->messages,
			],
			$this->service->listConversations(),
		);

		return new DataResponse($conversations);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/contacts')]
	public function contacts(): DataResponse {
		$contacts = [];
		foreach ($this->contacts->all() as $address => $name) {
			$contacts[] = ['address' => $address, 'name' => $name];
		}

		return new DataResponse($contacts);
	}
}
