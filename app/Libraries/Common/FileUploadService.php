<?php namespace App\Libraries;

use App\Libraries\Common\RandomGenerator;
/**
 * File System
 * 
 * The File System library is a library that manages 
 * the files on the server.
 * @author Bill Dwight Ijiran <dwight.ijiran@gmail.com>
 */
class FileSystem
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
        'DRIVERS_LICENSE' => 1,
        'IC_NO' => 2,
        'PASSPORT' => 3,
        'VEHICLE_REGISTRATION' => 4,
        'PROFILE_PHOTO' => 5,
        'WORK_PERMIT' => 6,
        'AGREEMENT' => 7,
        'PAYMENT_SLIP' => 8
    ];

    public const FILE_TYPE_VALUE = [
        1 => "Driver's License",
        2 => 'IC No',
        3 => 'Passport',
        4 => 'Vehicle Registration',
        5 => 'Profile Photo',
        6 => 'Work Permit',
        7 => 'Agreement',
        8 => 'Payment Slip'
    ];

    public const PATH = [
        'UPLOAD_DIR' => 'user_files',
        'DUMP_DIR' => WRITEPATH . 'dump'
    ];

    public const KEY_VALUES = [
        1 => 'driving_license_file',
        2 => 'ic_file',
        3 => 'passport_file',
        4 => 'vehicle_registration_file',
        5 => 'profile_photo',
        6 => 'work_permit',
        7 => 'agreement',
        8 => 'payment_slip'
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Use the Files table.
        $this->FileTable = new \App\Models\Files();
		$this->random = new RandomGenerator();
    }

    /**
     * Gets the files from the request. Uploads each file from 
     * the files object.
     *
     * This function will automatically retrieve the files[] object
     * on the passed request and read from there.
     * 
     * @param int $createdBy The creator of this save.
     * @param int $ownerId The owner of these files.
     * @param Request $request The full request/payload.
     * @return Array|string An array of result per file inside the files object or a general message.
     */
    public function saveFiles($createdBy, $ownerId, $request)
    {
        // Check the files object.
        if (!isset($request['files'])) {
            return null;
        }

        // Check if its empty.
        if (empty($request['files'])) {
            return null;
        }

        
        // If there are files, prepare the return variables.
        $numberOfUploadedFiles = 0;
        $numberOfFailedFiles = 0;
        $totalSizeOfUploadedFiles = 0;
        $debug_string = '--';

        // Copy the files.
        $files = $request['files'];

        // Flush
        unset($request['files']);

        // Loop through
        foreach ($files as $file) {
            

            if (!isset($file['file'])) {
                // Mark as failed.
                $numberOfFailedFiles += 1;
                continue;
            }

            // Create new file.
            $newFile = new \App\Entities\File();

            // Split the file
            $currentFile = explode(",", $file['file']);

            // Check
            if (!$currentFile[0] || !$currentFile[1]) {
                // Mark as failed.
                $numberOfFailedFiles += 1;
                continue;
            }

            // Try to build the file.
            try {
                $physicalFile = base64_decode($currentFile[1]);
            } catch (\Exception $e) {
                $numberOfFailedFiles += 1;
                continue;
            }

            // Get the file extension.
            $ext = $this->getFileType($currentFile[1]);
            $newFile->file_ext = $ext;

            // Enter file name.
            $newFile->file_past_name = (isset($file['file_name'])) ? $file['file_name'] : 'Untitled';
            $newFile->file_name = (isset($file['file_name'])) ? 'UCX_FILE_'.$this->random->generate(10,true).'_'.date('Ymd').'.'.$ext : 'Untitled';

            // Determine the file size.
            $fSize = $this->getFileSize($currentFile[1]);
            $newFile->file_type = $this::FILE_TYPE[$file['title']];
            $newFile->file_size = $fSize;
            $newFile->owner_id = $ownerId;
            $newFile->created_by = $createdBy;
            $newFile->modified_by = $createdBy;

            // Insert the file record into the database.
            if (!$this->FileTable->save($newFile)) {
                $numberOfFailedFiles += 1;
                $debug_string = $this->FileTable->errors();
                continue;
            }
            // Get the insert id. 
            $fileId = $this->FileTable->getInsertId();

            // Construct the path. 
            if (!file_exists($this::PATH['UPLOAD_DIR'])) {
                // Create the folder.
                mkdir($this::PATH['UPLOAD_DIR']);
            }
            if (!file_exists($this::PATH['UPLOAD_DIR'] . '/' . $ownerId)) {
                // Create the folder.
                mkdir($this::PATH['UPLOAD_DIR']  . '/' . $ownerId);
            }

            // Create the saveSource.
            $saveSource = $this::PATH['UPLOAD_DIR'] . '/' . $ownerId . '/' . $newFile->file_name;

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
            'debug_string' => $debug_string,
            'total_size_of_uploaded_files' => $totalSizeOfUploadedFiles,
            'last_file' => $fileId

        ];
    }

    /**
     * Retrieves all the files of the user and creates
     * an accessible URL out of it.
     *
     * @param [type] $user_id
     * @return void
     */
    public function retrieveFiles($user_id)
    {
        $files = $this->FileTable->where('owner_id', $user_id)
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
     * Retrieves all the files of the user and creates
     * an accessible URL out of it.
     *
     * @param [type] $id
     * @return void
     */
    public function retrieveFilesSingle($id)
    {
        $files = $this->FileTable->where('id', $id)
            ->where('deleted_at IS NULL')
            ->findAll();

        if (!$files) {
            return null;
        }

        $fileSet['files'] = [];
        $fileValue = [];
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
                    }else{
                        $fileValue = $this->getURL($file);
                    }
                } catch (Exception $e) {
                    $fileValue = $e->getMessage();
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
     * Deletes a file by id. 
     * When you delete a file, it gets dumped on the 
     * writable/dump and gets renamed with a .ucxdump file.
     *
     * @param [type] $id
     * @return bool True on success, False on fail.
     */
    public function delete($deletedBy, $id)
    {
        $file = $this->FileTable->find($id);

        if (!$file) {
            return false;
        }

        $file->modified_by = $deletedBy;
        $file->modified_at = date('Y-m-d H:i:s');
        $this->FileTable->save($file);

        if (!$this->FileTable->delete($id)) {
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

        $source = $this::PATH['UPLOAD_DIR'] . '/' . $file->owner_id . '/' . $file->file_name;
        $dest = $this::PATH['DUMP_DIR'] . '/' . $file->file_name . '.ucxdump';

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
        return getenv('app.baseURL') .'/'
        .$this::PATH['UPLOAD_DIR'] . '/'
        . $file->owner_id . '/'
        . $file->file_name;
    }

    /**
     * Directly sets the profile photo of the user.
     * This function will delete existing profile photo.
     * 
     * @param int $user_id
     * @param base64 $file
     * @return bool
     */
    public function setProfilePhoto($modifiedBy, $userId, $file)
    {
        $request['files'] = [
            [
                'title' => 'PROFILE_PHOTO',
                'file' => $file['file'],
                'file_name' => $file['file_name']
            ]
        ];

        $profilePhoto = $this->FileTable->where('file_type', $this::FILE_TYPE['PROFILE_PHOTO'])
            ->where('owner_id', $userId)
            ->where('deleted_at IS NULL')
            ->first();

        if ($profilePhoto) {
            $this->delete($modifiedBy, $profilePhoto->id);
        }

        if (!$this->saveFiles($modifiedBy, $userId, $request)) {
            return false;
        }

        return true;
    }

    /**
     * Gets the profile photo of the user.
     *
     * @param [type] $userId
     * @return void
     */
    public function getProfilePhoto($userId)
    {
        $file = $this->FileTable->where('file_type', $this::FILE_TYPE['PROFILE_PHOTO'])
            ->where('owner_id', $userId)
            ->where('deleted_at IS NULL')
            ->first();

        if (!$file) {
            return null;
        }

        return $this->getURL($file);
    }

    /**
     * Removes a current file of the type from user.
     * Only 1 file per type is allowed per user.
     * @param [type] $userId
     * @param [type] $type
     * @return void
     */
    public function removeFileType($adminId, $userId, $type)
    {
        $file = $this->FileTable->where('file_type', $type)
            ->where('owner_id', $userId)
            ->where('deleted_at IS NULL')
            ->first();

        if ($file) {
            $this->delete($adminId, $file->id);
        }
    }
}
