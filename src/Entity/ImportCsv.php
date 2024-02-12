<?php

namespace App\Entity;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
// todo: remove this class
class ImportCsv
{
    /**
     * @var File
     *
     * @Assert\File(mimeTypes={ "application/octet-stream", "text/plain", "text/csv" })
     */
    private $csvFile;

    /**
     * @return File
     */
    public function getCsvFile(): ?File
    {
        return $this->csvFile;
    }

    /**
     * @param File $csvFile
     * @return ImportCsv
     */
    public function setCsvFile(File $csvFile): ImportCsv
    {
        $this->csvFile = $csvFile;
        return $this;
    }


}