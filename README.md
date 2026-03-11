# Miller Media CSV Importer

[![WordPress Version](https://img.shields.io/badge/WordPress-5.0%2B-blue)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green)](LICENSE)

**Import posts, pages, custom post types, categories, tags, and custom fields from a simple CSV file.**

A maintained fork of [Really Simple CSV Importer](https://wordpress.org/plugins/really-simple-csv-importer/) with PHP 8.1+ compatibility, enhanced security, and active maintenance.

---

## Description

Miller Media CSV Importer is a powerful yet simple CSV import plugin for WordPress, perfect for developers and site administrators who need reliable bulk import functionality.

**This plugin is a maintained fork created with the blessing of the original author, [Takuro Hishikawa](https://github.com/hissy). We are grateful for his excellent work and for allowing us to continue this project.**

### Why This Fork?

The original Really Simple CSV Importer was last updated in 2017 and is incompatible with modern PHP versions. This fork provides:

- **PHP 8.1+ compatibility** - Fixed all deprecated features and warnings
- **WordPress 6.9+ compatibility** - Tested and verified with the latest WordPress
- **Enhanced security** - Proper sanitization and escaping for all inputs/outputs
- **Active maintenance** - Regular updates and support
- **Backward compatibility** - All filter hooks and action hooks maintained

---

## Features

✅ **Category support** - Import post categories  
✅ **Tag support** - Import post tags  
✅ **Custom field support** - Import custom fields (post meta)  
✅ **Smart Custom Fields support** - Integration with SCF plugin  
✅ **Custom Field Suite support** - Integration with CFS plugin  
✅ **Advanced Custom Fields support** - Integration with ACF plugin  
✅ **Custom Taxonomy support** - Import custom taxonomies  
✅ **Custom Post Type support** - Import any registered post type  
✅ **Filter hooks** - Customize import data processing  
✅ **Action hooks** - Run custom code after import  
✅ **PHP 8.1+ compatible** - Modern PHP support  
✅ **Security hardened** - Proper sanitization and escaping  

---

## Installation

### From WordPress.org

1. Go to **Plugins → Add New** in your WordPress admin
2. Search for "Miller Media CSV Importer"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through the **Plugins** menu in WordPress
4. Go to **Tools → Import** and click **CSV**

---

## Usage

### Basic Import Process

1. Go to **Tools → Import** in WordPress admin
2. Click **CSV**
3. Choose your CSV file (UTF-8 encoding required)
4. Click **Upload file and import**
5. Wait for the import to complete

### CSV File Requirements

- **Encoding**: UTF-8 (required)
- **Delimiter**: Comma (`,`)
- **Text cells**: Must be quoted (`"text"`)
- **File extension**: `.csv`

⚠️ **Excel-style CSV is not recommended.** Use LibreOffice for best results.

### CSV Format

Your CSV file should have column headers in the first row that match the field names below.

#### Example CSV

```csv
"post_type","post_status","post_title","post_content","post_category","post_tags","custom_field"
"post","publish","My First Post","This is the content.","news","announcement,update","Custom value"
"page","publish","About Us","About page content.","","",""
```

### Available Columns

#### Post Data Columns

| Column | Type | Description |
|--------|------|-------------|
| `ID` or `post_id` | int | Post ID. If exists, updates the post. If new, creates post with this ID. |
| `post_type` | string | **Required**. Post type slug (e.g., `post`, `page`, or custom post type) |
| `post_status` | string | Post status: `draft`, `publish`, `pending`, `future`, `private` |
| `post_title` | string | Post title |
| `post_content` | string | Post content (HTML allowed) |
| `post_excerpt` | string | Post excerpt |
| `post_date` | string | Publish date (e.g., `2024/02/12 10:00`) |
| `post_date_gmt` | string | Publish date in GMT |
| `post_author` | string/int | Author username or user ID |
| `post_author_login` | string | Author username (alternative to `post_author`) |
| `post_name` | string | Post slug |
| `post_parent` | int | Parent post ID (for hierarchical post types) |
| `post_password` | string | Post password (max 20 characters) |
| `menu_order` | int | Menu order |
| `comment_status` | string | `open` or `closed` |

#### Taxonomy Columns

| Column | Type | Description |
|--------|------|-------------|
| `post_category` | string | Comma-separated category slugs |
| `post_tags` | string | Comma-separated tag names |
| `tax_{taxonomy}` | string | Custom taxonomy terms (e.g., `tax_actors` for "actors" taxonomy) |

#### Media Columns

| Column | Type | Description |
|--------|------|-------------|
| `post_thumbnail` | string | Featured image URL or file path |

**For attachments:** If `post_type` is `attachment`, use `post_thumbnail` for the media file URL.

#### Custom Field Columns

| Prefix | Description | Example |
|--------|-------------|---------|
| (none) | Standard custom field | `my_custom_field` |
| `cfs_` | Custom Field Suite field | `cfs_my_field` |
| `scf_` | Smart Custom Fields field | `scf_my_field` |
| `field_` | Advanced Custom Fields key | `field_123abc` |

Any column not matching the above will be imported as a custom field (post meta).

### Important Notes

- **Empty cells** = "keep existing value" (does not delete)
- **Future posts** require `post_date` to be set
- **Page templates** use custom field `_wp_page_template`
- **Updates** require either `ID`/`post_id` or matching `post_title` (if option enabled)

---

## Developer Hooks

### Filter Hooks

#### `really_simple_csv_importer_save_post`

Modify post data before saving.

**Parameters:**
- `$post` (array) - Post data
- `$is_update` (bool) - Whether updating existing post

**Example:**

```php
add_filter( 'really_simple_csv_importer_save_post', function( $post, $is_update ) {
    // Remove specific tag
    if ( isset( $post['post_tags'] ) ) {
        $tags = explode( ',', $post['post_tags'] );
        $tags = array_diff( $tags, [ 'unwanted-tag' ] );
        $post['post_tags'] = implode( ',', $tags );
    }
    return $post;
}, 10, 2 );
```

#### `really_simple_csv_importer_save_meta`

Modify meta data before saving.

**Parameters:**
- `$meta` (array) - Meta data
- `$post` (array) - Post data
- `$is_update` (bool) - Whether updating existing post

**Example:**

```php
add_filter( 'really_simple_csv_importer_save_meta', function( $meta, $post, $is_update ) {
    // Serialize multiple meta fields into array
    $meta['combined_field'] = [
        $meta['field_1'] ?? '',
        $meta['field_2'] ?? ''
    ];
    unset( $meta['field_1'], $meta['field_2'] );
    return $meta;
}, 10, 3 );
```

#### `really_simple_csv_importer_save_tax`

Modify taxonomy data before saving.

**Parameters:**
- `$tax` (array) - Taxonomy data
- `$post` (array) - Post data
- `$is_update` (bool) - Whether updating existing post

**Example:**

```php
add_filter( 'really_simple_csv_importer_save_tax', function( $tax, $post, $is_update ) {
    // Fix misspelled taxonomy terms
    if ( isset( $tax['actors'] ) ) {
        $tax['actors'] = array_map( function( $actor ) {
            return str_replace( 'Johnny Dep', 'Johnny Depp', $actor );
        }, $tax['actors'] );
    }
    return $tax;
}, 10, 3 );
```

#### `really_simple_csv_importer_save_thumbnail`

Modify thumbnail data before saving.

**Parameters:**
- `$post_thumbnail` (string) - Thumbnail URL or path
- `$post` (array) - Post data
- `$is_update` (bool) - Whether updating existing post

**Example:**

```php
add_filter( 'really_simple_csv_importer_save_thumbnail', function( $post_thumbnail, $post, $is_update ) {
    // Import from FTP directory
    if ( ! empty( $post_thumbnail ) && file_exists( $post_thumbnail ) ) {
        $upload_dir = wp_upload_dir();
        $target = $upload_dir['path'] . '/' . basename( $post_thumbnail );
        if ( copy( $post_thumbnail, $target ) ) {
            return $target;
        }
    }
    return $post_thumbnail;
}, 10, 3 );
```

#### `really_simple_csv_importer_dry_run`

Enable dry-run mode (no database changes).

**Example:**

```php
add_filter( 'really_simple_csv_importer_dry_run', '__return_true' );
```

#### `really_simple_csv_importer_class`

Replace the importer class entirely.

**Example:**

```php
add_filter( 'really_simple_csv_importer_class', function() {
    return 'My_Custom_Importer_Class';
} );
```

### Action Hooks

#### `really_simple_csv_importer_post_saved`

Fired after a post is successfully imported.

**Parameters:**
- `$post_object` (WP_Post) - The imported post object

**Example:**

```php
add_action( 'really_simple_csv_importer_post_saved', function( $post_object ) {
    // Send notification email
    wp_mail( 
        'admin@example.com', 
        'Post Imported', 
        "Post '{$post_object->post_title}' was imported successfully." 
    );
} );
```

---

## Advanced Examples

### CSV Examples in `/sample` Directory

The plugin includes sample CSV files demonstrating various import scenarios:

- `sample.csv` - Basic post import
- `custom_fields.csv` - Custom field import
- `pages.csv` - Page import
- `movies.csv` - Custom post type with custom taxonomy
- `import_attachment.csv` - Media attachment import
- `import_thumbnail.csv` - Featured image import
- `smart_custom_fields.csv` - Smart Custom Fields integration
- `post_dates.csv` - Future/scheduled posts
- `post_status_test.csv` - Various post statuses

### Importing Multiple Attachments

Use the `really_simple_csv_importer_save_meta` filter to import multiple files into a custom field:

```php
add_filter( 'really_simple_csv_importer_save_meta', function( $meta, $post, $is_update ) {
    if ( isset( $meta['images'] ) ) {
        $urls = explode( '|', $meta['images'] );
        $attachment_ids = [];
        
        foreach ( $urls as $url ) {
            $attachment_id = media_sideload_image( $url, $post['ID'], null, 'id' );
            if ( ! is_wp_error( $attachment_id ) ) {
                $attachment_ids[] = $attachment_id;
            }
        }
        
        $meta['images'] = $attachment_ids;
    }
    return $meta;
}, 10, 3 );
```

CSV format: `"http://example.com/img1.jpg|http://example.com/img2.jpg"`

---

## Debugging

### Dry Run Testing

Use the [Really Simple CSV Importer Debugger](https://gist.github.com/hissy/7175656) add-on (created for the original plugin by Takuro Hishikawa) to preview imports without making database changes. It works with Miller Media CSV Importer since all filter hooks are maintained.

### Common Issues

**Import fails with encoding errors:**
- Ensure your CSV is UTF-8 encoded
- Use LibreOffice instead of Excel
- Quote all text cells

**Custom fields not importing:**
- Check column names don't match reserved post data columns
- For ACF: Use field keys (`field_abc123`) instead of field names
- For CFS: Use `cfs_` prefix
- For SCF: Use `scf_` prefix

**Images not importing:**
- Verify URLs are accessible
- Check file permissions in uploads directory
- Use absolute paths or full URLs

---

## Localizations

The plugin is available in the following languages:

- 🇺🇸 English (default)
- 🇫🇷 French (fr_FR)
- 🇯🇵 Japanese (ja)
- 🇪🇸 Spanish (es_ES)
- 🇩🇪 German (de_DE)
- 🇨🇳 Chinese Simplified (zh_CN)
- 🇧🇷 Portuguese - Brazil (pt_BR)
- 🇮🇹 Italian (it_IT)
- 🇷🇺 Russian (ru_RU)
- 🇵🇱 Polish (pl_PL)
- 🇳🇱 Dutch (nl_NL)
- 🇹🇷 Turkish (tr_TR)
- 🇸🇪 Swedish (sv_SE)

---

## Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 8.1 or higher
- **Recommended:** LibreOffice for CSV creation

---

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

**Bug Reports:** Please use [GitHub Issues](https://github.com/Miller-Media/miller-media-csv-importer/issues).

---

## License

This plugin is licensed under the [GPLv2 or later](LICENSE).

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## Credits

### Original Author

This plugin is based on [Really Simple CSV Importer](https://github.com/hissy/rs-csv-importer) created by **[Takuro Hishikawa](https://github.com/hissy)**. 

We are deeply grateful to Takuro for:
- Creating this excellent plugin
- Maintaining it for many years
- Graciously allowing us to fork and continue the project

**Thank you, Takuro!** 🙏

### Current Maintainers

Maintained by **[Miller Media](https://www.millermedia.io)**

### Cover Banner Design

Original cover banner designed by [@luchino__](http://uwasora.com/)

---

## Links

- [WordPress.org Plugin Page](https://wordpress.org/plugins/miller-media-csv-importer/)
- [GitHub Repository](https://github.com/Miller-Media/miller-media-csv-importer)
- [Original Plugin by Takuro Hishikawa](https://github.com/hissy/rs-csv-importer)
- [Miller Media](https://www.millermedia.io)

---

**Made with ❤️ by Miller Media, building on the excellent foundation by Takuro Hishikawa**
