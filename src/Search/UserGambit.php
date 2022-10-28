<?php

namespace ClarkWinkelmann\Scout\Search;

use ClarkWinkelmann\Scout\ScoutStatic;
use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Flarum\User\User;

class UserGambit implements GambitInterface
{
    public function apply(SearchState $search, $bit)
    {
        $builder = ScoutStatic::makeBuilder(User::class, $bit);

        $ids = $builder->keys();

        $search->getQuery()->whereIn('id', $ids);

        $search->setDefaultSort(function ($query) use ($ids) {
            if (!count($ids)) {
                return;
            }

            $query->orderByRaw('FIELD(id' . str_repeat(', ?', count($ids)) . ')', $ids);
        });
    }
}
