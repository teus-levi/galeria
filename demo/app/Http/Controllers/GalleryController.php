<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Services\ImageService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

//Service
//Abstração - Esconder por trás de camadas detalhes complexos da nossa aplicação
//Interface

class GalleryController extends Controller
{

    protected $imageService;

    public function __construct(ImageService $imageService){
        $this->imageService = $imageService;
    }

    public function index(){
        $images = Image::all();
        return view('index', ['images' => $images]);
    }

    public function upload(Request $request){

        $this->validateRequest($request);

        $title = $request->only('title');
        $image = $request->file('image');

        try{
            $this->imageService->storeNewImage($image, $title['title']);
            //throw new Exception('....');

        } catch(Exception $error){
            // $this->imageService->deleteDatabaseImage($databaseImage);
            // $this->imageService->deleteImageFromDisk($databaseImage->url);
            $this->imageService->rollback();

            return redirect()->back()->withErrors([
                'error' => 'Erro ao salvar a imagem. Tente novamente.'
            ]);
        }


        return redirect()->route('index');

    }

    public function delete($id){
        $image = Image::findOrFail($id);
        //$image->delete();
        $url = parse_url($image->url); //divide a url
        $path = ltrim($url['path'], '/storage\/'); //pega uma parte específica


        if (Storage::disk('public')->exists($path)){
            Storage::disk('public')->delete($path); //exclui o arquivo
            $image->delete(); //exclui do banco
        }
        return redirect()->route('index');
    }

    private function validateRequest(Request $request){
        $request->validate([
            'title' => 'required|string|max:255|min:6',
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:2048', //2MB no máximo
                Rule::dimensions()->maxWidth(2000)->maxHeight(2000)
            ]
        ]);
    }

}
