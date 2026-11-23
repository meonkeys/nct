<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Nct\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

final class Version000001Date20261117000000 extends SimpleMigrationStep {
	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array{tablePrefix?: string} $options
	 * @psalm-suppress MethodSignatureMismatch IMigrationStep documents $options'
	 *     shape only in its own docblock, not its native signature; Psalm wants
	 *     an exact (impossible) native match instead of honoring the @param above.
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('nct_messages')) {
			$table = $schema->createTable('nct_messages');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('address', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('body', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('direction', Types::STRING, [
				'notnull' => true,
				'length' => 3,
			]);
			$table->addColumn('sent_at', Types::BIGINT, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}
}
