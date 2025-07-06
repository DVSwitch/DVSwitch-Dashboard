<?php

/* This PHP script defines (if not exists) a strftime() function that is
 * deprecated and will be removed from standard PHP functions in the future.
 * The only thing you need to do is to load the script before everything else.
 * In this way, it is possible to run older code work based on strftime()
 * function on PHP version that doesn't support it without modifying your code.
 *
 * The script uses two methods to get the text:
 *   - using shell command;
 *   - using intl IntlDateFormatter class and additional processing.
 *
 * The choice between these two methods is automatic. The first method is used
 * if the system allows execution of shell commands and is the more reliable
 * option. The second method is not complete. I'm having trouble finding
 * a solution for the %V, %g, %G, %X, %c, %x tags.
 *
 * Pavel Tzonkov (C)2023
 */

if (!function_exists('xstrftime')) {

    function xstrftime($format, $timestamp=null) {

    // PARAMETER 1 CHECK

        if (($format === null) || ($format === false))
            return false;

        if ($format === true)
            return '1';

        $type = gettype($format);

        if (preg_match('/^(array|object|resource|resource \(closed\)|unknown type)$/', $type)) {
            trigger_error('strftime() expects parameter 1 to be string, ' . $type . ' given', E_USER_WARNING);
            return false;
        }

        if (preg_match('/^(integer|double)$/', $type))
            return (string) $format;

        if ($type !== 'string')
            return false;

    // PARAMETER 2 CHECK

        $type = gettype($timestamp);

        if ($timestamp === null)
            $timestamp = time();

        elseif (
            !is_scalar($timestamp) ||
            (is_string($timestamp) && !preg_match('/^(0|[1-9]\d*)$/', $timestamp))
        ) {
            trigger_error('strftime() expects parameter 2 to be integer, ' . $type . ' given', E_USER_WARNING);
            return false;
        }

        if (!is_integer($timestamp))
            $timestamp = (int) $timestamp;

        $locale = setlocale(LC_TIME, '0');


// EASY WAY - USING SHELL TO GET DATE TEXT

        if (is_callable('shell_exec') && (stripos(ini_get('disable_functions'), 'shell_exec') === false)) {
            $cmd = 'export LC_TIME=' . escapeshellarg($locale) . '; date --date @' . escapeshellarg($timestamp) . ' +' . escapeshellarg($format);
            return preg_replace('/\r?\n$/', '', shell_exec($cmd));
        }


// HARD WAY - NOT COMPLETED

    // CHECK FORMAT

        $format = strtr($format,[
            '%r' => '%I:%M:%S %p',
            '%R' => '%H:%M',
            '%T' => '%H:%M:%S',
            '%D' => '%m/%d/%y',
            '%F' => '%Y-%m-%d'
        ]);

        $modifiers = 'aAdejuwUVWbBhmCgGyYHkIlMpPSXzZcsxnt%';
        if (!preg_match('/%[' . $modifiers . ']/', $format))
            return $format;

    // FORMAT MAP

        $map = [    // https://unicode-org.github.io/icu/userguide/format_parse/datetime/
                    // https://www.php.net/manual/en/function.strftime.php#refsect1-function.strftime-parameters

            // DAY
            '%a' => 'ccc',      // Mon - Sun
            '%A' => 'cccc',     // Monday - Sunday
            '%d' => 'dd',       // 01 - 31
            '%e' => 'd',        // 1 - 31
            '%j' => ['D'],      // 001 - 366
            '%u' => ['c'],      // 1 - 7
            '%w' => ['c'],      // 0 - 6

            // WEEK
            '%U' => ['w'],      // Week number of the given year, starting with the first Sunday as the first week
            '%V' => ['ww'],     // Week number of the given year, starting with the first week of the year with at least 4 weekdays, with Monday being the start of the week (ISO-8601:1988)
            '%W' => ['w'],      // A numeric representation of the week of the year, starting with the first Monday as the first week

            // MONTH
            '%b' => 'LLL',      // Jan - Dec
            '%B' => 'LLLL',     // January - December
            '%h' => 'LLL',      // Jan - Dec
            '%m' => 'LL',       // 01 - 12

            // YEAR
            '%C' => ['y'],      // Two digit representation of the century (year divided by 100, truncated to an integer)
            '%g' => ['yy'],     // Two digit representation of the year (ISO-8601:1988 see %V)
            '%G' => ['y'],      // Full digit representation of the year (ISO-8601:1988 see %V)
            '%y' => 'yy',       // Two digit representation of the year
            '%Y' => 'y',        // Full digit representation of the year

            // TIME
            '%H' => 'HH',       // Hour 00 - 23
            '%k' => 'H',        // Hour 0 - 23
            '%I' => 'hh',       // Hour 01 - 12
            '%l' => 'h',        // Hour 1 - 12
            '%M' => 'mm',       // Minutes 00 - 59
            '%p' => [],         // AM / PM
            '%P' => [],         // am / pm
            '%S' => 'ss',       // Seconds 00 - 59
            '%X' => [],         // Preferred time representation based on locale, without the date. Example: 03:59:16 or 15:59:16
            '%z' => 'Z',        // Time zone -0500 for US Eastern Time
            '%Z' => 'z',        // Time zone EST for Eastern Time

            // TIME AND DATA STAMPS
            '%c' => [],         // Preferred date and time stamp based on locale. Example: Tue Feb 5 00:45:10 2009
            '%s' => [],         // Unix Epoch Time timestamp (same as the time() function)
            '%x' => [],         // Preferred date representation based on locale, without the time. Example: 02/05/09

            // MISCELLANEOUS
            '%n' => [],         // \n
            '%t' => [],         // \t
            '%%' => []          // %
        ];

        $timezone = date_default_timezone_get();

        $return = '';

        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {

            $current_char = $format[$i];
            $next_char = $i < $length - 1 ? $format[$i + 1] : false;

            // NORMAL TEXT
            if ($current_char !== '%') {
                $return .= $current_char;
                continue;
            }

            // MODIFIER
            else {

                // LAST CHARACTER
                if ($next_char === false) {
                    $return .= '%';
                    continue;
                }

                $fmt = $current_char . $next_char;
                $i++;

                // NOT FOUND
                if (!isset($map[$fmt])) {
                    $return .= $fmt;
                    continue;
                }

                // SIMPLE MODIFIER
                if (is_string($map[$fmt])) {
                    $return .= datefmt_format(datefmt_create(
                        $locale,
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::FULL,
                        $timezone,
                        IntlDateFormatter::GREGORIAN,
                        $map[$fmt]
                    ), $timestamp);
                    continue;
                }

                // SPECIAL MODIFIERS
                if (!empty($map['fmt']))
                    $str = datefmt_format(datefmt_create(
                        $locale,
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::FULL,
                        $timezone,
                        IntlDateFormatter::GREGORIAN,
                        $map[$fmt][0]
                    ), $timestamp);

                if ($fmt == '%j')
                    $return .= sprintf("%03d", $str);

                elseif ($fmt == '%u')
                    $return .= (--$str ? $str : '7');

                elseif ($fmt == '%w')
                    $return .= --$str;

                elseif ($fmt == '%U') {

                }

                elseif ($fmt == '%V') {

                }

                elseif ($fmt == '%W') {

                }

                elseif ($fmt == '%C')
                    $return .= (string) floor($str / 100);

                elseif ($fmt == '%g') {

                }

                elseif ($fmt == '%G') {

                }

                elseif (($fmt == '%p') || ($fmt == '%P')) {
                    $str = datefmt_format(datefmt_create(
                        'en_US',
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::FULL,
                        $timezone,
                        IntlDateFormatter::GREGORIAN,
                        'a'
                    ), $timestamp);
                    $return .= ($fmt == '%p') ? strtoupper($str) : strtolower($str);
                }

                elseif ($fmt == '%X') {

                }

                elseif ($fmt == '%c') {

                }

                elseif ($fmt == '%s')
                    $return .= $timestamp;

                elseif ($fmt == '%x') {

                }

                elseif ($fmt == '%n')
                    $return .= "\n";

                elseif ($fmt == '%t')
                    $return .= "\t";

                elseif ($fmt == '%%')
                    $return .= '%';

                else
                    $return .= $fmt;

                continue;
            }

        }

        return $return;
    }


}

