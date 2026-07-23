<?php

/**
 * The path constants used by the framework's bootstrap process.
 * Adjust systemDirectory / vendorDirectory if you relocate vendor/ or system/
 * on your production server.
 */
class Paths
{
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    public string $appDirectory = __DIR__ . '/..';

    public string $writableDirectory = __DIR__ . '/../../writable';

    public string $testsDirectory = __DIR__ . '/../../tests';

    public string $viewDirectory = __DIR__ . '/../Views';
}
