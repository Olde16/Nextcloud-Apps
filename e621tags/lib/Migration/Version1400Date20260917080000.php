<?php

declare(strict_types=1);

namespace OCA\E621Tags\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1400Date20260917080000 extends SimpleMigrationStep
{
    public function changeSchema(
        IOutput $output,
        Closure $schemaClosure,
        array $options,
    ): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('e621tags_posts')) {
            $table = $schema->createTable('e621tags_posts');

            $table->addColumn(
                'id',
                Types::BIGINT,
                [
                    'autoincrement' => true,
                    'notnull' => true,
                    'unsigned' => true,
                    'length' => 20,
                ]
            );

            $table->addColumn(
                'source',
                Types::STRING,
                [
                    'notnull' => true,
                    'length' => 16,
                ]
            );

            $table->addColumn(
                'post_id',
                Types::BIGINT,
                [
                    'notnull' => true,
                    'unsigned' => true,
                    'length' => 20,
                ]
            );

            $table->addColumn(
                'updated_at',
                Types::STRING,
                [
                    'notnull' => false,
                    'length' => 64,
                ]
            );

            $table->addColumn(
                'change_seq',
                Types::BIGINT,
                [
                    'notnull' => false,
                    'unsigned' => true,
                    'length' => 20,
                ]
            );

            $table->addColumn(
                'rating',
                Types::STRING,
                [
                    'notnull' => false,
                    'length' => 32,
                ]
            );

            $table->addColumn(
                'fetched_at',
                Types::DATETIME,
                [
                    'notnull' => true,
                ]
            );

            $table->addColumn(
                'checked_at',
                Types::DATETIME,
                [
                    'notnull' => false,
                ]
            );

            /*
             * The complete API response is stored as JSON.
             * This intentionally preserves fields we do not
             * currently use, so future features can use them
             * without another API request.
             */
            $table->addColumn(
                'raw_data',
                Types::TEXT,
                [
                    'notnull' => true,
                ]
            );

            $table->setPrimaryKey(
                ['id'],
                'e621tags_posts_pkey'
            );

            $table->addUniqueIndex(
                ['source', 'post_id'],
                'e621tags_posts_uniq'
            );

            $table->addIndex(
                ['checked_at'],
                'e621tags_posts_checked'
            );

            $table->addIndex(
                ['change_seq'],
                'e621tags_posts_change'
            );
        }

        if (!$schema->hasTable('e621tags_files')) {
            $table = $schema->createTable('e621tags_files');

            $table->addColumn(
                'id',
                Types::BIGINT,
                [
                    'autoincrement' => true,
                    'notnull' => true,
                    'unsigned' => true,
                    'length' => 20,
                ]
            );

            $table->addColumn(
                'post_record_id',
                Types::BIGINT,
                [
                    'notnull' => true,
                    'unsigned' => true,
                    'length' => 20,
                ]
            );

            $table->addColumn(
                'file_id',
                Types::BIGINT,
                [
                    'notnull' => true,
                    'unsigned' => true,
                    'length' => 20,
                ]
            );

            $table->setPrimaryKey(
                ['id'],
                'e621tags_files_pkey'
            );

            $table->addUniqueIndex(
                ['post_record_id', 'file_id'],
                'e621tags_files_uniq'
            );

            $table->addIndex(
                ['file_id'],
                'e621tags_files_file'
            );

            $table->addIndex(
                ['post_record_id'],
                'e621tags_files_post'
            );
        }

        if (!$schema->hasTable('e621tags_tags')) {
            $table = $schema->createTable('e621tags_tags');

            $table->addColumn(
                'id',
                Types::BIGINT,
                [
                    'autoincrement' => true,
                    'notnull' => true,
                    'unsigned' => true,
                    'length' => 20,
                ]
            );

            $table->addColumn(
                'post_record_id',
                Types::BIGINT,
                [
                    'notnull' => true,
                    'unsigned' => true,
                    'length' => 20,
                ]
            );

            $table->addColumn(
                'group_name',
                Types::STRING,
                [
                    'notnull' => true,
                    'length' => 32,
                ]
            );

            $table->addColumn(
                'tag_name',
                Types::STRING,
                [
                    'notnull' => true,
                    'length' => 255,
                ]
            );

            $table->setPrimaryKey(
                ['id'],
                'e621tags_tags_pkey'
            );

            $table->addUniqueIndex(
                ['post_record_id', 'group_name', 'tag_name'],
                'e621tags_tags_uniq'
            );

            $table->addIndex(
                ['post_record_id'],
                'e621tags_tags_post'
            );

            $table->addIndex(
                ['tag_name'],
                'e621tags_tags_name'
            );
        }

        return $schema;
    }
}
