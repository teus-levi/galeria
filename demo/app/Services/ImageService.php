<?php
namespace App\Services;

use App\Interfaces\ImageServiceInterface;
use App\Models\Image;
use Error;
use Exception;
use Illuminate\Support\Facades\Storage;

class ImageService implements ImageServiceInterface {

    private $rollbackStack = null;

    public function storeNewImage($image, $title) : Image {
        try {
            $url = $this->storeImageInDisk($image);
            return $this->storageImageInDatabase($title, $url);
        } catch(Exception $e){
            throw new Error('Erro ao gravar a imagem, tente novamente.' . $e);
        }

    }

    public function rollback(){
        //sleep(5);
        //transformando a fila em pilha
        /*if(!empty($this->rollbackQueue)){
            for ($i=count($this->rollbackQueue) - 1; $i >= 0; $i--) {
                $method = $this->rollbackQueue[$i]['method'];
                $params = $this->rollbackQueue[$i]['params'];

                if(method_exists($this, $method)){
                    call_user_func_array([$this,$method], $params);
                }
            }
            dd();
        }*/
        while(!empty($this->rollbackStack)){
            $rollbackAction = array_pop($this->rollbackStack);

            $method = $rollbackAction['method'];
            $params = $rollbackAction['params'];
            if(method_exists($this, $method)){
                call_user_func_array([$this,$method], $params);
            }
        }
    }

    private function storeImageInDisk($image) : string {
        $imageName = $image->storePublicly('uploads', 'public'); //poderia usar $image->hashName()

        $url = asset('storage/' .$imageName);
        $this->addToRollbackQueue('deleteImageFromDisk', [$url]);
        return $url;
    }

    private function storageImageInDatabase($title, $url) : Image {
        $image =  Image::create([
            'title' => $title,
            'url' => $url
        ]);
        //para fazer o rollback
        $this->addToRollbackQueue('deleteDatabaseImage', [$image]);
        return $image;
    }

    public function deleteImageFromDisk($imageUrl) : bool{
        $imagePath = str_replace(asset('storage/'), '', $imageUrl);
        Storage::disk('public')->delete($imagePath);
        return true;
    }

    public function deleteDatabaseImage($databaseImage) : bool{
        if($databaseImage){
            $databaseImage->delete();
            return true;
        }
            return false;
    }

    private function addToRollbackQueue($method, $params = []) {
        $this->rollbackStack[] = [
            'method' => $method,
            'params' => $params
        ];
    }
}
