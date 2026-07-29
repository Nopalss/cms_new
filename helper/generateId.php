<?php

function generateId($id)
{
    $micro2 = sprintf("%02d", ((int)(microtime(true) * 100)) % 100);
    return $id . date("YmdHis") . $micro2;
}
