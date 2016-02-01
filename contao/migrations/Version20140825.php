<?php

/**
 * Avisota newsletter and mailing system
 * Copyright © 2016 Sven Baumann
 *
 * PHP version 5
 *
 * @copyright  way.vision 2016
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @package    avisota/contao-message-element-image
 * @license    LGPL-3.0+
 * @filesource
 */

namespace DoctrineMigrations\AvisotaMessageElementEvent;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20140825
 *
 * @package DoctrineMigrations\AvisotaMessageElementEvent
 */
class Version20140825 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(Schema $schema)
    {
        if (!$schema->hasTable('orm_avisota_message_content')) {
            return;
        }

        $table = $schema->getTable('orm_avisota_message_content');

        if (!$table->hasColumn('eventId')) {
            return;
        }

        $this->addSql(
            'ALTER TABLE orm_avisota_message_content CHANGE eventId eventIdWithTimestamp VARCHAR(255) DEFAULT NULL'
        );
    }

    /**
     * @param Schema $schema
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function down(Schema $schema)
    {
    }
}
