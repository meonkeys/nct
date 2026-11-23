<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Contacts;

use OCA\Nct\Service\ContactLookup;
use OCP\Config\IUserConfig;
use OCP\IUserSession;
use Override;

final class NextcloudContactLookup implements ContactLookup {
	private const string APP_ID = 'nct';

	/** @var array<string, string>|null */
	private ?array $names = null;

	public function __construct(
		private readonly IUserConfig $userConfig,
		private readonly IUserSession $userSession,
	) {
	}

	#[Override]
	public function nameFor(string $address): ?string {
		return $this->names()[$address] ?? null;
	}

	#[Override]
	public function all(): array {
		return $this->names();
	}

	/**
	 * Reads the cache built by `occ nct:refresh-contacts <user>` for the
	 * current session user only -- contacts are personal, so one user must
	 * never see names resolved from another user's address books.
	 *
	 * @return array<string, string>
	 */
	private function names(): array {
		if ($this->names === null) {
			$uid = $this->userSession->getUser()?->getUID();
			$json = $uid === null ? '' : $this->userConfig->getValueString($uid, self::APP_ID, 'contact_names', '', lazy: true);
			$this->names = $json === '' ? [] : json_decode($json, true);
		}

		return $this->names;
	}
}
