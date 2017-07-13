<?php

namespace Netgen\Bundle\RemoteMediaBundle\RemoteMedia;

interface VariationResolver
{
    /**
     * Return merged transformations defined for a provided content type and default ones.
     *
     * @param string $contentTypeIdentifier
     *
     * @return array
     */
    public function getVariationsForContentType($contentTypeIdentifier);

    public function getCroppableVariations($contentTypeIdentifier);
}
