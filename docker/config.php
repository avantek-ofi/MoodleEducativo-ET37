<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dbtype = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost = 'db';
$CFG->dbname = 'moodle';
$CFG->dbuser = 'moodle';
$CFG->dbpass = getenv('POSTGRES_PASSWORD');
$CFG->prefix = 'mdl_';
$CFG->dboptions = ['dbpersist' => false, 'dbport' => '', 'dbsocket' => ''];
$CFG->wwwroot = getenv('MOODLE_URL') ?: 'http://localhost:8080';
$CFG->dataroot = '/var/moodledata';
$CFG->admin = 'admin';
$CFG->directorypermissions = 02770;
// Security: paths to executables cannot be changed from the web interface, and plugins are only
// installed from the Dockerfile (never uploaded or updated through the browser).
$CFG->preventexecpath = true;
$CFG->disableupdateautodeploy = true;
$CFG->disableonclickaddoninstall = true;
require_once(__DIR__ . '/lib/setup.php');
