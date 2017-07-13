<?php

namespace Netgen\Bundle\RemoteMediaBundle\RemoteMedia\VariationResolver;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\VariationResolver as VariationResolverInterface;

class VariationResolver implements VariationResolverInterface
{
    protected $siteAccessList = array();

    /**
     * @var ConfigResolverInterface
     */
    protected $configResolver;

    protected $variations = array();

    public function __construct(ConfigResolverInterface $configResolver, $saList)
    {
        die(dump('ble'));
        $this->configResolver = $configResolver;

        foreach ($saList as $sa) {
            $variations = $this->configResolver->getParameter('image_variations', 'netgen_remote_media', $sa);
        }

        die(dump($variations));
    }

    /**
     * Return merged transformations defined for a provided content type and default ones.
     *
     * @param string $contentTypeIdentifier
     *
     * @return array
     */
    public function getVariationsForContentType($contentTypeIdentifier)
    {
        // also ignore siteaccess

        $defaultVariations = isset($this->variations['default']) ? $this->variations['default'] : array();
        $contentTypeVariations= isset($this->variations[$contentTypeIdentifier]) ?
            $this->variations[$contentTypeIdentifier] : array();

        return array_merge($defaultVariations, $contentTypeVariations);
    }

    public function getCroppableVariations($contentTypeIdentifier)
    {
        // try to get variations for all siteaccesses
        $saList = $this->container->getParameter('ezpublish.siteaccess.list');

        foreach ($saList as $sa) {
            $variations = $this->configResolver->getParameter('image_variations', 'netgen_remote_media', $sa);
        }

        $variations = $this->getVariationsForContentType($contentTypeIdentifier);

        $croppableVariations = array();
        foreach ($variations as $variationName => $variationOptions) {
            if (isset($variationOptions['transformations']['crop'])) {
                $croppableVariations[$variationName] = $variationOptions;
            }
        }

        return $croppableVariations;
    }
}
