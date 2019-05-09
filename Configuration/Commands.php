<?php

return [
    'database:migrate:masi' => [
        'class' => \B13\Masi\Command\MigrateFieldsCommand::class,
        'schedulable' => false,
    ],
];