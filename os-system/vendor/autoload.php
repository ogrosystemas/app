<?php
/**
 * Autoload para TCPDF
 */

define('TCPDF_PATH', __DIR__ . '/tecnickcom/tcpdf/');

require_once TCPDF_PATH . 'tcpdf.php';
require_once TCPDF_PATH . 'tcpdf_parser.php';
require_once TCPDF_PATH . 'tcpdf_filters.php';
require_once TCPDF_PATH . 'include/tcpdf_colors.php';
require_once TCPDF_PATH . 'include/tcpdf_font_data.php';
require_once TCPDF_PATH . 'include/tcpdf_fonts.php';
require_once TCPDF_PATH . 'include/tcpdf_images.php';
require_once TCPDF_PATH . 'include/tcpdf_static.php';
