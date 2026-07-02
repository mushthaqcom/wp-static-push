<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSP_Zip {

    public static function create( $source_dir ) {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return new WP_Error( 'no_zip', 'ZipArchive PHP extension is not available.' );
        }

        $zip_file = WPSP_OUTPUT_DIR . '/static-site-' . gmdate('Ymd-His') . '.zip';
        $zip      = new ZipArchive();

        if ( $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            return new WP_Error( 'zip_fail', 'Could not create ZIP file.' );
        }

        $source_dir = realpath( $source_dir );
        $iterator   = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $iterator as $file ) {
            $file_path   = $file->getRealPath();
            $relative    = substr( $file_path, strlen( $source_dir ) + 1 );

            if ( $file->isDir() ) {
                $zip->addEmptyDir( $relative );
            } else {
                $zip->addFile( $file_path, $relative );
            }
        }

        $zip->close();

        return $zip_file;
    }

    public static function get_download_url( $zip_file ) {
        $relative = str_replace( WP_CONTENT_DIR, content_url(), $zip_file );
        return $relative;
    }
}
