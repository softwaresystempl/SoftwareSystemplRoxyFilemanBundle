<?php
/**
 * This file is part of the Roxyfileman Bundle
 *
 * (c) Jonas Renaudot <jonas.renaudot@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this code source
 */

namespace Softwaresystem\RoxyFilemanBundle\Controller;

use Softwaresystem\RoxyFilemanBundle\FileSystem\FileSystemInterface;
use Softwaresystem\RoxyFilemanBundle\FileSystem\LocalFileSystem;
use Softwaresystem\RoxyFilemanBundle\FileSystem\StandardResponse;
use Softwaresystem\RoxyFilemanBundle\FileSystem\StandardResponseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DirectoryController extends Controller
{
    public function dirListAction($profile = null)
    {
        $directories = $this->getFileSystem($profile)->getDirectoryTreeList();

        $output = array();

        foreach($directories as $directory){
            $output[] = array(
                'p' => $directory->getPath(),
                'f' => $directory->getFilesQuantity(),
                'd' => $directory->getSubdirectoriesQuantity()
            );
        }

        $response = new JsonResponse($output);
        return $response;
    }

    public function createDirAction(Request $request, $profile = null)
    {
        $path = $request->query->get('d');
        $directoryName = $request->query->get('n');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->createDirectory($path, $directoryName)
        );
    }

    public function deleteDirAction(Request $request, $profile = null)
    {
        $path = $request->query->get('d');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->deleteDirectory($path)
        );

    }

    public function moveDirAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('d');
        $destination = $request->query->get('n');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->moveDirectory($origin, $destination)
        );
    }

    public function copyDirAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('d');
        $destination = $request->query->get('n');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->copyDirectory($origin, $destination)
        );
    }

    public function renameDirAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('d');
        $destination = $request->query->get('n');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->renameDirectory($origin, $destination)
        );
    }

    public function fileListAction(Request $request, $profile = null)
    {
        $files = $this->getFileSystem($profile)->getFilesList($request->query->get('d'));

        $output = array();

        foreach($files as $file){
            $output[] = array(
                'p' => $file->getPath(),
                's' => $file->getSize(),
                't' => $file->getLastModificationTimestamp(),
                'w' => $file->getWidth(),
                'h' => $file->getHeight()
            );
        }

        $response = new JsonResponse($output);
        return $response;
    }

    public function uploadAction(Request $request, $profile = null)
    {
        $origin = $request->request->get('d');
        $files = $request->files->get('files');

        if(!is_array($files)){
            $files = array($files);
        }

        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->upload($origin, $files)
        );
    }

    public function downloadAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('f');
        $result = $this->getFileSystem($profile)->download($origin);

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $result->getContentType());
        $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename=' . $result->getFilename());
        $response->setCallback($result->getCallback());

        return $response;
    }

    public function downloadDirAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('d');
        $result = $this->getFileSystem($profile)->downloadDirectory($origin);

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $result->getContentType());
        $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename=' . $result->getFilename());
        $response->setCallback($result->getCallback());

        return $response;
    }

    public function deleteFileAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('f');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->deleteFile($origin)
        );
    }

    public function moveFileAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('f');
        $destination = $request->query->get('n');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->moveFile($origin, $destination)
        );
    }

    public function copyFileAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('f');
        $destination = $request->query->get('n');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->copyFile($origin, $destination)
        );
    }

    public function renameFileAction(Request $request, $profile = null)
    {
        $origin = $request->query->get('f');
        $destination = $request->query->get('n');
        return $this->standardResponseToHTTPResponse(
            $this->getFileSystem($profile)->renameFile($origin, $destination)
        );
    }

    public function generateThumbAction(Request $request, $profile = null)
    {
        $fileName = $request->query->get('f');
        $width = $request->query->get('width', 200);
        $height = $request->query->get('height', 200);

        $downloadableFile = $this->getFileSystem($profile)->thumbnail($fileName, $width, $height);

        $response = new StreamedResponse($downloadableFile->getCallback());
        $response->headers->set('Content-Type', $downloadableFile->getContentType());

        return $response;
    }


    /**
     * @return FileSystemInterface
     */
    private function getFileSystem($profile) {
        //$fileSystem = new LocalFileSystem('/var/www/tmpArticles', '/tmpArticles');
        if ($profile === null) {
            return $this->get('softwaresystem_roxy_fileman.file_system');
        }
        return $this->get('softwaresystem_roxy_fileman.' . $profile . '.file_system');
    }

    private function standardResponseToHTTPResponse(StandardResponseInterface $response){
        $data = array(
            'res' => $response->isSuccess() ? 'ok' : 'error',
            'msg' => $response->getErrorMessage()
        );

        return new JsonResponse($data);
    }
}
