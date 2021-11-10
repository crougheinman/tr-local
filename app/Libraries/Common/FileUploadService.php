<?php namespace App\Libraries\Common;

use App\Libraries\Common\RandomGenerator;
/**
 * File System
 * 
 * The File System library is a library that manages 
 * the files on the server.
 * @author Bill Dwight Ijiran <dwight.ijiran@gmail.com> (Original) Modded by Greg
 */
class FileUploadService
{
    // Defined type indicators of a base 64 encoding.
    public const B64_FILETYPE_DEFINITION = [
        '/' => 'jpg',
        'i' => 'png',
        'R' => 'gif',
        'U' => 'docx',
        'J' => 'pdf',
    ];

    public const FILE_TYPE = [
        'DEPOSIT_ATTACHMENT' => 1,
        'REG_FILE_BIR_COR' => 2,
        'REG_FILE_DTI' => 3,
        'REG_FILE_BUSINESS_CLEARANCE' => 4,
        'REG_FILE_DOT_CERT' => 5,
        'ANNOUNCEMENT_BANNER' => 6,
    ];

    public const FILE_TYPE_VALUE = [
        1 => "Deposit Attachment",
        2 => 'BIR',
        3 => 'DTI',
        4 => 'Business Clearance',
        5 => 'DOT',
        6 => 'Announcement Banner',
    ];

    public const PATH = [
        'UPLOAD_DIR' => 'attachments',
        'DUMP_DIR' => WRITEPATH . 'dump'
    ];

    public const KEY_VALUES = [
        1 => 'deposit_attachment',
        2 => 'file_bir',
        3 => 'file_dti',
        4 => 'file_bc',
        5 => 'file_dot',
        6 => 'announcement_banner',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Use the Files table.
        $this->Files = new \App\Models\Files();
		$this->random = new RandomGenerator();
    }

    /**
     * Gets the files from the request. Uploads each file from 
     * the files object.
     *
     * This function will automatically retrieve the files[] object
     * on the passed request and read from there.
     * 
     * @param int $createdBy The creator and owner of this save.
     * @param Request $request The full request/payload.
     * @param Folder $folder The name of the folder.
     * @return Array|string An array of result per file inside the files object or a general message.
     */
    public function saveFiles($createdBy, $request)
    {
        // Check the files object.
        if (!isset($request['files'])) {
            return 'no files payload';
        }

        // Check if its empty.
        if (empty($request['files'])) {
            return 'empty file payload';
        }

        
        // If there are files, prepare the return variables.
        $numberOfUploadedFiles = 0;
        $numberOfFailedFiles = 0;
        $totalSizeOfUploadedFiles = 0;

        // Copy the files.
        $files = $request['files'];

        // Flush
        unset($request['files']);

        // Loop through
        foreach ($files as $file) {
            

            if (!isset($file['file'])) {
                // Mark as failed.
                return 'empty file key';
                $numberOfFailedFiles += 1;
                continue;
            }

            // Create new file.
            $newFile = new \App\Entities\File();

            // Split the file
            try {
                $currentFile = explode(",", $file['file']);
            } catch (\Exception $e) {
                return 'unable to explode: '. $e->getMessage();
                $numberOfFailedFiles += 1;
                continue;
            }

            // Check
            if (!$currentFile[0] || !$currentFile[1]) {
                // Mark as failed.
                return 'unable to explode II: '. $e->getMessage();
                $numberOfFailedFiles += 1;
                continue;
            }

            // Try to build the file.
            try {
                $physicalFile = base64_decode($currentFile[1]);
            } catch (\Exception $e) {
                return 'unable to encode to base64: '. $e->getMessage();
                $numberOfFailedFiles += 1;
                continue;
            }

            // Get the file extension.
            $ext = $this->getFileType($currentFile[1]);
            $newFile->file_ext = $ext;

            // Enter file name.
            $newFile->file_name = 'TR_FILE_'.$this->random->generate(10,true).'_'.date('Ymdhis').'.'.$ext;

            // Determine the file size.
            $fSize = $this->getFileSize($currentFile[1]);
            $newFile->entity_id = $request['id'];
            $newFile->folder = $request['folder'];
            $newFile->file_type = $this::FILE_TYPE[$file['title']];
            $newFile->file_size = $fSize;
            $newFile->created_by = $createdBy;
            $newFile->modified_by = $createdBy;

            // Insert the file record into the database.
            if (!$this->Files->save($newFile)) {
                $numberOfFailedFiles += 1;
                continue;
            }
            // Get the insert id. 
            $fileId = $this->Files->getInsertId();

            // Construct the path. 
            if (!file_exists($this::PATH['UPLOAD_DIR'])) {
                // Create the folder.
                mkdir($this::PATH['UPLOAD_DIR']);
            }

            // Construct the path. 
            if (!file_exists($this::PATH['UPLOAD_DIR'].'/'.$request['folder'])) {
                // Create the folder.
                mkdir($this::PATH['UPLOAD_DIR'].'/'.$request['folder']);
            }
    
            if (!file_exists($this::PATH['UPLOAD_DIR'] .'/'.$request['folder']. '/' . $request['id'])) {
                // Create the folder.
                mkdir($this::PATH['UPLOAD_DIR']  .'/'.$request['folder']. '/' . $request['id']);
            }

            // Create the saveSource.
            $saveSource = $this::PATH['UPLOAD_DIR'] .'/'.$request['folder']. '/' . $request['id'] . '/' . $newFile->file_name;

            // Save the physicalFile.
            try {
                file_put_contents($saveSource, $physicalFile);
            } catch (\Exception $e) {
                $numberOfFailedFiles += 1;
                continue;
            }

            $numberOfUploadedFiles += 1;
            $totalSizeOfUploadedFiles += $fSize;
        }
        return [
            'message' => 'The save process is finished.',
            'number_of_uploaded_files' => $numberOfUploadedFiles,
            'number_of_failed_files' => $numberOfFailedFiles,
            'total_size_of_uploaded_files' => $totalSizeOfUploadedFiles,

        ];
    }

    /**
     * Gets the file from the request. Uploads the file from 
     * the files object.
     * 
     * @param int $createdBy The creator and owner of this save.
     * @param Request $file The request on the payload.
     * @param Request $folder The folder entity.
     * @return Array|string An array of result per file inside the files object or a general message.
     */
    public function saveFile($createdBy, $request, $folder)
    {
        // Check the files object.
        if (!isset($request['file'])) {
            return 'empty payload';
        }

        // Check if its empty.
        if (empty($request['file'])) {
            return 'empty file';
        }

        if (!isset($request['file']['file'])) {
            return 'empty physical payload';
        }

        if (empty($request['file']['file'])) {
            return 'empty physical file';
        }

        // Create new file.
        $newFile = new \App\Entities\File();

        // Split the file
        try {
            $currentFile = explode(",", $request['file']['file']);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        // Check
        if (!$currentFile[0] || !$currentFile[1]) {
            return 'error on exploding the file';
        }

        // Try to build the file.
        try {
            $physicalFile = base64_decode($currentFile[1]);
        } catch (\Exception $e) {
            return 'error building the file';
        }

        // Get the file extension.
        $ext = $this->getFileType($currentFile[1]);
        $newFile->file_ext = $ext;

        // Enter file name.
        $newFile->file_name = 'TR_FILE_'.$this->random->generate(10,true).'_'.date('Ymdhis').'.'.$ext;
        
        // Determine the file size.
        $fSize = $this->getFileSize($currentFile[1]);
        $newFile->entity_id = $request['id'];
        $newFile->folder = $folder;
        $newFile->file_type = $this::FILE_TYPE[$request['file']['title']];
        $newFile->file_size = $fSize;
        $newFile->created_by = $createdBy;
        $newFile->modified_by = $createdBy;

        // Insert the file record into the database.
        $saved_file = [];
        if ($this->Files->save($newFile)) {
            $saved_file['id'] = $this->Files->getInsertId();
        }else{
            return 'error saving the file to database';
        }

        // Construct the path. 
        if (!file_exists($this::PATH['UPLOAD_DIR'])) {
            // Create the folder.
            mkdir($this::PATH['UPLOAD_DIR']);
        }
        
        // Construct the path. 
        if (!file_exists($this::PATH['UPLOAD_DIR'].'/'.$folder)) {
            // Create the folder.
            mkdir($this::PATH['UPLOAD_DIR'].'/'.$folder);
        }

        if (!file_exists($this::PATH['UPLOAD_DIR'] .'/'.$folder. '/' . $request['id'])) {
            // Create the folder.
            mkdir($this::PATH['UPLOAD_DIR']  .'/'.$folder. '/' . $request['id']);
        }

        // Create the saveSource.
        $saveSource = $this::PATH['UPLOAD_DIR'] .'/'.$folder. '/' . $request['id'] . '/' . $newFile->file_name;

        // Save the physicalFile.
        try {
            file_put_contents($saveSource, $physicalFile);
        } catch (\Exception $e) {
            return 'unable to save the physical file';
        }

        return $saved_file;
    }

    /**
     * Retrieves all the files of the entity and creates
     * an accessible URL out of it.
     *
     * @param [type] $folder
     * @param [type] $entity_id
     * @return void
     */
    public function retrieveFiles($folder, $entity_id)
    {
        $files = $this->Files->where('folder', $folder)
            ->where('entity_id', $entity_id)
            ->where('deleted_at', NULL)
            ->findAll();

        if (!$files) {
            return null;
        }

        $fileSet['files'] = [];
        
        foreach ($files as $file) {
            $key = $this::KEY_VALUES[$file->file_type];
            $isImage = $file->file_ext == "jpg" || $file->file_ext == "png" || $file->file_ext == "gif" ? true : false;
            
            if ($isImage) {
                $fileValue = strval($this->getURL($file));
            } else {
                try {
                    $f_content = @file_get_contents($this->getURL($file));
                    if($f_content){
                        $fileValue = json_encode(base64_encode(file_get_contents($this->getURL($file))));
                    }
                }
                catch(Exception $e) {
                    echo 'Message: ' .$e->getMessage();
                }
            }
            
            $fileSet['files'][$key] = [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file' => $fileValue
            ];
        }

        return $fileSet;
    }

    /**
     * Retrieves a single file
     *
     * @param [type] $id
     * @return void
     */
    public function retrieveSingleFile($id)
    {
        $file = $this->Files->where('id', $id)
            ->where('deleted_at IS NULL')
            ->first();

        if (!$file) {
            return null;
        }

        $fileSet=[];
        $fileValue = [];

        $isImage = $file->file_ext == "jpg" || $file->file_ext == "png" || $file->file_ext == "gif" ? true : false;
            
        if ($isImage) {
            $fileValue = strval($this->getURL($file));
        } else {
            try {
                $f_content = @file_get_contents($this->getURL($file));
                if($f_content){
                    $fileValue = json_encode(base64_encode(file_get_contents($this->getURL($file))));
                }else{
                    $fileValue = $this->getURL($file);
                }
            } catch (Exception $e) {
                $fileValue = $e->getMessage();
            }
            
        }

        $fileSet= [
            'id' => $file->id,
            'file_name' => $file->file_name,
            'file' => $fileValue
        ];

        return $fileSet;
    }

    /**
     * Deletes a file by id. 
     * When you delete a file, it gets dumped on the 
     * writable/dump and gets renamed with a .trdump file.
     *
     * @param [type] $id
     * @return bool True on success, False on fail.
     */
    public function delete($deletedBy, $id)
    {
        $file = $this->Files->find($id);

        if (!$file) {
            return false;
        }

        $file->modified_by = $deletedBy;
        $file->modified_at = date('Y-m-d H:i:s');
        $this->Files->save($file);

        if (!$this->Files->delete($id)) {
            return false;
        }

        if (!$this->moveToDump($file)) {
            return false;
        }

        return true;
    }

    /**
     * Moves a file to the dump.
     *
     * @param [type] $file
     * @return void
     */
    public function moveToDump($file)
    {
        if (!file_exists($this::PATH['DUMP_DIR'])) {
            mkdir($this::PATH['DUMP_DIR']);
        }

        $source = $this::PATH['UPLOAD_DIR'] . '/' . $file->folder . '/' . $file->created_by . '/' . $file->file_name;
        $dest = $this::PATH['DUMP_DIR'] . '/' . $file->file_name . '.trdump';

        if (!copy($source, $dest)) {
            return false;
        }

        return unlink($source);

    }

    /**
     * Gets a file type from the base64 string.
     *
     * @param base64 $base64
     * @return void
     */
    public function getFileType($base64)
    {
        return $this::B64_FILETYPE_DEFINITION[$base64[0]];
    }

    /**
     * Gets the file size of the base64 string.
     *
     * @param base64 $base64
     * @return void
     */
    public function getFileSize($base64)
    {
        return (strlen($base64) * (3 / 4)) - 2;
    }

    /**
     * Gets the owner id from the request.
     * Returns null if not exist.
     *
     * @param Request $request
     * @return int|null
     */
    public function getOwnerId($request)
    {
        return (isset($request['owner_id'])) ? $request['owner_id'] : null;
    }

    /**
     * Get URL of file.
     *
     * @param [type] $file
     * @return void
     */
    public function getURL($file)
    {
        return getenv('app.fileURL') .'/'
        .$this::PATH['UPLOAD_DIR'] . '/'
        . $file->folder . '/'
        . $file->created_by . '/'
        . $file->file_name;
    }

}
