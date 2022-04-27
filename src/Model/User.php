<?php

namespace ClarkWinkelmann\Scout\Model;

use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;

class User extends \Flarum\User\User
{
    use Searchable, CommonModelTrait {
        CommonModelTrait::bootSearchable insteadof Searchable;
        CommonModelTrait::searchableAs insteadof Searchable;
    }

    public function searchIndexShouldBeUpdated()
    {
        $changes = $this->getChanges();

        // AuthenticateWithSession causes many saved events to be triggered even when the last_seen_at date didn't change
        // we'll skip all these
        if (count($changes) === 0) {
            return false;
        }

        // Additionally, also ignore every time last_seen_at is updated by AuthenticateWithSession
        if (count($changes) === 1 && Arr::exists($changes, 'last_seen_at')) {
            return false;
        }

        return true;
    }

    public function toSearchableArray(): array
    {
        return [
            'displayName' => $this->display_name,
            'username' => $this->username,
            'bio' => $this->bio,
        ];
    }
}
