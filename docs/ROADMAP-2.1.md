# BookPress 2.1: eBooks, print publishing and more templates

## Context

BookPress 2.0.0 is live on WordPress.org. It builds a PDF book from posts in wp-admin, with covers, templates and a print wrap cover. For the next release the user listed nine features:
1. EPUB
2. Kindle export
3. KDP export
4. More templates
5. CMYK support
6. Crop marks
7. Bleed guides
8. Printer presets
9. Price detail

Decisions from the user:
- **One release (2.1)** with all nine features.
- **Everything stays free** on WordPress.org.
- **"Price detail" means price plus ISBN barcode:**
  - an EAN-13 ISBN barcode with the EAN-5 price add-on on the back cover
  - ISBN and price on the copyright page
  - ISBN in the eBook metadata

Goal: one book in BookPress produces everything an author needs to publish:
- a reflowable eBook (EPUB/Kindle)
- a printer-ready interior and cover for KDP, IngramSpark or Lulu
- a proof PDF with crop marks and guides
- a correct ISBN barcode

What mPDF 8.3 already provides (checked in `vendor-prefixed/mpdf/mpdf`):
- **Crop and cross marks:** `@page { marks: crop cross; sheet-size; bleed }`, plus the `bleedMargin`, `cropMarkMargin` and `printers_info` settings.
- **CMYK and PDF/X:** `restrictColorSpace = 3` converts colours to CMYK; `PDFX` and `PDFXauto` produce PDF/X-1a. PDF/X needs a CMYK ICC profile, and mPDF ships only sRGB.
- **Barcodes:** `Barcode::getBarcodeArray()` handles ISBN and EAN5. The `<barcode>` tag can't add the price add-on, so we draw the bars ourselves as SVG.
- **Packaging:** `ZipArchive` is available, and WordPress core's PclZip can be the fallback. Imagick is not installed here, so we can't rely on it to turn the cover PDF into an image.

## Architecture changes

- **Schema v2** (`src/books/class-book-schema.php`, `VERSION` 2, with a migration from v1). New sections:
  - `formats`: `pdf` (default on), `epub`, `print_package`
  - `print`: preset, trim size, paper, colour mode `rgb|cmyk`, interior bleed, proof options (crop marks, bleed guides, safe area, printer info), barcode mode `own|printer|none`
  - `publishing`: print ISBN, eBook ISBN, price amount and currency, publisher or imprint, edition, publication date, language, description
- **One renderer, several outputs.** `Book_Renderer::sections()` stays the single source of content.
  - `Pdf_Engine` becomes `Book_Engine` (`render( Book_Renderer, $path, $progress )`).
  - The engines are `Mpdf_Engine` (screen and print PDF), the new `Epub_Engine`, and the new `Proof_Engine`. The proof engine reuses `Mpdf_Engine` with a larger sheet and an overlay.
- **Job:** `Generate_Book_Job::handle` runs every selected format in turn, with combined progress, and stores each result with its own kind:
  - `File_Store` kinds: `book`, `print-cover`, `epub`, `proof`, `proof-cover`, `package`
  - `File_Store::add()` keeps N builds and prunes by build, not by single file, so one build's files stay together.
- **Preflight:** `src/print/class-preflight.php` returns a list of pass, warn and fail checks after each print build. The job stores it, the REST job response returns it, and the editor shows it.

## Features

### 1–2. EPUB 3 and Kindle (`src/epub/`)

**Packaging (`class-epub-engine.php`):**
- Writes `mimetype` first and uncompressed, then `META-INF/container.xml` and `OEBPS/content.opf`.
- The OPF carries:
  - title, author and language
  - `dc:identifier`: the eBook ISBN, or a stable `urn:uuid` stored in book meta
  - `dcterms:modified` and publisher
  - the cover image, with `<meta name="cover">` for older readers
- `nav.xhtml` holds the TOC from the section headings, plus landmarks. `toc.ncx` is included for Kindle and older readers.

**Content:**
- One XHTML file per section: cover, title page, copyright, dedication, preface, TOC, parts, chapters and about the author.
- The section HTML is converted to well-formed XHTML with `DOMDocument::saveXML`.
- The content filter gets an EPUB profile that:
  - drops or converts tags EPUB doesn't allow
  - turns galleries and columns into a simple flow
  - wraps tables so they can scroll

**Assets:**
- Images are copied from uploads and resized to a 1600 px maximum with `wp_get_image_editor`.
- Fonts: the bundled OFL fonts used by the template are embedded.
- Each template gets an `epub.css`. It is the template's `book.css` without `@page`, mPDF-only rules or fixed sizes, and uses relative units.

**Cover image** (Kindle and KDP want at least 1600×2560), in priority order:
1. an uploaded "eBook cover image" (new cover field)
2. Imagick with Ghostscript, turning the front cover PDF into an image, when available
3. a GD-drawn cover built from the cover design's colours, fonts, title, author and featured image, in `class-cover-raster.php`

**Kindle:**
- Amazon now takes EPUB for both KDP and Send to Kindle; MOBI and KindleGen are retired.
- "Kindle" is therefore a **Kindle-tuned EPUB profile**:
  - cover at least 1600×2560
  - no font obfuscation
  - simpler drop caps, using a class rather than floats in the first-letter selector
  - only the hyphenation and page-break properties Kindle supports
  - file-size check
- It is offered as "Kindle (KDP / Send to Kindle)". There is no MOBI output.

**Zip:** ZipArchive, falling back to PclZip with the no-compression option for `mimetype`.

### 3 and 8. Printer presets and KDP export (`src/print/`)

**`class-printer-presets.php`** is a registry with a `wpbook_printer_presets` filter:
- **Presets:** `kdp`, `ingramspark`, `lulu` and `custom`.
- **Each preset defines:**
  - trim sizes (KDP: 5×8, 5.25×8, 5.5×8.5, 6×9, 6.14×9.21, 6.69×9.61, 7×10, 7.44×9.69, 7.5×9.25, 8×10, 8.25×6, 8.25×8.25, 8.5×8.5, 8.5×11, A4, and so on)
  - paper types with thickness, e.g. KDP white 0.002252 in/page, cream 0.0025, premium colour 0.002347
  - bleed: 0.125 in
  - page-count limits (KDP 24–828)
  - **gutter by page count.** KDP: 24–150 pages 0.375 in, 151–300 0.5, 301–500 0.625, 501–700 0.75, 701–828 0.875.
  - minimum outside margin: 0.25 in, or 0.375 in with bleed
  - minimum page count for spine text (KDP 79)
  - spine safety margin: 0.0625 in
  - barcode area: KDP 2 × 1.2 in, bottom right of the back cover
  - PDF profile: KDP accepts RGB with embedded fonts; IngramSpark wants PDF/X-1a and CMYK
- `Wrap_Cover::page_thickness()` and the trim list in `Page_Sizes` read from the preset, replacing the hard-coded values.

**Applying a preset:**
- It sets trim size, mirrored margins and right-hand chapter starts.
- Gutter and margins are computed from the last page count. A one-pass re-render runs if the page count crosses a gutter step.

**`class-preflight.php` checks:**
- page count is within the preset's limits
- trim size is supported
- margins are at least the minimums
- interior images have at least 300 effective DPI at their placed size; the renderer records each image's pixel and placed size
- fonts are embedded (always true with mPDF)
- spine text is allowed for the page count
- the barcode area is clear, or our own barcode fits inside it
- colour mode matches the preset
- no watermark or password on print files

**Print package:**
- The "KDP export" or "IngramSpark export" button builds `{slug}-kdp.zip` containing `interior.pdf`, `cover.pdf`, `ebook.epub` and `metadata.txt`.
- `metadata.txt` lists title, subtitle, author, description, keywords, ISBNs, price, trim, paper and page count, ready to paste into KDP.
- The editor shows the preflight report.

### 5. CMYK and PDF/X

**Settings:** Output gets a "Colour: Screen (RGB) / Print (CMYK)" choice. The preset sets the default: KDP RGB, IngramSpark CMYK.

**How CMYK is produced:**
- CMYK mode sets mPDF `restrictColorSpace = 3`.
- Black text and rules are forced to pure K (0/0/0/100), never four-colour rich black.
- The cover keeps rich black only for large solid areas.

**PDF/X-1a:**
- Turned on with `PDFX` and `PDFXauto` when the preset needs it.
- That requires a CMYK ICC output intent. We don't bundle one, because profile licences may not be GPL-compatible. Instead:
  - a "print profile" download, using the same mechanism and SHA-256 checks as the font packs, once a redistributable profile is confirmed (task: check the ECI or FOGRA39 licence)
  - or an uploaded ICC file from the printer, stored in the protected uploads folder
- PDF/X forbids transparency, so in PDF/X mode:
  - watermarks are disabled
  - the 12 covers are rendered in CMYK and PDF/X and checked
  - covers that rely on opacity (gradients, layered SVG shapes) get solid CMYK-safe variants through a `print_safe` flag in the cover registry

**IngramSpark ink limit:** preflight warns when a cover colour exceeds 240% total ink.

### 6–7. Crop marks, bleed guides and interior bleed (`src/print/class-proof.php`)

**Proof PDF and proof cover:**
- A separate output kind, `proof`. Printer-ready files never contain marks; KDP rejects them.
- The page is rendered on a sheet the size of trim + 2 × (bleed + mark margin), with `@page { marks: crop cross; }`. Printer info (file, page, date) is optional.

**Bleed guides** are drawn as an overlay on every page with the existing page-background mechanism, using an SVG data URI:
- trim box: thin solid cyan
- bleed box: dashed magenta
- safe area: dashed green, 0.25 in inside the trim
- gutter: tinted
- on the proof cover: spine fold lines, the spine-text safe zone, and a box marking the barcode area

**Interior bleed:**
- New `print.bleed` toggle. It adds 0.125 in at top, bottom and outside, and sets the PDF TrimBox and BleedBox.
- Full-bleed images can then run off the page, which the new picture-book template needs.
- Task: check mPDF's TrimBox and BleedBox output, and add the box entries ourselves if it doesn't write them.

### 9. Price and ISBN barcode (`src/publishing/`)

**`class-isbn.php`:**
- Validates ISBN-13 and ISBN-10 check digits, converts ISBN-10 to ISBN-13 and adds hyphens.
- Turns a price into the EAN-5 code:
  - the first digit is the currency (5 USD, 6 CAD, 0 GBP, 3 AUD, 4 NZD)
  - "90000" means no suggested price

**`class-barcode-svg.php`:**
- Builds an SVG from mPDF's `Barcode::getBarcodeArray()` for the ISBN (EAN-13) and the EAN-5 add-on.
- Adds human-readable text ("ISBN 978-…" above, digits below) and a white quiet-zone box.

**Placement:**
- Back cover: the barcode is placed in the preset's barcode area.
- Barcode mode:
  - "Print my barcode"
  - "Leave space for the printer's barcode" (KDP adds its own)
  - "None"

**Elsewhere:**
- Copyright page: print ISBN, eBook ISBN, edition and publisher, plus an optional price line.
- EPUB: the eBook ISBN becomes `dc:identifier`, with publisher and date.

**UI:** a new **Publishing** step in the editor with these fields.

### 4. More templates

**Six new interior templates**, each with `book.css`, `epub.css` and a thumbnail:
- **Cookbook:** styled ingredients and steps; works with the Recipe blocks.
- **Poetry:** centred, keeps line breaks, no justified text, one poem per page.
- **Academic:** numbered headings, footnotes from the Footnotes block, references, wider margins.
- **Picture Book:** full-bleed images, large type, square 8.5 × 8.5 or landscape pages; needs interior bleed.
- **Journal / Workbook:** lined pages and worksheet boxes.
- **Photo Book:** image-first with captions, landscape.

**Six new covers:** Watercolour, Retro, Noir, Romance, Kids Picture Book and Report/Academic.
- Every cover gets a `print_safe` check for CMYK.
- Thumbnails come from `bin/render-thumbs.php`.

## Admin UI (`assets/src/components/editor/`)

- **New Publishing step:** ISBNs, price and currency, publisher, edition, description, keywords, language.
- **Output becomes "Formats & print":**
  - format checkboxes: PDF, EPUB/Kindle, print package
  - printer preset picker
  - trim size filtered by the preset
  - paper
  - colour mode
  - interior bleed
  - barcode mode
  - proof options: crop marks, bleed guides, safe area, printer info
  - ICC profile status
- **Generate result:**
  - a card per file: PDF, EPUB, Print cover, Proof, KDP package
  - the **preflight panel**: pass, warn and fail rows, each linking to the setting to change
- **Gallery thumbnails** for the new templates and covers.

## Milestones (about 13 weeks for one developer)

| # | Scope | Estimate |
|---|---|---|
| M1 | Schema v2 and migration, `Book_Engine` refactor, multi-format job and file kinds, printer preset registry | 1.5 w |
| M2 | EPUB 3 engine, Kindle profile, cover image (upload, Imagick, GD), epub.css for 6 templates, epubcheck in CI | 2.5 w |
| M3 | Presets applied to layout: gutter by page count, preflight, print package ZIP, metadata.txt | 2 w |
| M4 | CMYK, PDF/X-1a, ICC profile download or upload, CMYK-safe covers, ink-limit check | 1.5 w |
| M5 | Interior bleed, TrimBox and BleedBox, proof PDF with crop marks and bleed guides, proof cover | 1 w |
| M6 | ISBN and price: validation, EAN-13 + EAN-5 SVG, back cover placement, copyright page, EPUB metadata | 1 w |
| M7 | 6 interior templates and 6 covers, with print and EPUB styles and thumbnails | 2.5 w |
| M8 | Publishing and Formats UI, preflight panel, readme, screenshots, new video, RC, release 2.1.0 | 1.5 w |

**Release process** is the same as 2.0:
1. Work on `release/2.1`, with one PR per milestone.
2. Before release: PHPCS, the shipped-PHP lint, PHPUnit, Plugin Check and `composer audit`.
3. Push the `2.1.0` tag, wait for the deploy to finish, then merge to `main`. The user pushes the tag.

## Risks and spikes (first week)

- **ICC licence.** If no CMYK profile is GPL-compatible to redistribute, PDF/X relies only on profiles the user uploads.
- **mPDF PDFXauto and the covers.** Gradients and opacity are the main problem; test the 12 covers early.
- **TrimBox and BleedBox in mPDF.** If they're missing, we patch the PDF output.
- **Turning post content into EPUB.** Galleries, embeds, wide tables and columns are the hard cases. We test against a corpus of block types.
- **Release size.** New templates and covers add about 1–2 MB. Fonts stay in the downloadable packs.

## Verification

- **PHPUnit:**
  - schema v1 → v2 migration
  - preset maths: gutter by page count, spine width per paper type, minimum page count for spine text
  - ISBN-10/13 validation, conversion and hyphens; price → EAN-5 (USD 14.99 → 51499, no price → 90000)
  - preflight rules
  - the EPUB package structure: mimetype first and uncompressed, OPF manifest and spine, nav
- **CI:**
  - **epubcheck** (pinned JAR) on sample EPUBs for each template and the Kindle profile; it must report zero errors
  - **zbarimg** decodes the rendered back-cover barcode as the right ISBN and price
  - `pdfinfo` and `qpdf --json` confirm the page size equals trim + bleed, that TrimBox and BleedBox exist, and that the PDF/X version and OutputIntent are set in CMYK mode
  - `pdfimages -list` confirms CMYK images in CMYK mode
  - the existing shipped-PHP lint, PHPCS, Plugin Check and PHPUnit matrix
- **Manual, done by the user with their accounts:**
  - upload the KDP package to a KDP paperback draft (the previewer checks interior and cover without publishing)
  - open the EPUB in Kindle Previewer
  - upload the IngramSpark package to their file check
- **Browser run on the demo site:** create a book with the KDP preset, add an ISBN and a price, build all formats, check the preflight panel, and download every file.
