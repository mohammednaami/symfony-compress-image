<?php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

// Define the path directory where the uploaded file will be stored
$target = '/optimizeImage/';

// Retrieve the uploaded file
$file = $request->files->get('image');


// Check if the file is an image
if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
    return new Response('Invalid format.', Response::HTTP_BAD_REQUEST);
}


// Generate a unique filename to avoid naming conflicts
$fileName = uniqid().'.'.$file->getClientOriginalExtension();

// Move the uploaded file to the target directory
try {
    $file->move($target, $fileName);
} catch (FileException $e) {
    return new Response('Unable to upload', Response::HTTP_INTERNAL_SERVER_ERROR);
}

// Compress and optimize the image
$image = new Imagick($target.'/'.$fileName);
$image->setImageCompressionQuality(80);
$image->setImageCompression(Imagick::COMPRESSION_JPEG);
$image->stripImage();
$image->writeImage($target.'/'.$fileName);

// Send the compressed image as a response
$response = new Response(file_get_contents($target.'/'.$fileName));
$response->headers->set('Content-Type', MimeTypeGuesser::getInstance()->guess($target.'/'.$fileName));
$response->headers->set('Content-Disposition', $response->headers->makeDisposition(
    ResponseHeaderBag::DISPOSITION_INLINE,
    $fileName
));
$response->headers->set('Content-Length', filesize($target.'/'.$fileName));

return $response;
