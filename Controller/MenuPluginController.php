<?php

namespace Netgen\Bundle\RemoteMediaBundle\Controller;

use \Cloudinary;
use \Cloudinary\Api;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

    public function library(Request $request)
    {
        $type = $request->query->get('type');

        $options = array(
            'resource_type' => $type
        );

        $resources = $this->cloudinaryApi->resources($options)->getArrayCopy();

        /*$items = $resources['resources'];
        while (!empty($resources['next_cursor'])) {
            $options['next_cursor'] = $resources['next_cursor'];
            $resources = $this->cloudinaryApi->resources($options)->getArrayCopy();

            if (!empty($resources['resources'])) {
                $items = array_merge($items, $resources['resources']);
            }
        }*/

        $media = [];
        foreach ($resources['resources'] as $resource) {
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

        return $this->render(
            'NetgenRemoteMediaBundle:ngadminui/plugin/dashboard:library.html.twig',
            [
                'resources' => $media,
                'type' => $type
            ]
        );
    }
}
