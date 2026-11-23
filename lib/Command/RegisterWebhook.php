<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Command;

use OCA\Nct\Service\GatewayClient;
use OCP\IURLGenerator;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RegisterWebhook extends Command {
	public function __construct(
		private readonly GatewayClient $gateway,
		private readonly IURLGenerator $urlGenerator,
	) {
		parent::__construct();
	}

	#[Override]
	protected function configure(): void {
		$this
			->setName('nct:register-webhook')
			->setDescription('Tells the phone to push incoming SMS to this Nextcloud instance in real time. Set gateway_url/login/password and webhook_signing_key (from the phone\'s SMSGate app: Settings > Webhooks > Signing Key) via occ config:app:set first.');
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$callbackUrl = $this->urlGenerator->linkToRouteAbsolute('nct.webhook.receive');
		$webhookId = $this->gateway->registerWebhook($callbackUrl);

		$output->writeln("Registered webhook $webhookId for $callbackUrl");

		return 0;
	}
}
