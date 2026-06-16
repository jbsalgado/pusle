<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => $_ENV['DB_DSN'] ?? getenv('DB_DSN') ?: 'pgsql:host=localhost;port=5432;dbname=pulse',
    'username' => $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'postgres',
    'charset' => 'utf8',
    'on afterOpen' => function ($event) {
        $event->sender->createCommand("SET TIME ZONE 'America/Recife'")->execute();
    },
    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
