<?php

function dqs_public_logo_url($isStore = false)
{
    $logoUrl = $isStore ? '../images/logo/logo.jpg' : 'images/logo/logo.jpg';
    $logoPath = dirname(__DIR__) . '/images/logo/logo.jpg';

    if (!is_file($logoPath)) {
        return $logoUrl;
    }

    $modifiedAt = @filemtime($logoPath);
    if ($modifiedAt === false) {
        return $logoUrl;
    }

    $fragment = '';
    if (strpos($logoUrl, '#') !== false) {
        list($logoUrl, $fragment) = explode('#', $logoUrl, 2);
        $fragment = '#' . $fragment;
    }

    if (preg_match('/([?&])v=[^&]*/', $logoUrl)) {
        $versionedUrl = preg_replace('/([?&])v=[^&]*/', '$1v=' . $modifiedAt, $logoUrl, 1);
    } else {
        $separator = strpos($logoUrl, '?') === false ? '?' : '&';
        $versionedUrl = $logoUrl . $separator . 'v=' . $modifiedAt;
    }

    return $versionedUrl . $fragment;
}
