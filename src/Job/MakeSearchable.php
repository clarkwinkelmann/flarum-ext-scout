<?php

namespace ClarkWinkelmann\Scout\Job;

class MakeSearchable extends \Laravel\Scout\Jobs\MakeSearchable
{
    use SerializesAndRestoresWrappedModelIdentifiers;
}
