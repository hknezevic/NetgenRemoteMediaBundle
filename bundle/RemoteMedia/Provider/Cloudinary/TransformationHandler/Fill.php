<?php

namespace Netgen\Bundle\RemoteMediaBundle\RemoteMedia\Provider\Cloudinary\TransformationHandler;

use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value;
use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\Transformation\HandlerInterface;

/**
 * Class Fill.
 *
 * Create an image with the exact given width and height while
 * retaining the original aspect ratio, using only part of the
 * image that fills the given dimensions if necessary (only part
 * of the original image might be visible if the requested aspect
 * ratio is different from the original aspect ratio).
 */
class Fill implements HandlerInterface
{
    /**
     * Takes options from the configuration and returns
     * properly configured array of options.
     *
     * @param \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value $value
     * @param string $variationName name of the configured image variation configuration
     * @param array $config
     *
     * @return array
     */
    public function process(Value $value, $variationName, array $config = [])
    {
        $options = [
            'crop' => 'fill',
        ];

        if (isset($config[0]) && 0 !== $config[0]) {
            $options['width'] = $config[0];
        }

        if (isset($config[1]) && 0 !== $config[1]) {
            $options['height'] = $config[1];
        }

        return $options;
    }
}
