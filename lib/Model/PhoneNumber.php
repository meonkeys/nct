<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Model;

/**
 * Normalizes to +<countrycode><number>, e.g. +999999999999.
 * Assumes a bare 10-digit number is a US/Canada number missing its "1".
 */
final class PhoneNumber {
	public static function normalize(string $raw): string {
		$digits = preg_replace('/\D+/', '', $raw);

		if (strlen($digits) === 10) {
			$digits = '1' . $digits;
		}

		return '+' . $digits;
	}
}
