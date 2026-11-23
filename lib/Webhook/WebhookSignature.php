<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Webhook;

final class WebhookSignature {
	private const int TOLERANCE_SECONDS = 300;

	/**
	 * Verifies a webhook request really came from SMSGate: HMAC-SHA256 over
	 * the raw body + timestamp, using the key configured on the phone
	 * (Settings -> Webhooks -> Signing Key). Also rejects stale timestamps,
	 * since signing the timestamp doesn't itself prevent a captured request
	 * being replayed later.
	 */
	public static function verify(string $rawBody, string $timestamp, string $signature, string $signingKey): bool {
		if (!ctype_digit($timestamp)) {
			return false;
		}

		if (abs(time() - (int)$timestamp) > self::TOLERANCE_SECONDS) {
			return false;
		}

		$expected = hash_hmac('sha256', $rawBody . $timestamp, $signingKey);
		return hash_equals($expected, $signature);
	}
}
