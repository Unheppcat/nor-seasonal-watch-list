<?php

declare(strict_types=1);

namespace App\Service;

final class ExportHelper
{
    /**
     * Formats a line (passed as a fields  array) as CSV and returns the CSV as a string.
     * Adapted from http://us3.php.net/manual/en/function.fputcsv.php#87120
     *
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $encloseAll
     * @param bool $nullToMysqlNull
     * @return string
     */
    public function arrayToCsv(
        array $fields,
        string $delimiter = ',',
        string $enclosure = '"',
        bool $encloseAll = true,
        bool $nullToMysqlNull = false
    ): string {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $output = array();
        foreach ( $fields as $field ) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            /** @noinspection RegExpUnnecessaryNonCapturingGroup */
            /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
            if ( $encloseAll || preg_match( "/(?:{$delimiter_esc}|{$enclosure_esc}|\s)/", $field ) ) {
                $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, (string)$field) . $enclosure;
            }
            else {
                $output[] = $field;
            }
        }

        return implode( $delimiter, $output );
    }
}
