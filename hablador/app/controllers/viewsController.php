<?php


// este es el controlador de usuarios

namespace app\controllers;
use app\models\mainModel; // usamos el mainModel

class viewsController extends mainModel{
    public function actualizarFotoProductoControlador(){

        $id=$this->limpiarCadena($_POST['id_producto']); // limpiamos el id por seguridad

        # Verificando usuario #
        $datos=$this->ejecutarConsulta("SELECT * FROM habla WHERE id='$id'"); // verificamos que el usuario exista por su id, de existir lo guardamos en un array
        if($datos->rowCount()<=0){
            $alerta=[
                "tipo"=>"simple",
                "titulo"=>"Ocurrió un error inesperado",
                "texto"=>"No hemos encontrado el producto en el sistema",
                "icono"=>"error"
            ];
            return json_encode($alerta);
            exit();
        }else{
            $datos=$datos->fetch();
        }

        # Directorio de imagenes #
        $img_dir="../views/fotos/";

        # Comprobar si se selecciono una imagen #
        if($_FILES['imagen_producto']['name']=="" && $_FILES['imagen_producto']['size']<=0){
            $alerta=[
                "tipo"=>"simple",
                "titulo"=>"Ocurrió un error inesperado",
                "texto"=>"No ha seleccionado una foto para el producto",
                "icono"=>"error"
            ];
            return json_encode($alerta);
            exit(); // es para comprobar si existe una imagen
        }

        # Creando directorio #
        if(!file_exists($img_dir)){ // si la carpeta donde se guardara la foto no existe, se creara
            if(!mkdir($img_dir,0777)){
                $alerta=[
                    "tipo"=>"simple",
                    "titulo"=>"Ocurrió un error inesperado",
                    "texto"=>"Error al crear el directorio",
                    "icono"=>"error"
                ];
                return json_encode($alerta);
                exit();
            } 
        }

        # Verificando formato de imagenes #
        if(mime_content_type($_FILES['imagen_producto']['tmp_name'])!="image/jpeg" && mime_content_type($_FILES['imagen_producto']['tmp_name'])!="image/png"){
            $alerta=[
                "tipo"=>"simple",
                "titulo"=>"Ocurrió un error inesperado",
                "texto"=>"La imagen que ha seleccionado es de un formato no permitido",
                "icono"=>"error"
            ];
            return json_encode($alerta);
            exit(); // todo para comprobar si es jpg o png
        }

        # Verificando peso de imagen #
        if(($_FILES['imagen_producto']['size']/1024)>5120){
            $alerta=[
                "tipo"=>"simple",
                "titulo"=>"Ocurrió un error inesperado",
                "texto"=>"La imagen que ha seleccionado supera el peso permitido",
                "icono"=>"error"
            ];
            return json_encode($alerta);
            exit();
        } // verificar si la imagen seleccionar no pesa mas de 5mb

        # Nombre de la foto #
        if($datos['imagen_producto']!=""){
            $foto=explode(".", $datos['img_hablador']); // al colocar explode y el punto se divide la cadena en dos partes una con el nombre y la otra con la extension
            $foto=$foto[0]; // entonces cuando colocamos aqui 0 elegimos el nombre y no la extension
        }else{
            $foto=str_ireplace(" ","_",'hablador');
            $foto=$foto."_".rand(0,100); // aqui con else se le crea el nombre desde cero a la foto
        }
        

        # Extension de la imagen #
        switch(mime_content_type($_FILES['img_hablador']['tmp_name'])){
            case 'image/jpeg':
                $foto=$foto.".jpg";
            break;
            case 'image/png':
                $foto=$foto.".png";
            break;
        } // aqui le agregamos la extension a la foto dependiendo de cual sea

        chmod($img_dir,0777); // permisos de lectura y escritura

        # Moviendo imagen al directorio #
        if(!move_uploaded_file($_FILES['img_hablador']['tmp_name'],$img_dir.$foto)){ // para mover la foto a la nueva carpeta 
            $alerta=[
                "tipo"=>"simple",
                "titulo"=>"Ocurrió un error inesperado",
                "texto"=>"No podemos subir la imagen al sistema en este momento",
                "icono"=>"error"
            ];
            return json_encode($alerta);
            exit();
        }

        # Eliminando imagen anterior #
        if(is_file($img_dir.$datos['img_hablador']) && $datos['img_hablador']!=$foto){ // comprobar si la foto anterior existe
            chmod($img_dir.$datos['img_hablador'], 0777); // dar los permisos
            unlink($img_dir.$datos['img_hablador']);  // eliminar la foto
        }

        $producto_datos_up=[ // array para el sql
            [
                "campo_nombre"=>"img_hablador",
                "campo_marcador"=>":Foto",
                "campo_valor"=>$foto
            ]
        ];

        $condicion=[ // array para el sql
            "condicion_campo"=>"id",
            "condicion_marcador"=>":ID",
            "condicion_valor"=>$id
        ];

        if($this->actualizarDatos("habla",$producto_datos_up,$condicion)){

            

            $alerta=[
                "tipo"=>"recargar",
                "titulo"=>"Foto actualizada",
                "texto"=>"La foto",
                "icono"=>"success"
            ];
        }else{

            $alerta=[
                "tipo"=>"recargar",
                "titulo"=>"Foto actualizada",
                "texto"=>"No hemos podido actualizar algunos datos del producto, sin embargo la foto ha sido actualizada",
                "icono"=>"warning"
            ];
        }

        return json_encode($alerta);
    }
}