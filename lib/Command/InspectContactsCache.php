<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Command;

use OCP\Config\IUserConfig;
use OCP\IUserManager;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class InspectContactsCache extends Command {
	private const string APP_ID = 'nct';

	public function __construct(
		private readonly IUserConfig $userConfig,
		private readonly IUserManager $userManager,
	) {
		parent::__construct();
	}

	#[Override]
	protected function configure(): void {
		$this
			->setName('nct:inspect-contacts-cache')
			->setDescription("Prints the given user's cached phone number -> contact name lookup, built by occ nct:refresh-contacts. The value is lazy-loaded, so ordinary occ config/user:setting commands can't see it.")
			->addArgument('user', InputArgument::REQUIRED, 'Nextcloud user whose cache to inspect');
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('user');
		if ($this->userManager->get($uid) === null) {
			$output->writeln("<error>No such user: $uid</error>");

			return 1;
		}

		$json = $this->userConfig->getValueString($uid, self::APP_ID, 'contact_names', '', lazy: true);
		if ($json === '') {
			$output->writeln("No cache for $uid yet -- run occ nct:refresh-contacts $uid first.");

			return 0;
		}

		$names = json_decode($json, true);
		foreach ($names as $address => $name) {
			$output->writeln("$address\t$name");
		}
		$output->writeln(count($names) . ' phone number(s).');

		return 0;
	}
}
