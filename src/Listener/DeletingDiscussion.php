<?php

namespace ClarkWinkelmann\Scout\Listener;

use Flarum\Discussion\Event\Deleting;

class DeletingDiscussion
{
    public function handle(Deleting $event)
    {
        // Retrieve list of post models before the deletion actually occurs
        $posts = $event->discussion->posts;

        // Flarum doesn't dispatch the Deleted event for each post when deleting a discussion, so we'll hook into it manually
        // This is also an opportunity to delete all posts from the index in a single job/request instead of one by one
        $event->discussion->afterDelete(function () use ($posts) {
            $posts->unsearchable();
        });
    }
}
