<?php

namespace ClarkWinkelmann\Scout\Search;

use Flarum\Search\GambitManager;

/**
 * Flarum's original GambitManager correctly keeps quoted string together by using str_getcsv but the quotes are lost
 * which breaks all forms of exact matching in Scout drivers
 * This version keeps a similar split behaviour but original quotes are preserved
 * Quotes only create a string group if there is a whitespace (or string start/end) before AND after it
 * Quotes sandwiched between letters or incomplete groups are kept as-it and will be split on any whitespace
 *
 * Possible issue: this will also affect searchers that are not using Scout, which might not handle quotes themselves
 */
class ImprovedGambitManager extends GambitManager
{
    protected function explode($query)
    {
        $exactMatchGroups = [];

        // Use positive lookahead so the ending space is not included in the match
        // Otherwise we can't match multiple quotes following immediately after a single whitespace
        $queryWithPlaceholders = preg_replace_callback('~(^|\s)(".+?")(?=$|\s)~', function ($matches) use (&$exactMatchGroups) {
            $exactMatchGroups[] = $matches[2];

            return $matches[1] . '%%SCOUT%%EXACT%%' . (count($exactMatchGroups) - 1);
        }, $query);

        $bits = preg_split('~\s+~', $queryWithPlaceholders);

        return array_map(function ($bit) use ($exactMatchGroups) {
            if (substr($bit, 0, 16) === '%%SCOUT%%EXACT%%') {
                $groupIndex = (int)substr($bit, 16);

                // In regular operation, the group index will always exist
                // But if someone manually types the special placeholder pattern in their query we don't want to throw PHP warnings
                if (isset($exactMatchGroups[$groupIndex])) {
                    return $exactMatchGroups[$groupIndex];
                }
            }

            return $bit;
        }, $bits);
    }
}
