<?php

declare(strict_types=1);

namespace Lumi\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Lumi\Laravel\Client\LumiClient;

/**
 * @method static array sendEmail(array $payload)
 * @method static array getEmail(string $id)
 * @method static array listEmails(array $query = [])
 * @method static array sendBatch(array $emails)
 * @method static array listDomains()
 * @method static array getDomain(string $id)
 * @method static array createDomain(string $name)
 * @method static array verifyDomain(string $id)
 * @method static array deleteDomain(string $id)
 * @method static array listTemplates()
 * @method static array getTemplate(string $id)
 * @method static array listApiKeys()
 * @method static array createApiKey(string $name, ?string $permission = 'full_access')
 * @method static array deleteApiKey(string $id)
 * @method static bool verifyWebhookSignature(string $payload, string $header, string $secret, int $tolerance = 300)
 *
 * @see \Lumi\Laravel\Client\LumiClient
 */
class Lumi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LumiClient::class;
    }
}
