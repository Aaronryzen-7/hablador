<?php

namespace app\models; // declaro el namespace
use \PDO; // se coloca asi para poder usar new PDO

if(file_exists(__DIR__."/../../config/server.php")){ // DIR es para dar la direccion exacta y asi buscar el archivo server.php
    require_once __DIR__."/../../config/server.php"; // si el archivo existe se agregara a aqui al mainModel.php
}


class mainModel{ //le doy el mainModel por que asi se llama el archivo, es para el autoload
    private $server = 'localhost'; //traemos los archivos o la informacion del server.php aqui
    private $db = 'hablador';
    private $user = 'root';
    private $pass = '';


//  conexion a la base de datos
    protected function conectar(){
        $conexion = new PDO("mysql:host=".$this->server.";dbname=".$this->db, $this->user, $this->pass); //esta es la conexion a la base de datos con PDO
        $conexion->exec("SET CHARACTER SET utf8"); //eso dice de que se quiere usar los tipos de caracteres utf8
        return $conexion; // se regresa el vlaor de $conexion
    }


    // para ejecutar consultas
    protected function ejecutarConsulta($consulta){
        $sql = $this->conectar()->prepare($consulta); // se usa conectar para relacionarlo con el pdo ya definido y prepare para la consulta que se va a ejecutar
        $sql->execute(); // ejecutar la consulta pasada
        return $sql; // cuando utilicen esta clase devolvera el valor de $sql
    }


    // filtro para ayudar a evitar inyecciones sql
    public function limpiarCadena($cadena){

        $palabras=["<script>","</script>","<script src","<script type=","SELECT * FROM","SELECT ",
        " SELECT ","DELETE FROM","INSERT INTO","DROP TABLE","DROP DATABASE","TRUNCATE TABLE","SHOW TABLES",
        "SHOW DATABASES","<?php","?>","--","^","<",">","==","=",";","::"];

        $cadena = trim($cadena); // trim es una funcion de php para eliminar los espacios en blanco del principio y final del texto
        $cadena = stripslashes($cadena); // es para eliminar las barras del texto ejemplo: \ y /


        foreach($palabras as $palabra){
            $cadena = str_ireplace($palabra, "", $cadena); // es para saber si en cadena esta cada uno de las palabras que no van y si es asi seran remplazadas por un espacion vacio
        }

        $cadena = trim($cadena);
        $cadena = stripslashes($cadena); 



        return $cadena;

    }



    // expresiones regulares, es la proteccion para saber que no puede ir en un texto o string
    protected function verificarDatos($filtro,$cadena){
        if(preg_match("/^".$filtro."$/", $cadena)){
            return false;
        }else{
            return true;
        }
    }


    // esta funcion o clase es para una consulta sql de guardar datos en una tabla pero es dinamica, pues para que sirvan para varias tablas
    protected function guardarDatos($tabla,$datos){

        $query="INSERT INTO $tabla (";

        $C=0;
        foreach ($datos as $clave){
            if($C>=1){ $query.=","; }
            $query.=$clave["campo_nombre"];
            $C++;
        }
        
        $query.=") VALUES(";

        $C=0;
        foreach ($datos as $clave){
            if($C>=1){ $query.=","; }
            $query.=$clave["campo_marcador"];
            $C++;
        }

        $query.=")";
        $sql=$this->conectar()->prepare($query);

        foreach ($datos as $clave){
            $sql->bindParam($clave["campo_marcador"],$clave["campo_valor"]);
        }

        $sql->execute();

        return $sql;
    }


    // modelo para seleccionar datos de forma dinamica en la base de datos
    public function seleccionarDatos($tipo, $tabla, $campo, $id){
        $tipo = $this->limpiarCadena($tipo); // limpiamos los datos recibidos por seguridad
        $tabla = $this->limpiarCadena($tabla);
        $campo = $this->limpiarCadena($campo);
        $id = $this->limpiarCadena($id);

        /* realizamos una condicion doble para asegurar que tipo de seleccion de datos se pide, dependiendo hace un sql diferente
        y ademas se utiliza la variable sql con bindParam */
        if($tipo == "Unico"){
            $sql = $this->conectar()->prepare("SELECT * FROM $tabla WHERE $campo=:ID");
            $sql->bindParam(":ID",$id);

        }elseif($tipo == "Normal"){

            $sql = $this->conectar()->prepare("SELECT $campo FROM $tabla");

        }


        $sql->execute(); // ejecutarmos el sql

        return $sql;
    }


    // modelo para actualizar datos de forma dinamica en la base de datos
    protected function actualizarDatos($tabla,$datos,$condicion){
			
        $query="UPDATE $tabla SET ";

        $C=0;
        foreach ($datos as $clave){
            if($C>=1){ $query.=","; }
            $query.=$clave["campo_nombre"]."=".$clave["campo_marcador"];
            $C++;
        }

        $query.=" WHERE ".$condicion["condicion_campo"]."=".$condicion["condicion_marcador"];

        $sql=$this->conectar()->prepare($query);

        foreach ($datos as $clave){
            $sql->bindParam($clave["campo_marcador"],$clave["campo_valor"]);
        }

        $sql->bindParam($condicion["condicion_marcador"],$condicion["condicion_valor"]);

        $sql->execute();

        return $sql;
    }


    // modelo para eliminar registros de forma dinamica
    protected function eliminarRegistro($tabla, $campo, $id){

        $sql = $this->conectar()->prepare("DELETE FROM $tabla WHERE $campo=:id"); // preparar la consulta sql
        $sql->bindParam(":id",$id); // pasar el bindParam

        $sql->execute(); // ejecutar la consulta
        return $sql;
    }


    // modelo para crear la paginacion de tablas
    protected function paginadorTablas($pagina, $numeroPaginas, $url, $botones){

        $tabla = '<nav class="pagination is-centered is-rounded" role="navigation" aria-label="pagination">';
        /* todo se hara a partir de condiciones y de ahi ver que elementos html se les agregara */

        if($pagina <= 1){ // si la pagina es 1 o menor se agregara el boton desabilitado el de anterior

            $tabla.='
            <a class="pagination-previous is-disabled" disabled >Anterior</a>
            <ul class="pagination-list">
            ';

        }else{ // sino se agregara normal y algunos botones en el medio
            $tabla.='
            <a class="pagination-previous" href="'.$url.($pagina-1).'/">Anterior</a>
            <ul class="pagination-list">
                <li><a class="pagination-link" href="'.$url.'1/">1</a></li>
                <li><span class="pagination-ellipsis">&hellip;</span></li>
            ';
        }

        // el ci y el for son para los botones en el medio
        $ci = 0;
        for($i=$pagina; $i<=$numeroPaginas; $i++){

            if($ci>=$botones){
                break; // si ci es igual al numero de botones que queremos se para el codigo
            }

            if($pagina == $i){
                $tabla.='<li><a class="pagination-link is-current" href="'.$url.$i.'/">'.$i.'</a></li>'; // para marcar que estamos en esa pagina
            }else{
                $tabla.='<li><a class="pagination-link" href="'.$url.$i.'/">'.$i.'</a></li>'; // lo que tendran los demas botones
            }
            $ci++;
        }

        if($pagina == $numeroPaginas){ // es para el final, si la pagina actual es la ultima se desabilitara el boton de siguiente
            $tabla.='
            </ul>
            <a class="pagination-next is-disabled" disabled >Siguiente</a>
            ';
        }else{ // sino se agregara el ultimo boton y antes de eso unos puntos suspensivos
            $tabla.='
                <li><span class="pagination-ellipsis">&hellip;</span></li>
                <li><a class="pagination-link" href="'.$url.$numeroPaginas.'/">'.$numeroPaginas.'</a></li>
            </ul>
            <a class="pagination-next" href="'.$url.($pagina+1).'/">Siguiente</a>
            ';
        }

        $tabla.='</nav>'; // se cierra el nav
        return $tabla; // se retorna el valor de la tabla

    }

}


?>