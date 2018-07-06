<?php

declare(strict_types=1);

namespace Contelizer\SyliusSubscriptionPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ContelizerSyliusSubscriptionPlugin extends Bundle
{
    use SyliusPluginTrait;
}
