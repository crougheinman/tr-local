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
        $this->Files = new \App\Models\Files();
		$this->user_id = 1;
    }



    /**
     * Uploads a file and save its information to the database as
     * well as the creator and the upload details.
     * @param int $createdBy The creator and owner of this save.
     * @param Request $request The full request/payload.
     * @param Request $folder The name of the folder.
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


    /**
     * Uploads a file and save its information to the database as
     * well as the creator and the upload details.
     * @param Request $folder The name of the folder.
     * @param Request $entity_id The name of the folder.
     * @return void
     */

    public function retrieve($folder,$entity_id)
    { 

        $files = $this->Files
        ->where('entity_id',$entity_id)
        ->where('folder',$folder)
        ->findAll();

        
        if(!$files){
            return $this->Response->success(
                'failed',
                [
                    'message'=> 'file(s) not found'
                ]
            );
        }

        return $this->Response->success(
            'success',
            [
                'files'=> $files
            ]
        );
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