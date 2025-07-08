<?php

// src/Service/DocumentPathResolver.php
namespace App\Service;

use App\Entity\Document;

class DocumentPathResolver
{
    public function __construct(
        private readonly string $projectDir
    ) {}

    public function getFullFilePath(Document $document): string
    {
        if (!$document->getFilePath()) {
            throw new \LogicException('File path not set');
        }

        return sprintf('%s/public/uploads/documents/%s/%s',
            $this->projectDir,
            $document->getUploadDir(), // make public if needed
            $document->getFileName()
        );
    }
}
