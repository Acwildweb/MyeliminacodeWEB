<?php

    $strPath = 'immaginicliente/';
    @$directory_handle = opendir($strPath);
    while ($file = readdir($directory_handle)) {
        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
            $images[] = $file;
        }
    }
    closedir($directory_handle);
    echo implode("|", $images);