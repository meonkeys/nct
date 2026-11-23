<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Gateway;

use AndroidSmsGateway\Domain\IncomingMessage;
use OCA\Nct\Model\Direction;
use OCA\Nct\Model\Message;
use OCA\Nct\Model\PhoneNumber;
use Psr\Log\LoggerInterface;

final class IncomingMessageMapper {
	public function __construct(
		private readonly LoggerInterface $logger,
	) {
	}

	public function toMessage(IncomingMessage $incoming): Message {
		$address = PhoneNumber::normalize($incoming->Sender());
		$sentAt = strtotime($incoming->CreatedAt());

		if ($sentAt === false) {
			$this->logger->error('SMSGate sent a message with an unparseable date', [
				'app' => 'nct',
				'createdAt' => $incoming->CreatedAt(),
				'sender' => $incoming->Sender(),
			]);

			// Same inputs must always produce the same stub, so MessageService's
			// dedup (by address/body/sentAt) recognizes a re-fetch of this same
			// bad record instead of piling up a new row every time.
			return new Message(
				$address,
				"error retrieving message (unparseable date: {$incoming->CreatedAt()})",
				Direction::Incoming,
				0,
			);
		}

		return new Message($address, $incoming->ContentPreview(), Direction::Incoming, $sentAt);
	}
}
