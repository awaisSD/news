<?php

namespace App\Libraries\AI;

/**
 * Thrown by AI provider implementations on a non-2xx HTTP response or
 * malformed/unparseable provider output. Messages MUST NEVER include the
 * provider API key or any other secret — providers are responsible for
 * scrubbing request/response bodies before including them in an exception
 * message or log line.
 */
class AiProviderException extends \RuntimeException
{
}
