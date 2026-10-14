<?php
/**
 * Generates a small PDF with the bundled mPDF and checks the output.
 * Mirrors the options WPBook\admin\Controller::generate_book() uses.
 *
 * Usage: php tests/smoke-pdf.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$out  = sys_get_temp_dir() . '/wp-book-smoke.pdf';
$mpdf = new \Mpdf\Mpdf(
    array(
        'format'           => 'A4',
        'orientation'      => 'P',
        'tempDir'          => sys_get_temp_dir() . '/wp-book',
        'autoScriptToLang' => true,
        'autoLangToFont'   => true,
    )
);
$mpdf->WriteHTML('h2{color:#c33}', \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->SetHTMLHeader('{DATE j-m-Y}');
$mpdf->SetHTMLFooter('{PAGENO}');
$mpdf->WriteHTML('<h2>Chapter 1</h2><p>Hello <b>world</b>: café, 日本語, العربية.</p>');
$mpdf->AddPage();
$mpdf->WriteHTML('<h2>Chapter 2</h2><p>Second page.</p>');
$mpdf->Output($out, 'F');

if (file_get_contents($out, false, null, 0, 5) !== '%PDF-' || $mpdf->page !== 2) {
    fwrite(STDERR, "PDF smoke test failed\n");
    exit(1);
}
printf("mPDF %s on PHP %s: %d pages, %d bytes\n", \Mpdf\Mpdf::VERSION, PHP_VERSION, $mpdf->page, filesize($out));
