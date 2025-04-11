<?php

spl_autoload_register(function($clase){ // codigo para realizar el autoload, y que regrese los namespace encontrados

    $archivo = __DIR__."/".$clase.".php"; // se coloca DIR para la direccion, mas el namespace y la extension .php hacen la direccion
    $archivo = str_replace("\\","/",$archivo); // se remplaza \ por /

    if(is_file($archivo)){ // ver si la direccion existe
        require_once $archivo; // incluirla en el archivo
    }
});

?>
