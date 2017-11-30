<?php

namespace Netgen\Bundle\RemoteMediaBundle\Controller;

use \Cloudinary;
use \Cloudinary\Api;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MenuPluginController extends Controller
{
    /** @var  Cloudinary */
    protected $cloudinary;

    /** @var  Api */
    protected $cloudinaryApi;

    /**
     * @todo: extract to the trait
     *
     * @param $cloudName
     * @param $apiKey
     * @param $apiSecret
     * @param bool $useSubdomains
     */
    public function initCloudinary($cloudName, $apiKey, $apiSecret, $useSubdomains = false)
    {
        $this->cloudinary = new Cloudinary();
        $this->cloudinary->config(
            [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'cdn_subdomain' => $useSubdomains
            ]
        );

        $this->cloudinaryApi = new Api();
    }

    public function index(Request $request)
    {
        $usageReport = $this->cloudinaryApi->usage()->getArrayCopy();

        return $this->render(
            'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:index.html.twig',
            [
                'usage' => $usageReport
            ]
        );
    }

    private function extractMedia($resources)
    {
        $media = [];
        foreach ($resources as $resource) {
            $finalOptions['transformation'] = [
                'media_lib_thumb'
            ];

            $finalOptions['format'] = 'jpg';

            $finalOptions['secure'] = true;
            $variationUrl = cloudinary_url_internal($resource['public_id'], $finalOptions);

            $data = $resource;
            $data['variation'] = $variationUrl;
            $media[] = $data;
        }

        return $media;
    }

    public function library(Request $request)
    {
        $type = $request->query->get('type', 'image');

        $options = array(
            'resource_type' => $type,
            'max_results' => 18
        );

        $resources = $this->cloudinaryApi->resources($options)->getArrayCopy();
        $media = $this->extractMedia($resources['resources']);

        return $this->render(
            'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:library.html.twig',
            [
                'next_cursor' => isset($resources['next_cursor']) ? $resources['next_cursor'] : false,
                'resources' => $media,
                'type' => $type
            ]
        );
    }

    public function loadMore(Request $request)
    {
        $type = $request->request->get('type', 'image');
        $nextCursor = $request->request->get('next_cursor');

        $options = [
            'resource_type' => $type,
            'max_results' => 18,
            'next_cursor' => $nextCursor
        ];

        $resources = $this->cloudinaryApi->resources($options)->getArrayCopy();
        $media = $this->extractMedia($resources['resources']);

        return new JsonResponse(
            [
                'html' => $this->renderView(
                    'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:library_list.html.twig',
                    [
                        'next_cursor' => isset($resources['next_cursor']) ? $resources['next_cursor'] : false,
                        'resources' => $media,
                        'type' => $type
                    ]
                ),
                'next_cursor' => isset($resources['next_cursor']) ? $resources['next_cursor'] : false
            ]
        );
    }

    private function handleFiles(array $files)
    {
        $languages = $this->container->get('ezpublish.config.resolver')->getParameter('languages');

        $repository = $this->container->get( 'ezpublish.api.repository' );
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $contentTypeService = $repository->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('image');

        $createdContent = [];

        $repository->beginTransaction();
        /** @var UploadedFile $file */
        foreach ($files as $file) {
            try {
                $contentCreateStruct = $contentService->newContentCreateStruct($contentType, $languages[0]);

                $contentCreateStruct->setField('name', $file->getClientOriginalName());
                $contentCreateStruct->setField('image', $file->getRealPath());

                $locationCreateStruct = $locationService->newLocationCreateStruct(51);
                $draft = $contentService->createContent($contentCreateStruct, array($locationCreateStruct));
                $createdContent[] = $contentService->publishVersion($draft->versionInfo);
            } catch (\Exception $e) {
                $repository->rollback();
            }
        }

        $repository->commit();

        return $createdContent;
    }

    public function uploadAndGenerate(Request $request)
    {
        if ($request->files->count() > 10) {
            return $this->render(
                'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:multiupload.html.twig',
                [
                    'error' => 'Too many files. Please select under 10 files'
                ]
            );
        }

        if ($request->getMethod() === 'POST') {
            $contentObjects = $this->handleFiles($request->files->get('file'));

            // @todo: redirect
            return $this->render(
                'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:multiupload_summary.html.twig',
                [
                    'content_objects' => $contentObjects
                ]
            );
        }

        // we need to display multi-upload
        // each uploaded image will automatically generate new ez content, with title set from the image and image itself set in remote media field
        // we'll need to limit to max 10 objects probably due to the memory/timeout limits

        // OPTIONAL: configure a way to quickly fill out one more field (description, caption, or something similar)
        return $this->render(
            'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:multiupload.html.twig'
        );
    }
}
