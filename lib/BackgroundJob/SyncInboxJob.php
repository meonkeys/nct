<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\BackgroundJob;

use OCA\Nct\Service\MessageService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

final class SyncInboxJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly MessageService $service,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(60);
	}

	#[Override]
	protected function run($argument): void {
		try {
			$synced = $this->service->syncInbox();
			if ($synced > 0) {
				$this->logger->info("nct synced $synced new message(s) from the gateway", ['app' => 'nct']);
			}
		} catch (Throwable $e) {
			$this->logger->error('nct inbox sync failed', ['app' => 'nct', 'exception' => $e]);
		}
	}
}
