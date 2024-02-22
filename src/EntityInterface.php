<?php

namespace TatTran\Repository;

interface EntityInterface
{
    public function scopeSort($query, $sort = null);
}
