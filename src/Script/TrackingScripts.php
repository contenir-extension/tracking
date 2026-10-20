<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Script;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Script\ConsentCategory;
use Contenir\Extension\Api\Script\Placement;
use Contenir\Extension\Api\Script\Script;
use Contenir\Extension\Api\Script\ScriptProviderInterface;
use Contenir\Extension\Tracking\TrackingSection;
use Contenir\Tracking\Snippet\Snippet;
use Contenir\Tracking\Tags;
use Override;

use function array_filter;
use function array_map;
use function array_values;

/**
 * The site's tags as scripts for public pages (P14), each with its
 * placement and consent category, so a site adapter renders and gates them
 * with every other add-on's. Noscript fallbacks aren't scripts, so they are
 * left to the site adapters.
 *
 * @api
 */
final class TrackingScripts implements ScriptProviderInterface
{
    private static function script(Snippet $snippet): Script
    {
        $placement = Placement::from($snippet->placement->value);
        $consent   = ConsentCategory::from($snippet->category->value);

        return null === $snippet->src
            ? Script::inline((string) $snippet->inline, $placement, $consent, $snippet->attributes)
            : Script::src($snippet->src, $placement, $consent, $snippet->attributes);
    }

    #[Override]
    public function scripts(ExtensionContextInterface $context): array
    {
        $snippets = array_filter(
            (new Tags(TrackingSection::config($context->settings())))->all(),
            static fn(object $snippet): bool => $snippet instanceof Snippet,
        );

        return array_values(array_map(self::script(...), $snippets));
    }
}
