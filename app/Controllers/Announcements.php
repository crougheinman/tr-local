<?php 
namespace App\Controllers;
use App\Entities\Announcement;
use App\Libraries\Common\FileUploadService;
use App\Libraries\Common\Pagination;

class Announcements extends BaseController
{

    public const TYPE = [
        'GENERAL' => 1,
        'ADVISORY' => 2,
        'URGENT' => 3,
        'PROMO' => 4,
        'HOT_DEAL' => 5
    ];

    public const RENDER_AS = [
        1 => 'HTML',
        2 => 'FILE'
    ];

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->FileUploadService = new FileUploadService();
        $this->Pagination = new Pagination();
        //temporary
        $this->user_id = 1;
    }
    public function create()
    {
        $payload = $this->request->getJSON(true);
        $announcement = new Announcement($payload);
        $announcement->slug = mb_url_title($announcement->title,'-',TRUE);

        if(isset($payload['file'])){
            $upload_result = $this->FileUploadService->saveFile($this->user_id, $payload['file'],'announcements');
    
            if(!isset($upload_result['id'])){
                return $this->Response->failed(
                    'failed',
                    [
                        'message' => $upload_result
                    ]
                ); 
            }
            $announcement->banner = $upload_result['id'];
        }
        

        if($this->Announcements->save($announcement)){
            return $this->Response->success(
                'success',
                [
                    'id' => $this->Announcements->getInsertId(),
                ]
            );            
        }else{
            return $this->Response->failed(
                'failed',
                [
                    'message' => $this->Announcements->errors()
                ]
            );  
        }
    }

    public function retrieve($slug = null)
    { 
        if($slug!=null){
            if(!is_numeric($slug)){
                //IF NOT NUMERIC
                $slugged = mb_url_title($slug,'-',TRUE);
                $announcement = $this->Announcements->where('slug',$slugged)->find();

                //get the file
                $file = $this->FileUploadService->retrieveSingleFile($announcement->banner);
                $announcement->file = $file? $file : null ;

                if(!$announcement){
                    return $this->Response->success(
                        'failed',
                        [
                            'message'=> 'search keyword not found'
                        ]
                    );
                }

                return $this->Response->success(
                    'success',
                    [
                        'announcements'=> $announcement
                    ]
                );
            }
            
            $announcement = $this->Announcements->find($slug);
                
            //get the file
            $file = $this->FileUploadService->retrieveSingleFile($announcement->banner);
            $announcement->file = $file? $file : null ;

            if(!$announcement){
                return $this->Response->success(
                    'failed',
                    [
                        'message'=> 'id not found'
                    ]
                );
            }

            return $this->Response->success(
                'success',
                [
                    'announcements'=> $announcement
                ]
            );
        }else{

            $announcement = $this->Announcements->orderBy('created_at','DESC')->findAll();

            
            if(!$announcement){
                return $this->Response->success(
                    'failed',
                    [
                        'message'=> 'id not found'
                    ]
                );
            }


            // check if it is paginated
            $request = \Config\Services::request();
            if(!$request->getVar('page')){

                return $this->Response->success(
                    'success',
                    [
                        'announcements' => $announcement,
                    ]
                );

            }

            $announcement = $this->Pagination->execute(
                $announcement,
                $page,
                MAX_PROPERTIES_PER_PAGE
            );
    
            return $this->Response->success(
                'success',
                [
                    'announcements' => $announcement['result'],
                    'pagination' => $announcement['pagination']
                ]
            );
        }
    }

    public function page($page = 1)
    { 

        $announcement = $this->Announcements->orderBy('created_at','DESC')->findAll();
        
        if(!$announcement){
            return $this->Response->success(
                'failed',
                [
                    'message'=> 'id not found'
                ]
            );
        }

        // check if it is paginated
        
        $announcement = $this->Pagination->execute(
            $announcement,
            $page,
            MAX_PROPERTIES_PER_PAGE
        );

        return $this->Response->success(
            'success',
            [
                'announcements' => $announcement['result'],
                'pagination' => $announcement['pagination']
            ]
        );
    }


    public function update($id)
    {
        $search_announcement = $this->Announcements->find($id);
        if(!$search_announcement){
            return $this->Response->failed(
                'search_failed',
                [
                    'message' => 'Announcement ID: '.$id.' not found'
                ]
            );  
        }

        $payload = $this->request->getJSON(true);
        $announcement = new Announcement($payload);

        if(isset($payload['file'])){
            if(empty($payload['file'])){
                goto dont_upload;
            }

            $this->FileUploadService->delete($this->user_id, $search_announcement->banner);
            $upload_result = $this->FileUploadService->saveFile($this->user_id, $payload['file'],'announcements');
            if(!isset($upload_result['id'])){
                return $this->Response->failed(
                    'failed',
                    [
                        'message' => $upload_result
                    ]
                ); 
            }
            $announcement->banner = $upload_result['id'];

            dont_upload: $upload=false;
        }

        $announcement->id = $id;
        $announcement->slug = mb_url_title($announcement->title,'-',TRUE);
        
        if($this->Announcements->save($announcement)){
            return $this->Response->success(
                'success',
                [
                    'payload' => $announcement
                ]
            );          
        }else{
            return $this->Response->failed(
                'failed',
                [
                    'message' => $this->Announcements->errors()
                ]
            );  
        }
    }
    public function delete($id)
    {
        if(!$this->Announcements->find($id)){
            return $this->Response->failed(
                'search_failed',
                [
                    'message' => 'Announcement ID: '.$id.' not found'
                ]
            );  
        }
        if(!$this->Announcements->delete($id,false)){
            return $this->Response->failed(
                'failed',
                [
                    'message' => $this->Announcements->errors()
                ]
            ); 
        }
        return $this->Response->success(
            'success'
        );
    }
    //---------------------------------------------


}
?>