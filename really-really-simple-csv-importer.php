<?php
/**
 * Plugin Name: Really Really Simple CSV Importer
 * Plugin URI: https://github.com/Miller-Media/really-really-simple-csv-importer
 * Description: Import posts, pages, custom post types, categories, tags, and custom fields from a simple CSV file. Based on Really Simple CSV Importer by Takuro Hishikawa.
 * Author: Miller Media
 * Author URI: https://mattmiller.ai
 * Text Domain: really-really-simple-csv-importer
 * Domain Path: /languages
 * Version: 2.0.0
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'RRSCI_PLUGIN_VERSION', '2.0.0' );

if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
	return;
}

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) ) {
		require_once $class_wp_importer;
	}
}

// Load Helpers
require dirname( __FILE__ ) . '/class-rrsci-csv-helper.php';
require dirname( __FILE__ ) . '/class-rrsci-import-post-helper.php';

/**
 * CSV Importer
 *
 * @package Really Really Simple CSV Importer
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
	/**
	 * RRSCI_CSV_Importer class.
	 *
	 * Extends WP_Importer to provide CSV import functionality.
	 */
	class RRSCI_CSV_Importer extends WP_Importer {

		/**
		 * Sheet columns
		 *
		 * @var array
		 */
		public $column_indexes = array();

		/**
		 * Column keys
		 *
		 * @var array
		 */
		public $column_keys = array();

		/**
		 * File ID
		 *
		 * @var int
		 */
		private $id;

		/**
		 * File path
		 *
		 * @var string
		 */
		private $file;

		/**
		 * User interface wrapper start
		 */
		public function header() {
			echo '<div class="wrap">';
			echo '<h2>' . esc_html__( 'Import CSV', 'really-really-simple-csv-importer' ) . '</h2>';
		}

		/**
		 * User interface wrapper end
		 */
		public function footer() {
			echo '</div>';
		}

		/**
		 * Step 1 - Display import form
		 */
		public function greet() {
			echo '<p>' . esc_html__( 'Choose a CSV (.csv) file to upload, then click Upload file and import.', 'really-really-simple-csv-importer' ) . '</p>';
			echo '<p>' . esc_html__( 'Excel-style CSV file is unconventional and not recommended. LibreOffice has enough export options and recommended for most users.', 'really-really-simple-csv-importer' ) . '</p>';
			echo '<p>' . esc_html__( 'Requirements:', 'really-really-simple-csv-importer' ) . '</p>';
			echo '<ol>';
			echo '<li>' . esc_html__( 'Select UTF-8 as charset.', 'really-really-simple-csv-importer' ) . '</li>';
			/* translators: %s: CSV field delimiter character */
			echo '<li>' . sprintf( esc_html__( 'You must use field delimiter as "%s"', 'really-really-simple-csv-importer' ), esc_html( RRSCI_CSV_Helper::DELIMITER ) ) . '</li>';
			echo '<li>' . esc_html__( 'You must quote all text cells.', 'really-really-simple-csv-importer' ) . '</li>';
			echo '</ol>';
			echo '<p>' . esc_html__( 'Download example CSV files:', 'really-really-simple-csv-importer' );
			echo ' <a href="' . esc_url( plugin_dir_url( __FILE__ ) . 'sample/sample.csv' ) . '">' . esc_html__( 'csv', 'really-really-simple-csv-importer' ) . '</a>,';
			echo ' <a href="' . esc_url( plugin_dir_url( __FILE__ ) . 'sample/sample.ods' ) . '">' . esc_html__( 'ods', 'really-really-simple-csv-importer' ) . '</a>';
			echo ' ' . esc_html__( '(OpenDocument Spreadsheet file format for LibreOffice. Please export as csv before import)', 'really-really-simple-csv-importer' );
			echo '</p>';
			?>
			<div id="really-simple-csv-importer-form-options" style="display: none;">
				<h2><?php esc_html_e( 'Import Options', 'really-really-simple-csv-importer' ); ?></h2>
				<p><?php esc_html_e( 'Replace by post title', 'really-really-simple-csv-importer' ); ?></p>
				<label>
					<input type="radio" name="replace-by-title" value="0" checked="checked" /><?php esc_html_e( 'Disable', 'really-really-simple-csv-importer' ); ?>
				</label>
				<label>
					<input type="radio" name="replace-by-title" value="1" /><?php esc_html_e( 'Enable', 'really-really-simple-csv-importer' ); ?>
				</label>
			</div>
			<?php
			wp_import_upload_form( add_query_arg( 'step', 1 ) );
		}

		/**
		 * Step 2 - Process the uploaded file
		 */
		public function import() {
			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'really-really-simple-csv-importer' ) . '</strong><br />';
				echo esc_html( $file['error'] ) . '</p>';
				return false;
			} elseif ( ! file_exists( $file['file'] ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'really-really-simple-csv-importer' ) . '</strong><br />';
				printf(
					/* translators: %s: file path */
					esc_html__( 'The export file could not be found at %s. It is likely that this was caused by a permissions problem.', 'really-really-simple-csv-importer' ),
					'<code>' . esc_html( $file['file'] ) . '</code>'
				);
				echo '</p>';
				return false;
			}

			$this->id   = (int) $file['id'];
			$this->file = get_attached_file( $this->id );
			$result     = $this->process_posts();
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		/**
		 * Insert post and postmeta using `RRSCI_Import_Post_Helper` class.
		 *
		 * @param array  $post      Post data.
		 * @param array  $meta      Meta data.
		 * @param array  $terms     Terms data.
		 * @param string $thumbnail The uri or path of thumbnail image.
		 * @param bool   $is_update Whether this is an update operation.
		 * @return RRSCI_Import_Post_Helper
		 */
		public function save_post( $post, $meta, $terms, $thumbnail, $is_update ) {

			// Separate the post tags from $post array
			if ( isset( $post['post_tags'] ) && ! empty( $post['post_tags'] ) ) {
				$post_tags = $post['post_tags'];
				unset( $post['post_tags'] );
			}

			// Special handling of attachments
			if ( ! empty( $thumbnail ) && $post['post_type'] === 'attachment' ) {
				$post['media_file'] = $thumbnail;
				$thumbnail          = null;
			}

			// Add or update the post
			if ( $is_update ) {
				$h = RRSCI_Import_Post_Helper::getByID( $post['ID'] );
				$h->update( $post );
			} else {
				$h = RRSCI_Import_Post_Helper::add( $post );
			}

			// Set post tags
			if ( isset( $post_tags ) ) {
				$h->setPostTags( $post_tags );
			}

			// Set meta data
			$h->setMeta( $meta );

			// Set terms
			foreach ( $terms as $key => $value ) {
				$h->setObjectTerms( $key, $value );
			}

			// Add thumbnail
			if ( $thumbnail ) {
				$h->addThumbnail( $thumbnail );
			}

			return $h;
		}

		/**
		 * Process parse csv and insert posts
		 */
		public function process_posts() {
			$h = new RRSCI_CSV_Helper();

			$handle = $h->fopen( $this->file, 'r' );
			if ( false === $handle ) {
				echo '<p><strong>' . esc_html__( 'Failed to open file.', 'really-really-simple-csv-importer' ) . '</strong></p>';
				wp_import_cleanup( $this->id );
				return false;
			}

			$is_first      = true;
			$post_statuses = get_post_stati();

			echo '<ol>';

			while ( ( $data = $h->fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				if ( $is_first ) {
					$h->parse_columns( $this, $data );
					$is_first = false;
				} else {
					echo '<li>';

					$post      = array();
					$is_update = false;
					$error     = new WP_Error();

					// (string) (required) post type
					$post_type = $h->get_data( $this, $data, 'post_type' );
					if ( $post_type ) {
						if ( post_type_exists( $post_type ) ) {
							$post['post_type'] = $post_type;
						} else {
							/* translators: %s: post type slug from CSV */
							$error->add( 'post_type_exists', sprintf( esc_html__( 'Invalid post type "%s".', 'really-really-simple-csv-importer' ), esc_html( $post_type ) ) );
						}
					} else {
						echo esc_html__( 'Note: Please include post_type value if that is possible.', 'really-really-simple-csv-importer' ) . '<br>';
					}

					// (int) post id
					$post_id = $h->get_data( $this, $data, 'ID' );
					$post_id = ( $post_id ) ? $post_id : $h->get_data( $this, $data, 'post_id' );
					if ( $post_id ) {
						$post_exist = get_post( $post_id );
						if ( is_null( $post_exist ) ) { // if the post id is not exists
							$post['import_id'] = $post_id;
						} else {
							if ( ! $post_type || $post_exist->post_type === $post_type ) {
								$post['ID'] = $post_id;
								$is_update  = true;
							} else {
								/* translators: %1$d: post ID, %2$s: post type from CSV, %3$s: post type from database */
								$error->add( 'post_type_check', sprintf( esc_html__( 'The post type value from your csv file does not match the existing data in your database. post_id: %1$d, post_type(csv): %2$s, post_type(db): %3$s', 'really-really-simple-csv-importer' ), absint( $post_id ), esc_html( $post_type ), esc_html( $post_exist->post_type ) ) );
							}
						}
					}

					// (string) post title
					$post_title = $h->get_data( $this, $data, 'post_title' );
					if ( $post_title ) {

						// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in dispatch() via check_admin_referer().
						if ( ! $is_update && isset( $_POST['replace-by-title'] ) && 1 === absint( wp_unslash( $_POST['replace-by-title'] ) ) ) {
							// Try to update a post with the same title
							if ( ! $post_type ) {
								$post_type = 'post';
							}
							$existing_post = get_page_by_title( $post_title, OBJECT, $post_type );

							if ( ! is_null( $existing_post ) ) {
								$post['ID'] = $existing_post->ID;
								$is_update  = true;
							}
						}

						$post['post_title'] = $post_title;
					}

					// (string) post slug
					$post_name = $h->get_data( $this, $data, 'post_name' );
					if ( $post_name ) {
						$post['post_name'] = $post_name;
					}

					// (login or ID) post_author
					$post_author = $h->get_data( $this, $data, 'post_author' );
					if ( $post_author ) {
						if ( is_numeric( $post_author ) ) {
							$user = get_user_by( 'id', $post_author );
						} else {
							$user = get_user_by( 'login', $post_author );
						}
						if ( isset( $user ) && is_object( $user ) ) {
							$post['post_author'] = $user->ID;
							unset( $user );
						}
					}

					// user_login to post_author
					$user_login = $h->get_data( $this, $data, 'post_author_login' );
					if ( $user_login ) {
						$user = get_user_by( 'login', $user_login );
						if ( isset( $user ) && is_object( $user ) ) {
							$post['post_author'] = $user->ID;
							unset( $user );
						}
					}

					// (string) publish date
					$post_date = $h->get_data( $this, $data, 'post_date' );
					if ( $post_date ) {
						$post['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( $post_date ) );
					}
					$post_date_gmt = $h->get_data( $this, $data, 'post_date_gmt' );
					if ( $post_date_gmt ) {
						$post['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime( $post_date_gmt ) );
					}

					// (string) post status
					$post_status = $h->get_data( $this, $data, 'post_status' );
					if ( $post_status ) {
						if ( in_array( $post_status, $post_statuses, true ) ) {
							$post['post_status'] = $post_status;
						}
					}

					// (string) post password
					$post_password = $h->get_data( $this, $data, 'post_password' );
					if ( $post_password ) {
						$post['post_password'] = $post_password;
					}

					// (string) post content
					$post_content = $h->get_data( $this, $data, 'post_content' );
					if ( $post_content ) {
						$post['post_content'] = $post_content;
					}

					// (string) post excerpt
					$post_excerpt = $h->get_data( $this, $data, 'post_excerpt' );
					if ( $post_excerpt ) {
						$post['post_excerpt'] = $post_excerpt;
					}

					// (int) post parent
					$post_parent = $h->get_data( $this, $data, 'post_parent' );
					if ( $post_parent ) {
						$post['post_parent'] = absint( $post_parent );
					}

					// (int) menu order
					$menu_order = $h->get_data( $this, $data, 'menu_order' );
					if ( $menu_order ) {
						$post['menu_order'] = absint( $menu_order );
					}

					// (string) comment status
					$comment_status = $h->get_data( $this, $data, 'comment_status' );
					if ( $comment_status ) {
						$post['comment_status'] = $comment_status;
					}

					// (string, comma separated) slug of post categories
					$post_category = $h->get_data( $this, $data, 'post_category' );
					if ( $post_category ) {
						$categories = preg_split( '/,+/', $post_category );
						if ( $categories ) {
							$post['post_category'] = wp_create_categories( $categories );
						}
					}

					// (string, comma separated) name of post tags
					$post_tags = $h->get_data( $this, $data, 'post_tags' );
					if ( $post_tags ) {
						$post['post_tags'] = $post_tags;
					}

					// (string) post thumbnail image uri
					$post_thumbnail = $h->get_data( $this, $data, 'post_thumbnail' );

					$meta = array();
					$tax  = array();

					// add any other data to post meta
					foreach ( $data as $key => $value ) {
						if ( false !== $value && isset( $this->column_keys[ $key ] ) ) {
							// check if meta is custom taxonomy
							if ( substr( $this->column_keys[ $key ], 0, 4 ) === 'tax_' ) {
								// (string, comma divided) name of custom taxonomies
								$customtaxes     = preg_split( '/,+/', $value );
								$taxname         = substr( $this->column_keys[ $key ], 4 );
								$tax[ $taxname ] = array();
								foreach ( $customtaxes as $taxvalue ) {
									$tax[ $taxname ][] = $taxvalue;
								}
							} else {
								$meta[ $this->column_keys[ $key ] ] = $value;
							}
						}
					}

					/* Backward compatibility: hook name preserved from original "Really Simple CSV Importer" plugin for migration */
					/**
					 * Filter post data.
					 *
					 * @param array $post       Post data (required).
					 * @param bool  $is_update  Whether this is an update operation.
					 */
					$post = apply_filters( 'really_simple_csv_importer_save_post', $post, $is_update );

					/* Backward compatibility: hook name preserved from original "Really Simple CSV Importer" plugin for migration */
					/**
					 * Filter meta data.
					 *
					 * @param array $meta       Meta data (required).
					 * @param array $post       Post data.
					 * @param bool  $is_update  Whether this is an update operation.
					 */
					$meta = apply_filters( 'really_simple_csv_importer_save_meta', $meta, $post, $is_update );

					/* Backward compatibility: hook name preserved from original "Really Simple CSV Importer" plugin for migration */
					/**
					 * Filter taxonomy data.
					 *
					 * @param array $tax        Taxonomy data (required).
					 * @param array $post       Post data.
					 * @param bool  $is_update  Whether this is an update operation.
					 */
					$tax = apply_filters( 'really_simple_csv_importer_save_tax', $tax, $post, $is_update );

					/* Backward compatibility: hook name preserved from original "Really Simple CSV Importer" plugin for migration */
					/**
					 * Filter thumbnail URL or path.
					 *
					 * @since 1.3
					 *
					 * @param string $post_thumbnail Thumbnail URL or path (required).
					 * @param array  $post           Post data.
					 * @param bool   $is_update      Whether this is an update operation.
					 */
					$post_thumbnail = apply_filters( 'really_simple_csv_importer_save_thumbnail', $post_thumbnail, $post, $is_update );

					/* Backward compatibility: hook name preserved from original "Really Simple CSV Importer" plugin for migration */
					/**
					 * Option for dry run testing.
					 *
					 * @since 0.5.7
					 *
					 * @param bool false
					 */
					$dry_run = apply_filters( 'really_simple_csv_importer_dry_run', false );

					if ( ! $error->get_error_codes() && false === $dry_run ) {

						/* Backward compatibility: hook name preserved from original "Really Simple CSV Importer" plugin for migration */
						/**
						 * Get Alternative Importer Class name.
						 *
						 * @since 0.6
						 *
						 * @param string Class name to override Importer class. Default to null (do not override).
						 */
						$class = apply_filters( 'really_simple_csv_importer_class', null );

						// save post data
						if ( $class && class_exists( $class, false ) ) {
							$importer = new $class();
							$result   = $importer->save_post( $post, $meta, $tax, $post_thumbnail, $is_update );
						} else {
							$result = $this->save_post( $post, $meta, $tax, $post_thumbnail, $is_update );
						}

						if ( $result->isError() ) {
							$error = $result->getError();
						} else {
							$post_object = $result->getPost();

							if ( is_object( $post_object ) ) {
								/* Backward compatibility: hook name preserved from original "Really Simple CSV Importer" plugin for migration */
								/**
								 * Fires after the post imported.
								 *
								 * @since 1.0
								 *
								 * @param WP_Post $post_object The imported post object.
								 */
								do_action( 'really_simple_csv_importer_post_saved', $post_object );
							}

							echo esc_html(
								sprintf(
									/* translators: %s: post title */
									__( 'Processing "%s" done.', 'really-really-simple-csv-importer' ),
									$post_title
								)
							);
						}
					}

					// show error messages
					foreach ( $error->get_error_messages() as $message ) {
						echo esc_html( $message ) . '<br>';
					}

					echo '</li>';

					wp_cache_flush();
				}
			}

			echo '</ol>';

			$h->fclose( $handle );

			wp_import_cleanup( $this->id );

			echo '<h3>' . esc_html__( 'All Done.', 'really-really-simple-csv-importer' ) . '</h3>';
		}

		/**
		 * Dispatcher
		 */
		public function dispatch() {
			$this->header();

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- step is just a numeric selector, not sensitive data.
			$step = isset( $_GET['step'] ) ? absint( wp_unslash( $_GET['step'] ) ) : 0;

			switch ( $step ) {
				case 0:
					$this->greet();
					break;
				case 1:
					check_admin_referer( 'import-upload' );
					set_time_limit( 0 );
					$result = $this->import();
					if ( is_wp_error( $result ) ) {
						echo esc_html( $result->get_error_message() );
					}
					break;
			}

			$this->footer();
		}

	}

	/**
	 * Initialize the CSV importer and register it with WordPress.
	 */
	function rrsci_initialize_importer() {
		$csv_importer = new RRSCI_CSV_Importer();
		register_importer(
			'csv',
			__( 'CSV', 'really-really-simple-csv-importer' ),
			__( 'Import posts, categories, tags, custom fields from simple csv file.', 'really-really-simple-csv-importer' ),
			array( $csv_importer, 'dispatch' )
		);
	}
	add_action( 'plugins_loaded', 'rrsci_initialize_importer' );

	/**
	 * Enqueue admin scripts for the CSV importer page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	function rrsci_enqueue_scripts( $hook ) {
		if ( 'admin.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'rrsci_admin_script',
			plugin_dir_url( __FILE__ ) . 'auto.js',
			array(),
			RRSCI_PLUGIN_VERSION,
			true
		);
	}
	add_action( 'admin_enqueue_scripts', 'rrsci_enqueue_scripts' );

} // class_exists( 'WP_Importer' )
