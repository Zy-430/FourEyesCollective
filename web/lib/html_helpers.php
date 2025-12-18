<?php
// Small HTML helper utilities used across pages

if (!function_exists('price')) {
    function price($amount)
    {
        return 'RM ' . number_format((float)$amount, 2);
    }
}

?>