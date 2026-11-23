<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Command;

use OCA\Nct\Model\PhoneNumber;
use OCP\Config\IUserConfig;
use OCP\Contacts\IManager;
use OCP\IUserManager;
use OCP\IUserSession;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RefreshContacts extends Command {
	private const string APP_ID = 'nct';

	public function __construct(
		private readonly IManager $contactsManager,
		private readonly IUserConfig $userConfig,
		private readonly IUserManager $userManager,
		private readonly IUserSession $userSession,
	) {
		parent::__construct();
	}

	#[Override]
	protected function configure(): void {
		$this
			->setName('nct:refresh-contacts')
			->setDescription("Rebuilds the cached phone number -> contact name lookup used to label conversations, from the given user's personal contacts. Run after adding/editing contacts -- nct does not pick up changes on its own.")
			->addArgument('user', InputArgument::REQUIRED, 'Nextcloud user whose contacts to read');
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('user');
		$user = $this->userManager->get($uid);
		if ($user === null) {
			$output->writeln("<error>No such user: $uid</error>");

			return 1;
		}

		// The contacts app only registers a user's personal address books
		// against whoever is logged into the current session; occ has no
		// session user by default, so without this, search() would only
		// ever see the (usually empty) system address book.
		$this->userSession->setUser($user);

		$names = [];
		foreach ($this->contactsManager->search('', ['TEL']) as $contact) {
			$name = $contact['FN'] ?? null;
			if ($name === null) {
				continue;
			}

			foreach ($contact['TEL'] ?? [] as $tel) {
				$names[PhoneNumber::normalize($tel)] = $name;
			}
		}

		// Per-user and lazy: this is personal data (another user must never
		// see it), and non-lazy app/user config is preloaded into memory on
		// every single request to the whole instance, not just nct's own.
		$this->userConfig->setValueString($uid, self::APP_ID, 'contact_names', json_encode($names), lazy: true);
		$output->writeln('Cached ' . count($names) . ' phone number(s) for ' . $uid . '.');

		return 0;
	}
}
