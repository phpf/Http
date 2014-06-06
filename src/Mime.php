<?php

namespace Phpf\Http;

/**
 * MIME storage.
 * Returns values for e.g. HTTP Content-Type headers.
 * Also performs reverse look-ups to file extensions (in some cases).
 */
class Mime extends StaticStorage {
	protected static $store = array(
        'html'		=> 'text/html',
        'xml'		=> 'text/xml',
        'csv'		=> 'text/csv',
        'css'		=> 'text/css',
        'vcard'		=> 'text/vcard',
        'text'		=> 'text/plain',
        'md'		=> 'text/x-markdown',
        'js'		=> 'text/javascript',
        'jsonp'		=> 'text/javascript',
        'json'		=> 'application/json',
        'xhtml'		=> 'application/html+xml',
        'rss'		=> 'application/rss+xml',
        'atom'		=> 'application/atom+xml',
        'rdf' 		=> 'application/rdf+xml',
        'dtd'		=> 'application/xml-dtd',
        'zip'		=> 'application/zip',
        'gzip'		=> 'application/gzip',
        'woff'		=> 'application/font-woff',
        'soap'		=> 'application/soap+xml',
        'pdf'		=> 'application/pdf',
        'ddl'		=> 'application/octet-stream',
        'download'	=> 'application/octet-stream',
        'form'		=> 'application/x-www-form-urlencoded',
        'xls'		=> 'application/vnd.ms-excel',
        'xlxs'		=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'		=> 'application/vnd.ms-powerpoint',
        'pptx'		=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'docx'		=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'odt'		=> 'application/vnd.oasis.opendocument.text',
        'odp'		=> 'application/vnd.oasis.opendocument.presentation',
        'ods'		=> 'application/vnd.oasis.opendocument.spreadsheet',
        'xps'		=> 'application/vnd.ms-xpsdocument',
        'kml'		=> 'application/vnd.google-earth.kml+xml',
        'flash'		=> 'application/x-shockwave-flash',
        'swf'		=> 'application/x-shockwave-flash',
        'dart'		=> 'application/dart',
        'upload'	=> 'multipart/form-data',
        'gif'		=> 'image/gif',
        'jpeg'		=> 'image/jpeg',
        'png'		=> 'image/png',
        'svg'		=> 'image/svg+xml',
        'mp4'		=> 'audio/mp4',
        'mp3'		=> 'audio/mpeg',
        'mpeg'		=> 'audio/mpeg',
        'ogg'		=> 'audio/ogg',
        'flac'		=> 'audio/ogg',
        'wav'		=> 'audio/vnd.wave',
        'message'	=> 'message/http',
    );
}
