<?php 
namespace App\Controllers;
use App\Entities\File;
use App\Libraries\Common\FileUploadService;

class Files extends BaseController
{
    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->FileUploadService = new FileUploadService();
		$this->user_id = 1;
    }



    /**
     * Uploads a file and save its information to the database as
     * well as the creator and the upload details.
     *
     * @return void
     */
    public function create()
    {

        // Get the payload.
        $payload = $this->request->getJSON(true);

        // Check payload existence.
        if (!$payload) {
            return $this->Response->failed(
                'failed',
                [
                    'message' => 'not found'
                ]
            );  
        }

        // Save the files.
        $result = $this->FileUploadService->saveFiles($this->user_id, $payload);

        return $this->Response->success(
            'success',
            [
                'result' => $result
            ]
        );
    }

    public function retrieve($id = null)
    { 
        if($id!=null){
            
            $file = $this->Files->find($id);

            if(!$file){
                return $this->Response->success(
                    'failed',
                    [
                        'message'=> 'files not found'
                    ]
                );
            }

            return $this->Response->success(
                'success',
                [
                    'file'=> $this->FileUploadService->retrieveFilesSingle($id)
                ]
            );
        }else{

            $file = $this->Files->findAll();

            
            if(!$file){
                return $this->Response->success(
                    'failed',
                    [
                        'message'=> 'file not found'
                    ]
                );
            }

            return $this->Response->success(
                'success',
                [
                    'file'=> $this->FileUploadService->retrieveFiles($id)['files']
                ]
            );
        }
    }

    public function delete($id)
    {

        if (!$this->FileSystem->delete($this->user_id, $id)) {
            return $this->Response->failed(
                'failed',
                [
                    'message' => 'error occured upon deleting the file'
                ]
            );
        }

        return $this->Response->success(
            'success',
            [
                'message' => 'file has been deleted'
            ]
        );
    }
}
?>