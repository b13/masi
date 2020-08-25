<?php

/**
 * Registering Upgrade Wizards
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['masiMigrateRealUrlExclude']
    = \B13\Masi\Updates\MigrateRealUrlExcludeField::class;
