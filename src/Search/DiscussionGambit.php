<?php

namespace ClarkWinkelmann\Scout\Search;

use ClarkWinkelmann\Scout\Model as ScoutModel;
use Flarum\Post\Post;
use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Expression;
use Laravel\Scout\Builder;

class DiscussionGambit implements GambitInterface
{
    public function apply(SearchState $search, $bit)
    {
        $discussionBuilder = resolve(Builder::class, [
            'model' => new ScoutModel\Discussion(),
            'query' => $bit,
        ]);

        $discussionIds = $discussionBuilder->keys()->all();

        $postBuilder = resolve(Builder::class, [
            'model' => new ScoutModel\Post(),
            'query' => $bit,
        ]);

        $postIds = $postBuilder->keys()->all();
        $postIdsCount = count($postIds);

        // We could replace the "where field" with "where false" everywhere when there are no IDs, but it's easier to
        // keep a FIELD() statement and just hard-code some values to prevent SQL errors
        // we know nothing will be returned anyway, so it doesn't really matter what impact it has on the query
        $postIdsSql = $postIdsCount > 0 ? str_repeat(', ?', count($postIds)) : ', 0';

        $query = $search->getQuery();
        $grammar = $query->getGrammar();

        $allMatchingPostsQuery = Post::whereVisibleTo($search->getActor())
            ->select('posts.discussion_id')
            ->selectRaw('FIELD(id' . $postIdsSql . ') as priority', $postIds)
            ->where('posts.type', 'comment')
            ->whereIn('id', $postIds);

        $bestMatchingPostQuery = Post::query()
            ->select('posts.discussion_id')
            ->selectRaw('min(matching_posts.priority) as min_priority')
            ->join(
                new Expression('(' . $allMatchingPostsQuery->toSql() . ') ' . $grammar->wrapTable('matching_posts')),
                'matching_posts.discussion_id',
                '=',
                'posts.discussion_id'
            )
            ->groupBy('posts.discussion_id')
            ->addBinding($allMatchingPostsQuery->getBindings(), 'join');

        // Code based on Flarum\Discussion\Search\Gambit\FulltextGambit
        $subquery = Post::whereVisibleTo($search->getActor())
            ->select('posts.discussion_id')
            ->selectRaw('id as most_relevant_post_id')
            ->join(
                new Expression('(' . $bestMatchingPostQuery->toSql() . ') ' . $grammar->wrapTable('best_matching_posts')),
                'best_matching_posts.discussion_id',
                '=',
                'posts.discussion_id'
            )
            ->whereIn('id', $postIds)
            ->whereRaw('FIELD(id' . $postIdsSql . ') = best_matching_posts.min_priority', $postIds)
            ->addBinding($bestMatchingPostQuery->getBindings(), 'join');

        $query
            ->where(function (\Illuminate\Database\Query\Builder $query) use ($discussionIds) {
                $query
                    ->whereNotNull('most_relevant_post_id')
                    ->orWhereIn('id', $discussionIds);
            })
            ->selectRaw('COALESCE(posts_ft.most_relevant_post_id, discussions.first_post_id) as most_relevant_post_id')
            ->leftJoin(
                new Expression('(' . $subquery->toSql() . ') ' . $grammar->wrapTable('posts_ft')),
                'posts_ft.discussion_id',
                '=',
                'discussions.id'
            )
            ->groupBy('discussions.id')
            ->addBinding($subquery->getBindings(), 'join');

        $search->setDefaultSort(function ($query) use ($postIdsSql, $postIds) {
            $query->orderByRaw('FIELD(id' . $postIdsSql . ')', $postIds);
        });
    }
}
