<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Tests\Unit\Webhook;

use OCA\Nct\Webhook\WebhookSignature;
use PHPUnit\Framework\TestCase;

final class WebhookSignatureTest extends TestCase {
	private const string KEY = 'signing-key';

	public function testValidSignaturePasses(): void {
		$body = '{"event":"sms:received"}';
		$timestamp = (string)time();
		$signature = hash_hmac('sha256', $body . $timestamp, self::KEY);

		$this->assertTrue(WebhookSignature::verify($body, $timestamp, $signature, self::KEY));
	}

	public function testTamperedBodyFails(): void {
		$timestamp = (string)time();
		$signature = hash_hmac('sha256', '{"event":"sms:received"}' . $timestamp, self::KEY);

		$this->assertFalse(WebhookSignature::verify('{"event":"sms:sent"}', $timestamp, $signature, self::KEY));
	}

	public function testWrongKeyFails(): void {
		$body = '{"event":"sms:received"}';
		$timestamp = (string)time();
		$signature = hash_hmac('sha256', $body . $timestamp, self::KEY);

		$this->assertFalse(WebhookSignature::verify($body, $timestamp, $signature, 'wrong-key'));
	}

	public function testStaleTimestampFails(): void {
		$body = '{"event":"sms:received"}';
		$timestamp = (string)(time() - 3600);
		$signature = hash_hmac('sha256', $body . $timestamp, self::KEY);

		$this->assertFalse(WebhookSignature::verify($body, $timestamp, $signature, self::KEY));
	}

	public function testNonNumericTimestampFails(): void {
		$body = '{"event":"sms:received"}';
		$signature = hash_hmac('sha256', $body . 'not-a-number', self::KEY);

		$this->assertFalse(WebhookSignature::verify($body, 'not-a-number', $signature, self::KEY));
	}
}
