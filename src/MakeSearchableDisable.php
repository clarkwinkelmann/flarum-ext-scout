<?php

namespace ClarkWinkelmann\Scout;

class MakeSearchableDisable
{
    public function __construct()
    {
        throw new \Exception('Flarum implementation does not use Scout::*Job static attributes');
    }
}
