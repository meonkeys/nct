<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Model;

/**
 * Did the message come to my phone, or go out from it?
 * An enum means a typo like 'incomming' can't sneak in at runtime.
 */
enum Direction: string {
	case Incoming = 'in';
	case Outgoing = 'out';
}
