<?php
/**
 * Helper functions
 */

function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 64)
{
    return bin2hex(random_bytes($length / 2));
}

function getTimeGreeting()
{
    $hour = (int) date('H');
    if ($hour < 12) return 'buổi sáng';
    if ($hour < 18) return 'buổi chiều';
    return 'buổi tối';
}

function formatDate($date, $format = 'd/m/Y H:i')
{
    return date($format, strtotime($date));
}
