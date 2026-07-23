<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

defined('CI_DEBUG') || define('CI_DEBUG', (int) (ENVIRONMENT !== 'production'));
