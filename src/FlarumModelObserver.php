<?php

namespace ClarkWinkelmann\Scout;

use ClarkWinkelmann\Scout\Model;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;
use Laravel\Scout\ModelObserver;

class FlarumModelObserver extends ModelObserver
{
    public function __construct()
    {
        // Override the constructor because the original calls a Facade that doesn't exist in Flarum
        $this->afterCommit = false;
        $this->usingSoftDeletes = false;
    }

    protected function convertToScoutModel(AbstractModel $model): AbstractModel
    {
        foreach ([
                     Discussion::class => Model\Discussion::class,
                     Post::class => Model\Post::class,
                     User::class => Model\User::class,
                 ] as $flarumModel => $scoutModel) {
            if ($model instanceof $flarumModel) {
                return (new $scoutModel)->cloneFlarumModel($model);
            }
        }

        return $model;
    }

    public function saved($model)
    {
        parent::saved($this->convertToScoutModel($model));
    }

    public function deleted($model)
    {
        parent::deleted($this->convertToScoutModel($model));
    }
}
