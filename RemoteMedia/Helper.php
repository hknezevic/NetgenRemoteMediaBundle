<?php

namespace Netgen\Bundle\RemoteMediaBundle\RemoteMedia;

use eZ\Publish\SPI\Persistence\Content\Field;
use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value;
use Netgen\Bundle\RemoteMediaBundle\Core\Persistence\Legacy\RemoteMedia\Handler;
use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProviderInterface;

class Helper
{
    /**
     * @var \Netgen\Bundle\RemoteMediaBundle\Core\Persistence\Legacy\RemoteMedia\Handler
     */
    protected $dbHandler;

    /**
     * @var \Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProviderInterface
     */
    protected $provider;

    /**
     * Helper constructor.
     * @param Netgen\Bundle\RemoteMediaBundle\Core\Persistence\Legacy\RemoteMedia\Handler $handler
     * @param \Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProviderInterface $provider
     */
    public function __construct(Handler $handler, RemoteMediaProviderInterface $provider)
    {
        $this->dbHandler = $handler;
        $this->provider = $provider;
    }

    /**
     * Loads the field value from the database
     *
     * @param mixed $fieldId
     * @param mixed $versionId
     *
     * @return \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia
     */
    public function loadValue($fieldId, $versionId)
    {
        return $this->dbHandler->loadValue($fieldId, $versionId);
    }

    /**
     * Loads available formats for the field
     *
     * @param mixed $fieldId
     * @param mixed $versionId
     *
     * @return array
     */
    public function loadAvailableFormats($fieldId, $versionId)
    {
        $fieldSettings = $this->dbHandler->loadFieldSettingsByFieldId($fieldId, $versionId);

        return !empty($fieldSettings['formats']) ? $fieldSettings['formats'] : array();
    }

    /**
     * Updates the field in the database with the provided value
     *
     * @param \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value $value
     * @param mixed $fieldId
     * @param mixed $contentVersionId
     *
     * @return \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value
     */
    public function updateValue($value, $fieldId, $contentVersionId)
    {
        return $this->dbHandler->update($value, $fieldId, $contentVersionId);
    }

    /**
     * Uploads the local file to the remote provider and returns new Value
     *
     * @param string $fileUri
     * @param string $fileName
     * @param mixed|null $fieldId
     * @param mixed|null $contentVersionId
     *
     * @return \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value
     */
    public function upload($fileUri, $fileName, $fieldId = null, $contentVersionId = null)
    {
        if (!empty($fieldId) && !empty($contentVersionId)) {
            $folder = $fieldId . '/' . $contentVersionId;
        }

        $fileName = $this->filenameCleanUp($fileName);

        $options = array(
            'public_id' => $fileName.'/'.$folder,
            'overwrite' => true,
            'context' => array(
                'alt' => '',
                'caption' => '',
            ),
            'resource_type' => 'auto'
        );

        $response = $this->provider->upload($fileUri, $options);

        return $this->provider->getValueFromResponse($response);
    }

    public function getValueFromRemoteResource($resourceId, $resourceType)
    {
        $response = $this->provider->getRemoteResource($resourceId, $resourceType);

        return $this->provider->getValueFromResponse($response);
    }

    public function getVariationFromValue($value, $variantName, $availableFormats)
    {
        $availableFormats = $this->provider->getVariation($value, $availableFormats, $variantName);
    }

    /**
     * Adds the tag to the value
     *
     * @param $fieldId
     * @param $versionId
     * @param $tag
     *
     * @return array list of tags for the value
     */
    public function addTag($fieldId, $versionId, $tag)
    {
        /** @var \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value $value */
        $value = $this->loadValue($fieldId, $versionId);
        $metaData = $value->metaData;
        $attributeTags = !empty($metaData['tags']) ? $metaData['tags'] : array();

        $result = $this->provider->addTagToResource($resourceId, $tag);
        $attributeTags[] = $tag;

        $metaData['tags'] = $attributeTags;
        $value->metaData = $metaData;

        $this->updateValue($value, $fieldId, $versionId);

        return $attributeTags;
    }

    /**
     * Removes the tag from the value
     *
     * @param $fieldId
     * @param $versionId
     * @param $tag
     *
     * @return array list of tags for the value
     */
    public function removeTag($fieldId, $versionId, $tag)
    {
        /** @var \Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value $value */
        $value = $this->loadValue($fieldId, $versionId);
        $metaData = $value->metaData;
        $attributeTags = !empty($metaData['tags']) ? $metaData['tags'] : array();

        $result = $this->provider->removeTagFromResource($resourceId, $tag);
        $attributeTags = array_diff($attributeTags, array($tag));

        $metaData['tags'] = $attributeTags;
        $value->metaData = $metaData;

        $this->updateValue($value, $fieldId, $versionId);

        return $attributeTags;

    }

    /**
     * Cleans up the file name for uploading
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function filenameCleanUp($fileName)
    {
        $clean = preg_replace("/[^\p{L}|\p{N}]+/u", '_', $fileName);
        $cleanFileName = preg_replace("/[\p{Z}]{2,}/u", '_', $clean);

        return rtrim($cleanFileName, '_');
    }
}