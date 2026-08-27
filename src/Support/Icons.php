<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Support;

/**
 * Inline Control Panel nav icons.
 *
 * Named icons are NOT portable across Statamic majors — the name resolves
 * against a different directory in each, and the sets do not overlap:
 *
 *   v4  NavItem::icon() setter  -> Statamic::svg('icons/light/'.$name)
 *   v5  NavItem::svg()          -> Statamic::svg('icons/light/'.$name)
 *   v6  NavItem::svg()          -> Statamic::svg("icons/{$name}")
 *
 * On v4 that resolution happens in the *setter*, and Statamic::svg() calls
 * File::get() with no existence guard — so a name the running version does not
 * ship throws FileNotFoundException while the nav is being built, taking down
 * every Control Panel page. The v6 names this addon used ("arrow-roadmap-path-flow",
 * "alert-warning-exclamation-mark") do not exist in the v4/v5 sets, so they are a
 * hard crash there rather than a missing glyph.
 *
 * All three majors short-circuit on Str::startsWith($value, '<svg'), so an inline
 * SVG string is the one seam that works everywhere. Each constant must therefore
 * begin with "<svg" — no leading whitespace — and must survive NavItem::sanitizeSvg().
 */
final class Icons
{
    public const REDIRECTS = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10a4 4 0 0 1 4-4h11"/><path d="m15 2 4 4-4 4"/></svg>';

    public const NOT_FOUND = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
}
