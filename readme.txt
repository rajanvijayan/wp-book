=== WP Book ===
Contributors: rajanit2000
Tags: pdf, ebook, book, export, mpdf
Requires at least: 3.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn your posts, pages and custom post types into a downloadable PDF book in a few clicks.

== Description ==

WP Book collects content from your site and compiles it into a single PDF book. Pick the posts you want, put them in the order you want, choose a page layout and template, and download the result. No coding is needed.

Watch the [WP Book overview video](https://www.youtube.com/watch?v=nBIG8jT8aNY):

https://www.youtube.com/watch?v=nBIG8jT8aNY

= Choose your content =

* **Post type**: build a book from posts, pages or any public custom post type.
* **Number of posts**: limit how many entries go into the book.
* **Order and sort**: sort entries by ID, author, title, slug, type, published date, modified date, parent or at random, in ascending or descending order.
* **Drag and drop**: rearrange the selected entries by hand before generating the book.

= Shape the PDF =

* **Page format**: more than 50 paper sizes, including the A, B, C and RA series, Letter, Legal, Tabloid and Executive.
* **Orientation**: portrait or landscape.
* **Page breaks**: optionally start each post on a new page.
* **Header and footer**: add custom text with placeholders such as `{PAGENO}` for the page number and `{DATE j-m-Y}` for the current date.
* **Templates**: choose the Default, Style 1 or Dark template.

Generated books are saved to `wp-content/uploads/wp-book/`, and you can download them right after they are created.

== Installation ==

= From your WordPress dashboard =

1. Go to **Plugins → Add New**.
2. Search for **WP Book**.
3. Click **Install Now**, then **Activate**.

= Manual installation =

1. Download the plugin ZIP file.
2. Go to **Plugins → Add New → Upload Plugin**, choose the ZIP file and click **Install Now**. You can also extract the ZIP file and upload the `wp-book` folder to `wp-content/plugins/` over FTP.
3. Activate **WP Book** on the **Plugins** screen.

= Usage =

1. Go to **Settings → WP Book**.
2. Set your post filters and PDF options.
3. Drag the listed entries into the order you want.
4. Click **Generate Book**, then **Download PDF**.

== Frequently Asked Questions ==

= Which PHP versions are supported? =

PHP 7.4 and later, including PHP 8.x. WP Book bundles mPDF 8.

= I only see a blank page after clicking Generate Book. =

This usually means a large number of posts on a server with limited resources. Check your PHP error log. The most common causes are script timeouts and running out of memory. Try raising these values in `php.ini`:

* `max_execution_time = 600`: the maximum execution time of each script, in seconds.
* `max_input_time = 30`: the maximum time each script may spend parsing request data.
* `memory_limit = 256M`: the maximum amount of memory a script may use.

You can also generate a book with fewer posts.

= I get "Temporary files directory ... is not writable". =

WP Book stores mPDF's temporary files in a `wp-book` folder inside your server's temporary directory, as reported by WordPress's `get_temp_dir()`. Make sure your web server user can write to that directory, or set `WP_TEMP_DIR` in `wp-config.php` to a writable path.

= Where are the generated PDF files stored? =

In `wp-content/uploads/wp-book/`.

== Screenshots ==

1. The WP Book screen with post filters, PDF options and the list of entries to include.
2. A book generated successfully and ready to download.
3. A sample page from a generated PDF.
4. Choosing the post type.
5. Choosing the page format.
6. Adding a custom header and footer with page number and date placeholders.
7. Choosing a predefined template.

== Credits ==

* [mPDF](https://mpdf.github.io/): PHP library used to generate the PDF files.
* [jQuery UI](https://jqueryui.com/): used for drag and drop sorting.
* [Select2](https://select2.org/): used for the searchable select boxes.

== Changelog ==

= 1.0.1 =
* Rewrote the plugin description, FAQ and screenshot captions.
* New plugin banners.
* Added a Requires PHP header (7.1).
* Updated bundled libraries: myclabs/deep-copy 1.13.4 and psr/log 1.1.4.

= 1.0 =
* Initial release.
