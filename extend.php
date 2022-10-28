<?php

namespace ClarkWinkelmann\Scout;

use ClarkWinkelmann\Scout\Extend\Scout as ScoutExtend;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event as DiscussionEvent;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Post\Event as PostEvent;
use Flarum\User\Search\UserSearcher;
use Flarum\User\User;
use Flarum\User\Event as UserEvent;
use FoF\UserBio\Event\BioChanged;
use Laravel\Scout\Console as ScoutConsole;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    (new Extend\ServiceProvider())
        ->register(ScoutServiceProvider::class),

    (new Extend\SimpleFlarumSearch(DiscussionSearcher::class))
        ->setFullTextGambit(Search\DiscussionGambit::class),
    (new Extend\SimpleFlarumSearch(UserSearcher::class))
        ->setFullTextGambit(Search\UserGambit::class),

    (new Extend\Console())
        ->command(Console\FlushCommand::class)
        ->command(Console\ImportAllCommand::class)
        ->command(Console\ImportCommand::class)
        ->command(ScoutConsole\IndexCommand::class)
        ->command(ScoutConsole\DeleteIndexCommand::class),

    (new ScoutExtend(Discussion::class))
        ->listenSaved(DiscussionEvent\Started::class, function (DiscussionEvent\Started $event) {
            return $event->discussion;
        })
        ->listenSaved(DiscussionEvent\Renamed::class, function (DiscussionEvent\Renamed $event) {
            return $event->discussion;
        })
        // Hidden/Restored events might be needed if we save it as meta in a future version
        /*->listenSaved(DiscussionEvent\Hidden::class, function (DiscussionEvent\Hidden $event) {
            return $event->discussion;
        })
        ->listenSaved(DiscussionEvent\Restored::class, function (DiscussionEvent\Restored $event) {
            return $event->discussion;
        })*/
        ->listenDeleted(DiscussionEvent\Deleted::class, function (DiscussionEvent\Deleted $event) {
            return $event->discussion;
        })
        ->attributes(function (Discussion $discussion): array {
            return [
                'id' => $discussion->id, // TNTSearch requires the ID to be part of the searchable data
                'title' => $discussion->title,
            ];
        }),
    (new ScoutExtend(Post::class))
        ->listenSaved(PostEvent\Posted::class, function (PostEvent\Posted $event) {
            return $event->post;
        })
        ->listenSaved(PostEvent\Revised::class, function (PostEvent\Revised $event) {
            return $event->post;
        })
        // Hidden/Restored events might be needed if we save it as meta in a future version
        /*->listenSaved(PostEvent\Hidden::class, function (PostEvent\Hidden $event) {
            return $event->post;
        })
        ->listenSaved(PostEvent\Restored::class, function (PostEvent\Restored $event) {
            return $event->post;
        })*/
        ->listenDeleted(PostEvent\Deleted::class, function (PostEvent\Deleted $event) {
            return $event->post;
        })
        ->searchable(function (Post $post) {
            if ($post->type !== 'comment') {
                return false;
            }
        })
        ->attributes(function (Post $post): array {
            return [
                'id' => $post->id,
            ];
        }),
    // We use a separate extender call specifically for CommentPost
    // This is both a good way to organise the code and removes the need to check for instanceof before rendering the content
    // Natively we only index comments, but an extension could make more posts searchable so this code is nicely isolated in anticipation for that
    (new ScoutExtend(CommentPost::class))
        ->attributes(function (CommentPost $post): array {
            return [
                // We use the rendered version and not unparsed version as the unparsed version might expose original text that's hidden by extensions in the output
                // strip_tags is used to strip HTML tags and their properties from the index but not provide any additional security
                'content' => strip_tags($post->formatContent()),
            ];
        }),
    (new ScoutExtend(User::class))
        ->listenSaved(UserEvent\Registered::class, function (UserEvent\Registered $event) {
            return $event->user;
        })
        ->listenDeleted(UserEvent\Deleted::class, function (UserEvent\Deleted $event) {
            return $event->user;
        })
        ->listenSaved(BioChanged::class, function (BioChanged $event) {
            return $event->user;
        })
        ->attributes(function (User $user): array {
            return [
                'id' => $user->id,
                'displayName' => $user->display_name,
                'username' => $user->username,
                // It doesn't matter if fof/user-bio is installed or not, it'll just be null if not
                'bio' => $user->bio,
            ];
        }),

    (new Extend\Event())
        ->listen(DiscussionEvent\Deleting::class, Listener\DeletingDiscussion::class),
];
