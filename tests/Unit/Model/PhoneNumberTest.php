<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Tests\Unit\Model;

use OCA\Nct\Model\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase {
	public function testAlreadyNormalizedNumberIsUnchanged(): void {
		$this->assertSame('+999999999999', PhoneNumber::normalize('+999999999999'));
	}

	public function testBareTenDigitNumberGetsUsCountryCode(): void {
		$this->assertSame('+15555550199', PhoneNumber::normalize('5555550199'));
	}

	public function testPunctuationAndSpacesAreStripped(): void {
		$this->assertSame('+15555550199', PhoneNumber::normalize('(555) 555-0199'));
		$this->assertSame('+15555550199', PhoneNumber::normalize('1-555-555-0199'));
		$this->assertSame('+15555550199', PhoneNumber::normalize('+1 555 555 0199'));
	}

	public function testElevenDigitNumberAlreadyHasCountryCode(): void {
		$this->assertSame('+15555550199', PhoneNumber::normalize('15555550199'));
	}
}
