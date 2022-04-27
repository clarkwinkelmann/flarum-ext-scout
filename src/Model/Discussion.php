<?php

namespace ClarkWinkelmann\Scout\Model;

use Laravel\Scout\Searchable;

class Discussion extends \Flarum\Discussion\Discussion
{
    use Searchable, CommonModelTrait {
        CommonModelTrait::bootSearchable insteadof Searchable;
        CommonModelTrait::searchableAs insteadof Searchable;
    }

    public function shouldBeSearchable(): bool
    {
        return !$this->is_private;
    }

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
        ];
    }
}
