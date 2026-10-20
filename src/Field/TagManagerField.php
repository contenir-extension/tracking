<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Field;

use Contenir\Tracking\Provider;
use Override;

/**
 * A field holding the Tag Manager container ID (GTM-): tracking:tag-manager.
 *
 * @api
 */
final class TagManagerField extends AbstractTagIdField
{
    public const string NAME = 'tag-manager';

    #[Override]
    protected function provider(): Provider
    {
        return Provider::TagManager;
    }
}
