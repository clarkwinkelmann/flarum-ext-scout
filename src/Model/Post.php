<?php

namespace ClarkWinkelmann\Scout\Model;

use Flarum\Formatter\Formatter;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends \Flarum\Post\Post
{
    use Searchable, CommonModelTrait {
        CommonModelTrait::bootSearchable insteadof Searchable;
        CommonModelTrait::searchableAs insteadof Searchable;
    }

    public function shouldBeSearchable(): bool
    {
        return $this->type === 'comment' && !$this->is_private;
    }

    public function toSearchableArray(): array
    {
        /**
         * @var Formatter $formatter
         */
        $formatter = resolve(Formatter::class);

        return [
            // TODO: convert $this to CommentPost to use as context?
            'content' => $formatter->unparse($this->getOriginal('content'), $this),
        ];
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        // Force a skip of the Flarum post type logic because otherwise we loose the methods from the Searchable trait
        return Model::newFromBuilder($attributes, $connection);
    }
}
