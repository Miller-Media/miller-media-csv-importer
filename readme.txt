=== Miller Media CSV Importer ===
Contributors: MillerMediaNow, hissy
Tags: csv, importer, import, acf, custom-fields
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import posts, pages, custom post types, categories, tags, taxonomies, and custom fields from a simple CSV file.

== Description ==

**This plugin is a maintained fork of [Really Simple CSV Importer](https://wordpress.org/plugins/really-simple-csv-importer/) originally created by [Takuro Hishikawa](https://github.com/hissy). We are grateful for his work and his blessing to continue this project.**

Miller Media CSV Importer adds a "Miller Media CSV Importer" item to **Tools &rarr; Import**. Upload a CSV file and the plugin creates or updates posts, pages, or any custom post type, along with categories, tags, custom taxonomies, featured images, and custom fields (including Advanced Custom Fields, Custom Field Suite, and Smart Custom Fields).

= Features =

* Create new posts/pages/custom post types, or update existing ones by ID or title
* Category, tag, and custom taxonomy support
* Custom field (post meta) support, including bulk-mapped columns
* [Advanced Custom Fields](http://www.advancedcustomfields.com/) support
* [Custom Field Suite](http://customfieldsuite.com/) support
* [Smart Custom Fields](https://wordpress.org/plugins/smart-custom-fields/) support
* Featured image / attachment import from a local path or a remote URL
* Filter and action hooks so developers can customize every step of the import
* PHP 8.1+ and WordPress 7.1 compatible

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/miller-media-csv-importer` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **Tools &rarr; Import** and click **Miller Media CSV Importer** (or use the "Import CSV" link on this plugin's row on the Plugins screen).
4. Follow the on-screen instructions: prepare your CSV, download the bundled sample if you want a starting point, choose your file, and click "Upload file and import".

== How to Use ==

= 1. Prepare your CSV file =

* Save the file as **UTF-8** encoded CSV.
* Use a **comma (,)** as the field delimiter.
* **Quote** every text cell (most spreadsheet applications, such as LibreOffice or Excel, do this automatically when you export as CSV).
* The **first row** must be a header row containing the column names described below.

= 2. Upload and import =

1. Go to **Tools &rarr; Import &rarr; Miller Media CSV Importer**.
2. Choose your CSV file and, if you want existing posts with a matching title to be updated instead of duplicated, enable "Replace by post title" under Import Options.
3. Click "Upload file and import".
4. The plugin processes each row and reports the result (created, updated, or any error) directly on the screen.

Empty cells mean "leave the existing value alone", not "delete it".

= Column reference =

Only `post_type` is required. Every other column is optional - include only the ones you need.

* `post_type` - *(required)* The post type slug (e.g. `post`, `page`, or any custom post type). Must already be registered.
* `ID` or `post_id` - (int) ID of an existing post to update. If the ID does not exist yet, a new post is created using that ID. If omitted entirely, a new post is created with the next available ID.
* `post_title` - (string) The post title.
* `post_name` - (string) The post slug.
* `post_author` - (login or ID) The user login or user ID of the author. Left unset (defaults to the current user) if the given user cannot be found - for example when a CSV exported from another site references a user that does not exist on this one.
* `post_author_login` - (string) Alternative way to specify the author by user login.
* `post_date` - (string) The publish date, e.g. `2024-01-31 10:00`.
* `post_date_gmt` - (string) The publish date in GMT.
* `post_status` - (string) `publish`, `draft`, `pending`, `future`, `private`, or any custom registered status. Defaults to `draft`. If you use `future`, you must also provide `post_date`.
* `post_password` - (string) Password to protect the post (max 20 characters).
* `post_content` - (string) The full post content.
* `post_excerpt` - (string) The post excerpt.
* `post_parent` - (int) The parent post ID. Used for hierarchical post types such as pages.
* `menu_order` - (int) Numeric menu order.
* `comment_status` - (string) `open` or `closed`.
* `post_category` - (string, comma separated) Category slugs. Categories that do not exist yet are created.
* `post_tags` - (string, comma separated) Tag names.
* `tax_{taxonomy}` - (string, comma separated) Term names or slugs for an existing custom taxonomy. For example `tax_actors` imports values into the `actors` taxonomy, which must already be registered. Names of terms that don't exist are created.
* `post_thumbnail` - (string) A URL or server path of an image. The image is imported into the Media Library and set as the post's featured image.
* `cfs_{field}` - (string) Value for a [Custom Field Suite](http://customfieldsuite.com/) field, e.g. `cfs_color`.
* `scf_{field}` - (string) Value for a [Smart Custom Fields](https://wordpress.org/plugins/smart-custom-fields/) field, e.g. `scf_text`.
* `field_xxxxxxxxxxxxx` - (string) An [Advanced Custom Fields](http://www.advancedcustomfields.com/) field key (starts with `field_`). The value is saved with ACF's `update_field()` so field-specific formatting is respected.
* Any other column name - (string) Imported as a plain custom field (post meta), using the column name as the meta key.

Note: To set the page template of a page, use the custom field key `_wp_page_template`.

= Minimal sample CSV =

`
"post_title","post_type","post_status","post_content"
"My First Import","post","publish","Hello from the CSV importer!"
`

A fuller example, plus several ready-to-use files covering categories/tags, custom fields, custom taxonomies, pages, dates, and attachments, are bundled with the plugin in its `sample/` directory (for example `wp-content/plugins/miller-media-csv-importer/sample/sample.csv`), and the main one is linked directly from the importer screen.

== Frequently Asked Questions ==

= Can I update existing posts instead of creating new ones? =

Yes. Either:

* Include an `ID` or `post_id` column that matches an existing post, or
* Enable "Replace by post title" on the importer screen so a row whose `post_title` matches an existing post of the same `post_type` updates that post instead of creating a new one.

= Does this work with custom post types? =

Yes. Set `post_type` to the slug of any post type already registered on your site (by WordPress, your theme, or another plugin).

= Does this work with custom taxonomies? =

Yes. Use a column named `tax_{taxonomy}`, where `{taxonomy}` is the taxonomy's registered slug (not its label). The taxonomy must already exist. For example:

**csv file**

`
"post_title","tax_actors"
"Captain Phillips","Tom Hanks, Barkhad Abdi, Barkhad Abdirahman"
`

**imported post data**

Post Title: Captain Phillips. Custom taxonomy "Actors": Tom Hanks, Barkhad Abdi, Barkhad Abdirahman.

= How do dates work? =

`post_date` and `post_date_gmt` accept anything PHP's `strtotime()` understands (e.g. `2024-01-31`, `2024/01/31 10:00`). If you set `post_status` to `future`, you must provide `post_date` so WordPress knows when to publish the post.

= My file has special characters / was exported from Excel - will it work? =

Save/export it as **UTF-8** CSV and quote every text cell. Excel's "CSV UTF-8" export option works; the plain "CSV" export option often does not use UTF-8 and can corrupt non-ASCII characters.

= Is there a file size or row limit? =

The upload is limited by your server's PHP upload settings (`upload_max_filesize`, `post_max_size`). There's no hard row limit, but very large files can take a long time to process since each row triggers a full `wp_insert_post()` (and any associated term/meta/thumbnail calls); consider splitting huge exports into smaller files.

= Can I import custom fields or custom taxonomy values with array data (e.g. a Select or Checkbox field)? =

Yes, with a small companion plugin using the `really_simple_csv_importer_save_meta` filter below to convert the CSV string into an array before it's saved.

= Can I import multiple files as attachments into a single custom field? =

See the developer example under "How to customize import post data" below, or the [add-on development example](https://gist.github.com/hissy/d2041481a72510b7f394).

= What's the difference between this and the original Really Simple CSV Importer? =

This is a maintained fork that provides PHP 8.1+ and WordPress 7.1 compatibility, hardened sanitization/escaping, a unique importer ID (so it no longer collides with the original plugin's "csv" importer ID if both are installed), and active maintenance. The original plugin was last updated in 2017. This fork keeps backward compatibility with all filter/action hooks from the original (they intentionally keep their original `really_simple_csv_importer_*` names) so existing customizations keep working.

You can get the example CSV files in the `/wp-content/plugins/miller-media-csv-importer/sample` directory.

== How to Customize Import Post Data (for developers) ==

There are several filters available, all kept under their original `really_simple_csv_importer_*` names for backward compatibility with existing add-ons.

= really_simple_csv_importer_save_post =

Applied to post data before it is saved.

Parameters:

* `$post` - (array)(required) post data
* `$is_update` - (bool) whether this is an update of an existing post, or a new insert

Example:

`
function really_simple_csv_importer_save_post_filter( $post, $is_update ) {

	// remove specific tag from import data
	if (isset($post['post_tags'])) {
		$_tags = array();
		foreach ($post['post_tags'] as $tag) {
			if ($tag != 'Apple') {
				$_tags[] = $tag;
			}
		}
		$post['post_tags'] = $_tags;
	}

	return $post;
}
add_filter( 'really_simple_csv_importer_save_post', 'really_simple_csv_importer_save_post_filter', 10, 2 );
`

= really_simple_csv_importer_save_meta =

Applied to post meta (custom field) data.

Parameters:

* `$meta` - (array)(required) post meta data
* `$post` - (array) post data
* `$is_update` - (bool)

Example:

`
function really_simple_csv_importer_save_meta_filter( $meta, $post, $is_update ) {

	// serialize metadata
	$meta_array = array();
	if (isset($meta['meta_key_1'])) $meta_array[] = $meta['meta_key_1'];
	if (isset($meta['meta_key_2'])) $meta_array[] = $meta['meta_key_2'];
	$meta = array( 'meta_key' => $meta_array );

	return $meta;
}
add_filter( 'really_simple_csv_importer_save_meta', 'really_simple_csv_importer_save_meta_filter', 10, 3 );
`

= really_simple_csv_importer_save_tax =

Applied to custom taxonomy data (categories and tags are post data, not included here).

Parameters:

* `$tax` - (array)(required) post taxonomy data
* `$post` - (array) post data
* `$is_update` - (bool)

Example:

`
function really_simple_csv_importer_save_tax_filter( $tax, $post, $is_update ) {

	// Fix misspelled taxonomy
	if (isset($tax['actors'])) {
		$_actors = array();
		foreach ($tax['actors'] as $actor) {
			if ($actor == 'Johnny Dep') {
				$actor = 'Johnny Depp';
			}
			$_actors[] = $actor;
		}
		$tax['actors'] = $_actors;
	}

	return $tax;
}
add_filter( 'really_simple_csv_importer_save_tax', 'really_simple_csv_importer_save_tax_filter', 10, 3 );
`

= really_simple_csv_importer_save_thumbnail =

Applied to the thumbnail URL/path before it is fetched and attached.

Parameters:

* `$post_thumbnail` - (string)(required) the thumbnail file path or remote URL
* `$post` - (array) post data
* `$is_update` - (bool)

Example:

`
function really_simple_csv_importer_save_thumbnail_filter( $post_thumbnail, $post, $is_update ) {

	// Import a local file from an FTP directory
	if (!empty($post_thumbnail) && file_exists($post_thumbnail)) {
		$upload_dir   = wp_upload_dir();
		$target_path  = $upload_dir['path'] . DIRECTORY_SEPARATOR . basename($post_thumbnail);
		if (copy($post_thumbnail, $target_path)) {
			$post_thumbnail = $target_path;
		}
	}

	return $post_thumbnail;
}
add_filter( 'really_simple_csv_importer_save_thumbnail', 'really_simple_csv_importer_save_thumbnail_filter', 10, 3 );
`

= really_simple_csv_importer_dry_run =

Return `true` from this filter to parse the file and show results without actually writing anything to the database.

= really_simple_csv_importer_class =

Return the name of a class (already loaded, with a `save_post( $post, $meta, $tax, $post_thumbnail, $is_update )` method) to completely replace the default save routine. See the [add-on development example](https://gist.github.com/hissy/199ad9be855ec9be1e54).

== How to Customize the Post Data After Importing (for developers) ==

= really_simple_csv_importer_post_saved =

Action fired with the imported `WP_Post` object right after each row is saved, so you can run follow-up tasks (e.g. download a remote image into a custom field - see this [gist](https://gist.github.com/hissy/0973a6a9977129a6ebd0)).

== How to Debug Import Data ==

*Really Simple CSV Importer Debugger add-on* (a separate, optional tool from the original plugin's author) enables dry-run testing and shows detailed post/meta/taxonomy data for each CSV row before it's saved. Download from [gist](https://gist.github.com/hissy/7175656).

== Localizations ==

The plugin ships with translations for: French (fr_FR), Japanese (ja), Spanish (es_ES), German (de_DE), Chinese Simplified (zh_CN), Portuguese - Brazil (pt_BR), Italian (it_IT), Russian (ru_RU), Polish (pl_PL), Dutch (nl_NL), Turkish (tr_TR), and Swedish (sv_SE), in addition to English.

== Credits ==

* Original plugin created by [Takuro Hishikawa](https://github.com/hissy): [Really Simple CSV Importer](https://github.com/hissy/rs-csv-importer)
* Cover banner designed by @[luchino__](http://uwasora.com/)
* Maintained fork: [Miller Media](https://mattmiller.ai) - [GitHub repository](https://github.com/Miller-Media/miller-media-csv-importer)

== Changelog ==

= 2.0.2 =
* Removed the Tested up to header from the main plugin file (it is declared in readme.txt).

= 2.0.1 =
* Fix: register the importer on `admin_init` instead of `plugins_loaded` so WordPress no longer logs a "textdomain loaded too early" notice on every admin request.
* Fix: pass the `$escape` parameter to `fgetcsv()` explicitly to remove a PHP 8.4+ deprecation notice.
* Fix: removed the dead link to a bundled `sample.ods` file (the file is no longer shipped).
* Changed: the importer now registers under the unique ID `miller-media-csv-importer` (shown as "Miller Media CSV Importer" under Tools > Import) instead of the generic `csv` ID, which could silently collide with other CSV importer plugins, including the original Really Simple CSV Importer.
* New: an "Import CSV" action link on this plugin's row on the Plugins screen, and a one-time dismissible admin notice after activation, both pointing straight to the importer screen.
* New: the importer screen now shows numbered setup instructions and an expandable column reference table.
* New: the "Import Options" (replace-by-title) box is now rendered directly in the form and is fully usable without JavaScript; the separate `auto.js` script is no longer needed and was removed.
* Housekeeping: resolved all WordPress Plugin Check and PHPCS WordPress-standard findings; bumped "Tested up to" to 7.1.

= 2.0.0 =
* **MAJOR UPDATE**: Renamed from "Really Really Simple CSV Importer" to "Miller Media CSV Importer"
* **PHP 8.1+ compatibility**: Fixed all deprecated features and warnings
  * Replaced deprecated `get_page_by_title()` with WP_Query
  * Fixed dynamic property declarations
  * Updated date() to gmdate() for proper timezone handling
  * Added proper type checking and null safety
  * Removed deprecated syntax
* **Security hardening**:
  * Added proper sanitization for all user inputs (sanitize_text_field, sanitize_key, absint, esc_url_raw)
  * Added proper escaping for all outputs (esc_html, esc_attr, esc_url)
  * Enhanced nonce verification
  * Improved file upload security
* **WordPress 6.9.1 compatibility**: Tested and verified
* **Code quality improvements**:
  * Added comprehensive PHPDoc blocks
  * Improved WordPress coding standards compliance
  * Added ABSPATH security checks
  * Better code organization and readability
* **Backward compatibility**: All filter hooks and action hooks maintained for existing implementations
* **Text domain updated** to `miller-media-csv-importer`
* **New**: Added plugin version constant `MMCSV_PLUGIN_VERSION`
* **Minimum requirements updated**:
  * WordPress 5.0+
  * PHP 8.1+

= 1.3 =
* Some Enhancements (Thanks @piwi!)
  * Attachment support
  * Add "really_simple_csv_importer_save_thumbnail" Filter
  * French translations
* Support post date GMT
* Support post password
= 1.2 =
* Enhancement: Smart Custom Fields support
* Check if the post status is already registered
= 1.1 =
* Enhancement: Support localization
* Bug fixes
= 1.0 =
* Enhancement: Replace a helper class to more better one
* Enhancement: Add action hook
* Bug fixes
= 0.6.3 =
* Enhancement: Remove Byte Order Mark automatically (Thanks lucianosantana!)
= 0.6.2 =
* Fix error on PHP 5.2
= 0.6.1 =
* Bug fix: enables to import "0" value
= 0.6 =
* Enhancement: Custom Field Suite support
* Enhancement: Add ability to override save_post method
* Performance Improvements
= 0.5.7 =
* Enhancement: Add dry run filter
= 0.5.6 =
* Bug fix: Fails to update empty custom field value.
= 0.5.5 =
* Bug fix: Fix to enable to update post meta values.
= 0.5.4 =
* Enhancement: Check the post type is already exists.
* Update readme
= 0.5.2 =
* New feature: Add Post Thumbnail support
* Bug fixes
= 0.5.1 =
* Enhancement: Check whether both posts has same post type when updating.
= 0.5 =
* New feature: Added filter hooks to customize import data
= 0.4.2 =
* Post title bug fix
= 0.4.1 =
* Version fix
= 0.4 =
* New feature: Added custom taxonomy support. (Thanks chuckhendo!)
= 0.3 =
* New feature: Advanced Custom Fields integrate.
* Enhancement: Use post_id if not already present when inserting post.
= 0.2 =
* New feature: Add post_id column. It enables to update post data.
* Some bug fixes
= 0.1.1 =
* Bug fix
= 0.1 =
* First Release (beta)

== Upgrade Notice ==

= 2.0.1 =
Bug fixes and a unique importer ID to avoid colliding with other CSV importers. Find it at Tools > Import > Miller Media CSV Importer, or via the new "Import CSV" link on the Plugins screen. No CSV, filter, or action changes needed.

= 2.0.0 =
Major update with PHP 8.1+ compatibility and security hardening. Backward compatible with all existing filter hooks. Recommended for all users, especially those running PHP 8.1+.

= 1.0 =
* wp_post_helper class is deprecated.
