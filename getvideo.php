<?php

    $strPath = 'immaginicliente/';
    @$directory_handle = opendir($strPath);
    while ($file = readdir($directory_handle)) {
        if (preg_match('/\.(mp4|avi|mov)$/i', $file)) {
            $videos[] = '/immaginicliente/'.$file;
        }
    }
    closedir($directory_handle);
	echo json_encode($videos, true);