<?php

/**
 * Avisota newsletter and mailing system
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    avisota/contao-message-element-image
 * @license    LGPL-3.0+
 * @filesource
 */

namespace DoctrineMigrations\AvisotaMessageElementEvent;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20140825 extends AbstractMigration
{
	public function up(Schema $schema)
	{
		if (!$schema->hasTable('orm_avisota_message_content')) {
			return;
		}

		$table = $schema->getTable('orm_avisota_message_content');

		if (!$table->hasColumn('eventId')) {
			return;
		}

		$this->addSql('ALTER TABLE orm_avisota_message_content CHANGE eventId eventIdWithTimestamp VARCHAR(255) DEFAULT NULL');
	}

	public function down(Schema $schema)
	{
	}
}
