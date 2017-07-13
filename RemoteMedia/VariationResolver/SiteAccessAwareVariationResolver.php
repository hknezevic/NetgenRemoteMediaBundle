<?php

namespace Netgen\Bundle\RemoteMediaBundle\RemoteMedia\VariationResolver;

use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\VariationResolver;

class SiteAccessAwareVariationResolver implements VariationResolver
{
    protected $variations = array();

    /**
     * Dynamic injection of configured transformations
     *
     * @param array $variations
     */
    public function setVariations($variations = array())
    {
        $this->variations = $variations;
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
        $defaultVariations = isset($this->variations['default']) ? $this->variations['default'] : array();
        $contentTypeVariations= isset($this->variations[$contentTypeIdentifier]) ?
            $this->variations[$contentTypeIdentifier] : array();

        return array_merge($defaultVariations, $contentTypeVariations);
    }

    public function getCroppableVariations($contentTypeIdentifier)
    {
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
