<?php

    $strPath = 'videocliente/';
    @$directory_handle = opendir($strPath);
    while ($file = readdir($directory_handle)) {
        if (preg_match('/\.(mp4|avi|mov)$/i', $file)) {
            $videos[] = $file;
        }
    }
    closedir($directory_handle);
    echo implode("|", $videos);