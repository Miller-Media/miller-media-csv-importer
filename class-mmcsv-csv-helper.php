<?php
/**
 * A helper class for getting data from CSV files.
 *
 * @package Miller Media CSV Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * MMCSV_CSV_Helper class.
 */
class MMCSV_CSV_Helper {

	/**
	 * CSV delimiter
	 */
	const DELIMITER = ',';

	/**
	 * Open file
	 *
	 * @param string $filename File path.
	 * @param string $mode     File open mode.
	 * @return resource|false File handle or false on failure.
	 */
	public function fopen( $filename, $mode = 'r' ) {
		return fopen( $filename, $mode ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	}

	/**
	 * Get CSV line
	 *
	 * @param resource $handle File handle.
	 * @param int      $length Maximum line length.
	 * @return array|false|null Array of values or false on error.
	 */
	public function fgetcsv( $handle, $length = 0 ) {
		return fgetcsv( $handle, $length, self::DELIMITER, '"', '\\' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.runtime_configuration_fgetcsv
	}

	/**
	 * Close file
	 *
	 * @param resource $fp File handle.
	 * @return bool True on success, false on failure.
	 */
	public function fclose( $fp ) {
		return fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Parse column headers from CSV
	 *
	 * @param object $obj Importer object to store columns in.
	 * @param array  $row Column header row.
	 * @return bool False on failure, void on success.
	 */
	public function parse_columns( &$obj, $row ) {
		if ( ! is_array( $row ) || count( $row ) === 0 ) {
			return false;
		}

		// Remove UTF-8 BOM if present.
		$bom = pack( 'CCC', 0xef, 0xbb, 0xbf );
		if ( 0 === strncmp( $row[0], $bom, 3 ) ) {
			$row[0] = substr( $row[0], 3 );
		}

		$keys   = array_keys( $row );
		$values = array_values( $row );

		$obj->column_indexes = array_combine( $values, $keys );
		$obj->column_keys    = array_combine( $keys, $values );
	}

	/**
	 * Get data from CSV row by column name
	 *
	 * @param object $obj Importer object with column mappings.
	 * @param array  $row CSV row data.
	 * @param string $key Column name to retrieve.
	 * @return string|false Value from CSV or false if not found.
	 */
	public function get_data( $obj, &$row, $key ) {
		if ( ! isset( $obj->column_indexes ) || ! is_array( $row ) || count( $row ) === 0 ) {
			return false;
		}

		if ( isset( $obj->column_indexes[ $key ] ) ) {
			$index = $obj->column_indexes[ $key ];
			if ( isset( $row[ $index ] ) && ! empty( $row[ $index ] ) ) {
				$value = $row[ $index ];
				unset( $row[ $index ] );
				return $value;
			} elseif ( isset( $row[ $index ] ) ) {
				unset( $row[ $index ] );
			}
		}

		return false;
	}
}
