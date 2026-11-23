<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getAddress()
 * @method void setAddress(string $address)
 * @method string getBody()
 * @method void setBody(string $body)
 * @method string getDirection()
 * @method void setDirection(string $direction)
 * @method int getSentAt()
 * @method void setSentAt(int $sentAt)
 */
final class MessageEntity extends Entity {
	/** @var string */
	protected $address;
	/** @var string */
	protected $body;
	/** @var string */
	protected $direction;
	/** @var int */
	protected $sentAt;

	public function __construct() {
		$this->addType('sentAt', 'integer');
	}
}
