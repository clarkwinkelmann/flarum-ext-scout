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
            'id' => $this->id,
            // TODO: convert $this to CommentPost to use as context?
            // We use the rendered version and not unparsed version as the unparsed version might expose original text that's hidden by extensions in the output
            // strip_tags is used to strip HTML tags and their properties from the index but not provide any additional security
            'content' => strip_tags($formatter->render($this->content ?? '<t></t>', $this)),
        ];
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        // Force a skip of the Flarum post type logic because otherwise we loose the methods from the Searchable trait
        return Model::newFromBuilder($attributes, $connection);
    }
}
