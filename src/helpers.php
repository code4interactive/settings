<?php

if (!function_exists('array_is_assoc')) {
    /**
     * Sprawdza czy tablica jest asocjacyjna czy nie
     * @param array $arr
     * @return bool
     */
    function array_is_assoc($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}