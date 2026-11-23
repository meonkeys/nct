<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Db;

use OCA\Nct\Model\Direction;
use OCA\Nct\Model\Message;
use OCA\Nct\Service\MessageRepository;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use Override;

/**
 * @template-extends QBMapper<MessageEntity>
 */
final class MessageMapper extends QBMapper implements MessageRepository {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'nct_messages', MessageEntity::class);
	}

	#[Override]
	public function save(Message $message): void {
		$entity = new MessageEntity();
		$entity->setAddress($message->address);
		$entity->setBody($message->body);
		$entity->setDirection($message->direction->value);
		$entity->setSentAt($message->sentAt);
		$this->insert($entity);
	}

	/** @return list<Message> */
	#[Override]
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());

		return array_map(
			fn (MessageEntity $entity) => new Message(
				$entity->getAddress(),
				$entity->getBody(),
				Direction::from($entity->getDirection()),
				$entity->getSentAt(),
			),
			$this->findEntities($qb),
		);
	}
}
