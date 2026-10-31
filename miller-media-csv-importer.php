<?php
/**
 * Plugin Name: Miller Media CSV Importer
 * Plugin URI: https://github.com/Miller-Media/miller-media-csv-importer
 * Description: Import posts, pages, custom post types, categories, tags, and custom fields from a simple CSV file. Based on Really Simple CSV Importer by Takuro Hishikawa.
 * Author: Miller Media
 * Author URI: https://mattmiller.ai
 * Text Domain: miller-media-csv-importer
 * Domain Path: /languages
 * Version: 2.0.2
 * Requires at least: 5.0
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Miller Media CSV Importer
 */

// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName,Universal.Files.SeparateFunctionsFromOO.Mixed -- Main plugin bootstrap file: the filename must match the plugin slug (not the class name), and mixing small bootstrap functions with the importer class in one file is the standard single-file WP_Importer plugin layout.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'MMCSV_PLUGIN_VERSION', '2.0.2' );

/**
 * Unique importer id used to register with WordPress and to build admin links.
 *
 * A generic id such as "csv" can collide with other CSV importer plugins
 * (including the original Really Simple CSV Importer this plugin is based
 * on), silently hiding one importer behind the other. Using a unique id
 * avoids that collision.
 */
define( 'MMCSV_IMPORTER_ID', 'miller-media-csv-importer' );

/**
 * Flag a one-time admin notice to show after activation.
 */
function mmcsv_activate() {
	add_option( 'mmcsv_show_activation_notice', 1 );
}
register_activation_hook( __FILE__, 'mmcsv_activate' );

/**
 * Show a dismissible, one-time notice pointing admins to the importer screen.
 */
function mmcsv_activation_notice() {
	if ( ! get_option( 'mmcsv_show_activation_notice' ) || ! current_user_can( 'import' ) ) {
		return;
	}

	delete_option( 'mmcsv_show_activation_notice' );

	echo '<div class="notice notice-info is-dismissible"><p>';
	printf(
		/* translators: %s: link to the Tools > Import > Miller Media CSV Importer screen. */
		esc_html__( 'Miller Media CSV Importer is ready. Go to %s to upload a CSV file.', 'miller-media-csv-importer' ),
		'<a href="' . esc_url( admin_url( 'admin.php?import=' . MMCSV_IMPORTER_ID ) ) . '">' . esc_html__( 'Tools &rarr; Import &rarr; Miller Media CSV Importer', 'miller-media-csv-importer' ) . '</a>'
	);
	echo '</p></div>';
}
add_action( 'admin_notices', 'mmcsv_activation_notice' );

/**
 * Add a direct "Import CSV" link to this plugin's row on the Plugins screen.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function mmcsv_plugin_action_links( $links ) {
	$import_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?import=' . MMCSV_IMPORTER_ID ) ),
		esc_html__( 'Import CSV', 'miller-media-csv-importer' )
	);
	array_unshift( $links, $import_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mmcsv_plugin_action_links' );

if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
	return;
}

// Load Importer API.
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$mmcsv_wp_importer_file = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $mmcsv_wp_importer_file ) ) {
		require_once $mmcsv_wp_importer_file;
	}
}

// Load Helpers.
require __DIR__ . '/class-mmcsv-csv-helper.php';
require __DIR__ . '/class-mmcsv-import-post-helper.php';

/**
 * CSV Importer
 *
 * @package Miller Media CSV Importer
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
	/**
	 * MMCSV_CSV_Importer class.
	 *
	 * Extends WP_Importer to provide CSV import functionality.
	 */
	class MMCSV_CSV_Importer extends WP_Importer {

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
			echo '<h2>' . esc_html__( 'Miller Media CSV Importer', 'miller-media-csv-importer' ) . '</h2>';
		}

		/**
		 * User interface wrapper end
		 */
		public function footer() {
			echo '</div>';
		}

		/**
		 * Get the reference list of recognized CSV columns and their meaning.
		 *
		 * @return array<string,string> Map of column name (or prefix) to description.
		 */
		protected function get_column_reference() {
			return array(
				'post_type'           => __( 'Required. The post type slug (e.g. post, page, or any custom post type).', 'miller-media-csv-importer' ),
				'ID / post_id'        => __( 'Existing post ID to update. If omitted, a new post is created. If provided but not found, a new post is created using that ID.', 'miller-media-csv-importer' ),
				'post_title'          => __( 'The post title.', 'miller-media-csv-importer' ),
				'post_name'           => __( 'The post slug.', 'miller-media-csv-importer' ),
				'post_author'         => __( 'Author user login or user ID. Left unset if the user cannot be found.', 'miller-media-csv-importer' ),
				'post_author_login'   => __( 'Author user login (alternative to post_author).', 'miller-media-csv-importer' ),
				'post_date'           => __( 'Publish date, e.g. 2024-01-31 10:00.', 'miller-media-csv-importer' ),
				'post_date_gmt'       => __( 'Publish date in GMT.', 'miller-media-csv-importer' ),
				'post_status'         => __( 'publish, draft, pending, future, private, or a custom registered status.', 'miller-media-csv-importer' ),
				'post_password'       => __( 'Password to protect the post (max 20 characters).', 'miller-media-csv-importer' ),
				'post_content'        => __( 'The full post content.', 'miller-media-csv-importer' ),
				'post_excerpt'        => __( 'The post excerpt.', 'miller-media-csv-importer' ),
				'post_parent'         => __( 'Parent post ID (for hierarchical post types such as pages).', 'miller-media-csv-importer' ),
				'menu_order'          => __( 'Numeric menu order.', 'miller-media-csv-importer' ),
				'comment_status'      => __( 'open or closed.', 'miller-media-csv-importer' ),
				'post_category'       => __( 'Comma-separated category slugs. Missing categories are created.', 'miller-media-csv-importer' ),
				'post_tags'           => __( 'Comma-separated tag names.', 'miller-media-csv-importer' ),
				'tax_{taxonomy}'      => __( 'Comma-separated term names/slugs for an existing custom taxonomy, e.g. tax_actors.', 'miller-media-csv-importer' ),
				'post_thumbnail'      => __( 'URL or server path of an image to import and set as the featured image.', 'miller-media-csv-importer' ),
				'cfs_{field}'         => __( 'Value for a Custom Field Suite field, e.g. cfs_color.', 'miller-media-csv-importer' ),
				'scf_{field}'         => __( 'Value for a Smart Custom Fields field, e.g. scf_text.', 'miller-media-csv-importer' ),
				'field_xxxxxxxxxxxxx' => __( 'Advanced Custom Fields field key, used to update that ACF field directly.', 'miller-media-csv-importer' ),
				'{anything else}'     => __( 'Any other column name is imported as a plain custom field (post meta).', 'miller-media-csv-importer' ),
			);
		}

		/**
		 * Step 1 - Display import form
		 */
		public function greet() {
			?>
			<h2><?php esc_html_e( 'How to Import', 'miller-media-csv-importer' ); ?></h2>
			<ol>
				<li>
					<?php
					printf(
						/* translators: %s: CSV field delimiter character. */
						esc_html__( 'Prepare a CSV file: UTF-8 encoded, fields delimited with "%s", all text cells quoted, and a header row of column names.', 'miller-media-csv-importer' ),
						esc_html( MMCSV_CSV_Helper::DELIMITER )
					);
					?>
				</li>
				<li>
					<?php
					printf(
						/* translators: %s: link to download the bundled sample.csv file. */
						esc_html__( 'Not sure where to start? Download the bundled sample file: %s', 'miller-media-csv-importer' ),
						'<a href="' . esc_url( plugin_dir_url( __FILE__ ) . 'sample/sample.csv' ) . '">' . esc_html__( 'sample.csv', 'miller-media-csv-importer' ) . '</a>'
					);
					?>
					<?php esc_html_e( 'More example files (categories & tags, custom fields, pages, dates, attachments) are included in the sample/ folder of this plugin.', 'miller-media-csv-importer' ); ?>
				</li>
				<li><?php esc_html_e( 'Choose your CSV file below and click "Upload file and import".', 'miller-media-csv-importer' ); ?></li>
				<li><?php esc_html_e( 'Review the per-row results shown on the next screen.', 'miller-media-csv-importer' ); ?></li>
			</ol>

			<details>
				<summary><?php esc_html_e( 'Column reference (click to expand)', 'miller-media-csv-importer' ); ?></summary>
				<table class="widefat striped" style="max-width:800px;margin-top:0.5em;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Column', 'miller-media-csv-importer' ); ?></th>
							<th><?php esc_html_e( 'Description', 'miller-media-csv-importer' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $this->get_column_reference() as $column => $description ) : ?>
						<tr>
							<td><code><?php echo esc_html( $column ); ?></code></td>
							<td><?php echo esc_html( $description ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</details>
			<?php

			// Render the core upload form, then inject the import options before its
			// closing tag so the options are part of the form (and visible) even when
			// JavaScript is disabled.
			ob_start();
			wp_import_upload_form( add_query_arg( 'step', 1 ) );
			$upload_form = ob_get_clean();

			ob_start();
			?>
			<h2><?php esc_html_e( 'Import Options', 'miller-media-csv-importer' ); ?></h2>
			<p><?php esc_html_e( 'Replace an existing post that has the same title instead of creating a new one:', 'miller-media-csv-importer' ); ?></p>
			<p>
				<label>
					<input type="radio" name="replace-by-title" value="0" checked="checked" />
					<?php esc_html_e( 'Disable', 'miller-media-csv-importer' ); ?>
				</label>
				<br />
				<label>
					<input type="radio" name="replace-by-title" value="1" />
					<?php esc_html_e( 'Enable', 'miller-media-csv-importer' ); ?>
				</label>
			</p>
			<?php
			$options_form = ob_get_clean();

			$upload_form = str_replace( '</form>', $options_form . '</form>', $upload_form );

			echo $upload_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from core wp_import_upload_form() output plus our own escaped markup above.
		}

		/**
		 * Step 2 - Process the uploaded file
		 */
		public function import() {
			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'miller-media-csv-importer' ) . '</strong><br />';
				echo esc_html( $file['error'] ) . '</p>';
				return false;
			} elseif ( ! file_exists( $file['file'] ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'miller-media-csv-importer' ) . '</strong><br />';
				printf(
					/* translators: %s: file path */
					esc_html__( 'The export file could not be found at %s. It is likely that this was caused by a permissions problem.', 'miller-media-csv-importer' ),
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
		 * Insert post and postmeta using `MMCSV_Import_Post_Helper` class.
		 *
		 * @param array  $post      Post data.
		 * @param array  $meta      Meta data.
		 * @param array  $terms     Terms data.
		 * @param string $thumbnail The uri or path of thumbnail image.
		 * @param bool   $is_update Whether this is an update operation.
		 * @return MMCSV_Import_Post_Helper
		 */
		public function save_post( $post, $meta, $terms, $thumbnail, $is_update ) {

			// Separate the post tags from $post array.
			if ( isset( $post['post_tags'] ) && ! empty( $post['post_tags'] ) ) {
				$post_tags = $post['post_tags'];
				unset( $post['post_tags'] );
			}

			// Special handling of attachments.
			if ( ! empty( $thumbnail ) && 'attachment' === $post['post_type'] ) {
				$post['media_file'] = $thumbnail;
				$thumbnail          = null;
			}

			// Add or update the post.
			if ( $is_update ) {
				$h = MMCSV_Import_Post_Helper::getByID( $post['ID'] );
				$h->update( $post );
			} else {
				$h = MMCSV_Import_Post_Helper::add( $post );
			}

			// Set post tags.
			if ( isset( $post_tags ) ) {
				$h->setPostTags( $post_tags );
			}

			// Set meta data.
			$h->setMeta( $meta );

			// Set terms.
			foreach ( $terms as $key => $value ) {
				$h->setObjectTerms( $key, $value );
			}

			// Add thumbnail.
			if ( $thumbnail ) {
				$h->addThumbnail( $thumbnail );
			}

			return $h;
		}

		/**
		 * Process parse csv and insert posts
		 */
		public function process_posts() {
			$h = new MMCSV_CSV_Helper();

			$handle = $h->fopen( $this->file, 'r' );
			if ( false === $handle ) {
				echo '<p><strong>' . esc_html__( 'Failed to open file.', 'miller-media-csv-importer' ) . '</strong></p>';
				wp_import_cleanup( $this->id );
				return false;
			}

			$is_first      = true;
			$post_statuses = get_post_stati();

			echo '<ol>';

			while ( false !== ( $data = $h->fgetcsv( $handle ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				if ( $is_first ) {
					$h->parse_columns( $this, $data );
					$is_first = false;
				} else {
					echo '<li>';

					$post      = array();
					$is_update = false;
					$error     = new WP_Error();

					// (string) (required) post type.
					$post_type = $h->get_data( $this, $data, 'post_type' );
					if ( $post_type ) {
						if ( post_type_exists( $post_type ) ) {
							$post['post_type'] = $post_type;
						} else {
							/* translators: %s: post type slug from CSV */
							$error->add( 'post_type_exists', sprintf( esc_html__( 'Invalid post type "%s".', 'miller-media-csv-importer' ), esc_html( $post_type ) ) );
						}
					} else {
						echo esc_html__( 'Note: Please include post_type value if that is possible.', 'miller-media-csv-importer' ) . '<br>';
					}

					// (int) post id.
					$post_id = $h->get_data( $this, $data, 'ID' );
					$post_id = ( $post_id ) ? $post_id : $h->get_data( $this, $data, 'post_id' );
					if ( $post_id ) {
						$post_exist = get_post( $post_id );
						if ( is_null( $post_exist ) ) { // if the post id is not exists.
							$post['import_id'] = $post_id;
						} elseif ( ! $post_type || $post_exist->post_type === $post_type ) {
								$post['ID'] = $post_id;
								$is_update  = true;
						} else {
							/* translators: %1$d: post ID, %2$s: post type from CSV, %3$s: post type from database */
							$error->add( 'post_type_check', sprintf( esc_html__( 'The post type value from your csv file does not match the existing data in your database. post_id: %1$d, post_type(csv): %2$s, post_type(db): %3$s', 'miller-media-csv-importer' ), absint( $post_id ), esc_html( $post_type ), esc_html( $post_exist->post_type ) ) );
						}
					}

					// (string) post title.
					$post_title = $h->get_data( $this, $data, 'post_title' );
					if ( $post_title ) {

						// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in dispatch() via check_admin_referer().
						if ( ! $is_update && isset( $_POST['replace-by-title'] ) && 1 === absint( wp_unslash( $_POST['replace-by-title'] ) ) ) {
							// Try to update a post with the same title.
							if ( ! $post_type ) {
								$post_type = 'post';
							}
							$query         = new WP_Query(
								array(
									'post_type'           => $post_type,
									'title'               => $post_title,
									'posts_per_page'      => 1,
									'no_found_rows'       => true,
									'ignore_sticky_posts' => true,
									'update_post_term_cache' => false,
									'update_post_meta_cache' => false,
									'orderby'             => 'post_date ID',
									'order'               => 'ASC',
								)
							);
							$existing_post = ! empty( $query->post ) ? $query->post : null;

							if ( ! is_null( $existing_post ) ) {
								$post['ID'] = $existing_post->ID;
								$is_update  = true;
							}
						}

						$post['post_title'] = $post_title;
					}

					// (string) post slug.
					$post_name = $h->get_data( $this, $data, 'post_name' );
					if ( $post_name ) {
						$post['post_name'] = $post_name;
					}

					// (login or ID) post_author.
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

					// user_login to post_author.
					$user_login = $h->get_data( $this, $data, 'post_author_login' );
					if ( $user_login ) {
						$user = get_user_by( 'login', $user_login );
						if ( isset( $user ) && is_object( $user ) ) {
							$post['post_author'] = $user->ID;
							unset( $user );
						}
					}

					// (string) publish date.
					$post_date = $h->get_data( $this, $data, 'post_date' );
					if ( $post_date ) {
						$post['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( $post_date ) );
					}
					$post_date_gmt = $h->get_data( $this, $data, 'post_date_gmt' );
					if ( $post_date_gmt ) {
						$post['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime( $post_date_gmt ) );
					}

					// (string) post status.
					$post_status = $h->get_data( $this, $data, 'post_status' );
					if ( $post_status ) {
						if ( in_array( $post_status, $post_statuses, true ) ) {
							$post['post_status'] = $post_status;
						}
					}

					// (string) post password.
					$post_password = $h->get_data( $this, $data, 'post_password' );
					if ( $post_password ) {
						$post['post_password'] = $post_password;
					}

					// (string) post content.
					$post_content = $h->get_data( $this, $data, 'post_content' );
					if ( $post_content ) {
						$post['post_content'] = $post_content;
					}

					// (string) post excerpt.
					$post_excerpt = $h->get_data( $this, $data, 'post_excerpt' );
					if ( $post_excerpt ) {
						$post['post_excerpt'] = $post_excerpt;
					}

					// (int) post parent.
					$post_parent = $h->get_data( $this, $data, 'post_parent' );
					if ( $post_parent ) {
						$post['post_parent'] = absint( $post_parent );
					}

					// (int) menu order.
					$menu_order = $h->get_data( $this, $data, 'menu_order' );
					if ( $menu_order ) {
						$post['menu_order'] = absint( $menu_order );
					}

					// (string) comment status.
					$comment_status = $h->get_data( $this, $data, 'comment_status' );
					if ( $comment_status ) {
						$post['comment_status'] = $comment_status;
					}

					// (string, comma separated) slug of post categories.
					$post_category = $h->get_data( $this, $data, 'post_category' );
					if ( $post_category ) {
						$categories = preg_split( '/,+/', $post_category );
						if ( $categories ) {
							$post['post_category'] = wp_create_categories( $categories );
						}
					}

					// (string, comma separated) name of post tags.
					$post_tags = $h->get_data( $this, $data, 'post_tags' );
					if ( $post_tags ) {
						$post['post_tags'] = $post_tags;
					}

					// (string) post thumbnail image uri.
					$post_thumbnail = $h->get_data( $this, $data, 'post_thumbnail' );

					$meta = array();
					$tax  = array();

					// add any other data to post meta.
					foreach ( $data as $key => $value ) {
						if ( false !== $value && isset( $this->column_keys[ $key ] ) ) {
							// check if meta is custom taxonomy.
							if ( substr( $this->column_keys[ $key ], 0, 4 ) === 'tax_' ) {
								// (string, comma divided) name of custom taxonomies.
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

						// save post data.
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
									__( 'Processing "%s" done.', 'miller-media-csv-importer' ),
									$post_title
								)
							);
						}
					}

					// show error messages.
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

			echo '<h3>' . esc_html__( 'All Done.', 'miller-media-csv-importer' ) . '</h3>';
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
					set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Importing many rows can exceed the default max_execution_time; no-op on hosts where this is disabled.
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
	function mmcsv_initialize_importer() {
		$csv_importer = new MMCSV_CSV_Importer();
		register_importer(
			MMCSV_IMPORTER_ID,
			__( 'Miller Media CSV Importer', 'miller-media-csv-importer' ),
			__( 'Import posts, pages, categories, tags, and custom fields from a CSV file.', 'miller-media-csv-importer' ),
			array( $csv_importer, 'dispatch' )
		);
	}
	// Registered on admin_init (not plugins_loaded) so translation functions above run after
	// WordPress has finished loading text domains, avoiding a "textdomain loaded too early" notice.
	add_action( 'admin_init', 'mmcsv_initialize_importer' );

} // End if class_exists( 'WP_Importer' ).
