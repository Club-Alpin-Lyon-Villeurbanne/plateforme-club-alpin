<?php

// débug
// Handle file uploads via XMLHttpRequest
class qqUploadedFileXhr
{
    /**
     * Save the file to the specified path.
     *
     * @return bool TRUE on success
     */
    public function save($path)
    {
        $input = fopen('php://input', 'r');
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->getSize()) {
            return false;
        }

        $target = fopen($path, 'w');
        fseek($temp, 0, \SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return true;
    }

    public function getName()
    {
        return $_GET['qqfile'];
    }

    public function getSize()
    {
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            return (int) $_SERVER['CONTENT_LENGTH'];
        }
        throw new Exception('Getting content length is not supported.');
    }
}
// Handle file uploads via regular form post (uses the $_FILES array)
class qqUploadedFileForm
{
    /**
     * Save the file to the specified path.
     *
     * @return bool TRUE on success
     */
    public function save($path)
    {
        if (!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)) {
            return false;
        }

        return true;
    }

    public function getName()
    {
        return $_FILES['qqfile']['name'];
    }

    public function getSize()
    {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader
{
    private $allowedExtensions = [];
    private $sizeLimit = 10485760;
    private $file;

    public function __construct(array $allowedExtensions = ['jpeg', 'jpg', 'gif', 'png', 'bmp', 'webp'], $sizeLimit = 20 * 1024 * 1024)
    {
        $allowedExtensions = array_map('strtolower', $allowedExtensions);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;

        $this->checkServerSettings();

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false;
        }
    }

    private function checkServerSettings()
    {
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));
        /*
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
        }
        */
    }

    private function toBytes($str)
    {
        $val = trim($str);
        $last = strtolower($str[strlen($str) - 1]);
        switch ($last) {
            case 'g': $val *= 1024;
            // no break
            case 'm': $val *= 1024;
            // no break
            case 'k': $val *= 1024;
        }

        return $val;
    }

    /**
     * Returns array('success'=>true) or array('error'=>'error message').
     */
    public function handleUpload($uploadDirectory, $replaceOldFile = false)
    {
        if (!is_writable($uploadDirectory)) {
            return ['error' => "Server error. Upload directory $uploadDirectory isn't writable."];
        }

        if (!$this->file) {
            return ['error' => 'No files were uploaded.'];
        }

        $size = $this->file->getSize();

        if (0 == $size) {
            return ['error' => 'File is empty'];
        }

        if ($size > $this->sizeLimit) {
            return ['error' => 'File is too large'];
        }

        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        $ext = $pathinfo['extension'];

        if ($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions, true)) {
            $these = implode(', ', $this->allowedExtensions);

            return ['error' => 'Le fichier '.$filename.' n\'a pas la bonne extension. Les extensions autorisées sont '.$these.'.'];
        }

        if (!$replaceOldFile) {
            /// don't overwrite previous files that were uploaded
            $suffixe = '';
            while (file_exists($uploadDirectory.$filename.$suffixe.'.'.$ext)) {
                // $filename .= rand(10, 99);
                $suffixe = (int) $suffixe + 1;
            }
            $filename .= $suffixe;
        }

        if ($this->file->save($uploadDirectory.$filename.'.'.$ext)) {
            return ['success' => true, 'filename' => ($filename.'.'.$ext)];
        }

        return ['error' => 'Could not save uploaded file.'.
                'The upload was cancelled, or server error encountered', ];
    }
}
