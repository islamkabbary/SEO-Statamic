<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Gateway;

use SilaSeo\Statamic\Fields\FieldResolver;
use SilaSeo\Statamic\Locale\LocaleStrategy;

/**
 * Builds the {@see StatamicGateway} used to read entries.
 *
 * There is deliberately ONE implementation rather than one per Statamic major.
 * The entire read surface this package uses -- value(), augmentedValue(), url(),
 * absoluteUrl(), locale(), site(), sites(), in(), origin(), published(), data(),
 * slug(), id(), collectionHandle() -- lives on shared traits whose composition
 * line in Entry is character-for-character identical in 4.58, 5.73 and 6.24, and
 * the few genuine deltas (lastModified()'s timezone argument, Value::value()'s
 * default substitution) do not touch anything read here. Three drivers would have
 * been three copies of one file, and the previous version-matching `match` was a
 * stub returning the same class from every branch.
 *
 * What actually varies between projects is not the Statamic version but the
 * content model -- which handles hold which meaning, and what "the same page in
 * another language" means. That lives in the field map and the locale strategy
 * passed in here. This class remains as the seam should a future major diverge.
 */
final class VersionGate
{
    public static function driver(
        ?FieldResolver $resolver = null,
        ?LocaleStrategy $locale = null,
    ): StatamicGateway {
        return new V6Driver($resolver, $locale);
    }
}
