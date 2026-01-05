<?php
require __DIR__ . '/vendor/autoload.php';

if (class_exists('TCPDF')) {
    echo "TCPDF is installed and working";
} else {
    echo "TCPDF is NOT available";
}
