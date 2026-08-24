<?php

unset($CFG);
global $CFG;
$CFG = new stdClass();

function get_moodle_setting($env_name, $secret_path = null) {
    // 1. Tenta pegar da variável de ambiente tradicional
    $val = getenv($env_name);
    if ($val !== false && $val !== '') {
        return $val;
    }

    // 2. Se não achar e houver um caminho de secret, tenta ler o arquivo
    if ($secret_path && file_exists($secret_path)) {
        $secret_val = trim(file_get_contents($secret_path));
        if ($secret_val !== '') {
            return $secret_val;
        }
    }

    return null;
}

$CFG->dbtype    = getenv('MOODLE_DBTYPE') ?: 'mariadb';
$CFG->dblibrary = getenv('MOODLE_DBLIB') ?: 'native';
$CFG->dbhost    = getenv('MOODLE_DBHOST') ?: 'db';
$CFG->dbname    = getenv('MOODLE_DBNAME') ?: 'moodle';
$CFG->prefix    = getenv('MOODLE_DBPFX') ?: 'mdl_';
$CFG->dboptions = array('dbcollation' => getenv('MOODLE_DBCOLLATION') ?: 'utf8mb4_bin');

$CFG->dbuser    = get_moodle_setting('MOODLE_DBUSER', '/run/secrets/db_user');
$CFG->dbpass    = get_moodle_setting('MOODLE_DBPASS', '/run/secrets/db_password');

$CFG->wwwroot   = getenv('MOODLE_URL') ?: 'https://develop.local';
$CFG->dataroot  = getenv('MOODLE_DATA') ?: '/var/www/moodledata';
$CFG->admin     = getenv('MOODLE_ADMIN') ?: 'admin';

$CFG->directorypermissions = 0777;
$CFG->sslproxy  = true;

// Debug: respeita MOODLE_DEBUG/MOODLE_DEBUG_DISPLAY do ambiente.
// DEVELOPER = 32767 (E_ALL); qualquer outro valor cai para NONE.
$debugenv = strtoupper((string) getenv('MOODLE_DEBUG'));
$CFG->debug = ($debugenv === 'DEVELOPER') ? 32767 : 0;
$CFG->debugdisplay = (int) (getenv('MOODLE_DEBUG_DISPLAY') ?: 0);

// Caches de JS e de strings ficam LIGADOS so quando nao estamos em DEVELOPER,
// senao as edicoes de AMD e de lang nao aparecem sem purgar cache.
$CFG->cachejs = ($debugenv !== 'DEVELOPER');
$CFG->langstringcache = ($debugenv !== 'DEVELOPER');

$CFG->preventexecpath = true;
$CFG->localcachedir = '/var/www/moodledata/localcache';

$CFG->phpunit_dataroot  = '/var/www/phpunitdata';
$CFG->phpunit_prefix = 't_';
define('PHPUNIT_LONGTEST', true);

require_once(__DIR__ . '/lib/setup.php');