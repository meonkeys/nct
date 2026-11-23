<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Service;

interface ContactLookup {
	/**
	 * @param string $address normalized, e.g. +999999999999
	 */
	public function nameFor(string $address): ?string;

	/**
	 * @return array<string, string> normalized address => contact name
	 */
	public function all(): array;
}
