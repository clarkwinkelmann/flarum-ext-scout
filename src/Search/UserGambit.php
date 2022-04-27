<?php

namespace ClarkWinkelmann\Scout\Search;

use ClarkWinkelmann\Scout\Model\User;
use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Laravel\Scout\Builder;

class UserGambit implements GambitInterface
{
    public function apply(SearchState $search, $bit)
    {
        $builder = resolve(Builder::class, [
            'model' => new User(),
            'query' => $bit,
        ]);

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
