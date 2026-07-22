<?php

return [
    'encoding'         => 'UTF-8',
    'finalize'         => true,
    'ignoreNonStrings' => false,
    'cachePath'        => storage_path('app/purifier'),
    'cacheFileMode'    => 0755,

    'settings' => [

        // Used for block-level "text" fields (paragraph, header, list item,
        // quote text/caption). Deliberately narrow: inline formatting only,
        // no block tags, no images, no iframes/scripts/styles/on* attributes
        // (HTMLPurifier strips those by default regardless of HTML.Allowed).
        'inline' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'b,strong,i,em,u,s,mark,a[href|title|rel],br,sub,sup,code',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => true,
            'URI.AllowedSchemes'       => ['http' => true, 'https' => true, 'mailto' => true],
            // Prevent tabnabbing via editor-inserted links.
            'HTML.TargetBlank'         => true,
            'HTML.Nofollow'            => true,
        ],

        // Used for the Editor.js "raw" block only. Broader (block-level
        // markup, images, tables) but still routed through HTMLPurifier so
        // <script>, on* attributes, javascript: URIs, iframes, etc. are
        // stripped rather than passed straight into the page.
        'default' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'div[class],p[class],b,strong,i,em,u,s,mark,a[href|title|rel|target],ul,ol,li,br,sub,sup,code,pre,blockquote,h1,h2,h3,h4,h5,h6,table,thead,tbody,tr,th,td,img[src|alt|width|height|class]',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => true,
            'URI.AllowedSchemes'       => ['http' => true, 'https' => true, 'mailto' => true],
            'HTML.TargetBlank'         => true,
            'HTML.Nofollow'            => true,
        ],

    ],
];
