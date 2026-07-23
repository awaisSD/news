<?php

/*
 |--------------------------------------------------------------------------
 | Timezone & environment constants
 |--------------------------------------------------------------------------
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

date_default_timezone_set('UTC');

/*
 |--------------------------------------------------------------------------
 | File and Directory Modes
 |--------------------------------------------------------------------------
 */
defined('FOPEN_READ')                          || define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     || define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       || define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb');
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE')  || define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b');
defined('FOPEN_WRITE_CREATE')                   || define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              || define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            || define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       || define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
 |--------------------------------------------------------------------------
 | Exit Status Codes
 |--------------------------------------------------------------------------
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6);
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);

/*
 |--------------------------------------------------------------------------
 | Editorial workflow status constants
 |--------------------------------------------------------------------------
 | Single source of truth for the articles.status enum used across
 | ArticleWorkflow, EditorialReviewService, admin controllers, and views.
 */
defined('ARTICLE_STATUS_DRAFT')             || define('ARTICLE_STATUS_DRAFT', 'draft');
defined('ARTICLE_STATUS_IN_REVIEW')         || define('ARTICLE_STATUS_IN_REVIEW', 'in_review');
defined('ARTICLE_STATUS_CHANGES_REQUESTED') || define('ARTICLE_STATUS_CHANGES_REQUESTED', 'changes_requested');
defined('ARTICLE_STATUS_APPROVED')          || define('ARTICLE_STATUS_APPROVED', 'approved');
defined('ARTICLE_STATUS_PUBLISHED')         || define('ARTICLE_STATUS_PUBLISHED', 'published');
defined('ARTICLE_STATUS_CORRECTED')         || define('ARTICLE_STATUS_CORRECTED', 'corrected');
defined('ARTICLE_STATUS_REJECTED')          || define('ARTICLE_STATUS_REJECTED', 'rejected');
defined('ARTICLE_STATUS_RETRACTED')         || define('ARTICLE_STATUS_RETRACTED', 'retracted');
