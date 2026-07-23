<?php

if (! function_exists('generate_uuid4')) {
    /**
     * Dependency-free UUIDv4 generator (no ramsey/uuid installed).
     * Used anywhere a new uuid column needs populating (articles, media, users).
     */
    function generate_uuid4(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
