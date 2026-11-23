<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\AppInfo;

use OCA\Nct\BackgroundJob\SyncInboxJob;
use OCA\Nct\Contacts\NextcloudContactLookup;
use OCA\Nct\Db\MessageMapper;
use OCA\Nct\Gateway\SmsGatewayClient;
use OCA\Nct\Service\ContactLookup;
use OCA\Nct\Service\GatewayClient;
use OCA\Nct\Service\MessageRepository;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;

final class Application extends App implements IBootstrap {
	public const string APP_ID = 'nct';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService(MessageRepository::class, fn ($c) => $c->get(MessageMapper::class));
		$context->registerService(GatewayClient::class, fn ($c) => $c->get(SmsGatewayClient::class));
		$context->registerService(ContactLookup::class, fn ($c) => $c->get(NextcloudContactLookup::class));
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (IJobList $jobList): void {
			// IJobList::add() resets last_run/last_checked even when the job
			// already exists -- calling it on every boot() (i.e. every
			// request, including every cron.php tick) kept wiping our own
			// job's "due" state right before the scheduler checked it, so it
			// was perpetually reset and never actually ran. Only add it once.
			if (!$jobList->has(SyncInboxJob::class, null)) {
				$jobList->add(SyncInboxJob::class);
			}
		});
	}
}
