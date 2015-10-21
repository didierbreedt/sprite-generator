<?php

namespace SpriteGenerator\Services;

use SpriteGenerator\Exception\SpriteException;
use SpriteGenerator\CssFormatter\PlainCssFormatter;
use SpriteGenerator\CssFormatter\SassFormatter;
use SpriteGenerator\Positioner\OneColumnPositioner;
use SpriteGenerator\Positioner\MinImageSizePositioner;
use SpriteGenerator\ImageGenerator\Gd2Generator;


/**
 * Sprite generator service
 *
 * @TODO: check if method visibility is correct
 */
class SpriteService
{
    /**
     * All sprite configs
     * @var
     */
    private $config = array();

    /**
     * Active sprite name
     * @var string
     */
    private $activeSprite = null;

    /**
     * @param $config array
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $name string
     * @return mixed
     * @throws \SpriteGenerator\Exception\SpriteException
     */
    public function getConfigParam($name, $default=null)
    {
        if (!isset($this->config[$this->activeSprite][$name]) AND empty($default)) {
            throw new SpriteException('Sprite config "' . $name . '" is not set.');
        }
        else if (!empty($default)) {
            return $default;
        }

        return $this->config[$this->activeSprite][$name];
    }

    /**
     * @param $spriteName
     */
    public function setActiveSprite($spriteName)
    {
        $this->activeSprite = $spriteName;
    }

    /**
     * @param $spriteName string
     * @return array
     * @throws \SpriteGenerator\Exception\SpriteException
     */
    protected function getSpriteList($spriteName)
    {
        $spriteList = $this->getConfig();
        if (empty($spriteList)) {
            throw new SpriteException('No sprite configs found');
        }

        if ($spriteName) {
            if (!isset($spriteList[$spriteName])) {
                throw new SpriteException('Sprite config for ' . $spriteName . ' not found');
            }

            $spriteList = array($spriteName => $spriteList[$spriteName]);
        }

        return $spriteList;
    }

    /**
     * @param bool $spriteName
     * @return bool
     * @throws \SpriteGenerator\Exception\SpriteException
     */
    public function generateSprite($spriteName = false)
    {
        $spriteList = $this->getSpriteList($spriteName);

        foreach ($spriteList as $spriteName => $spriteInfo) {
            if (file_exists($spriteInfo['inDir']) === false) {
                throw new SpriteException('Image source directory doesn\'t exist');
            }

            $this->setActiveSprite($spriteName);
            $this->create();
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function create()
    {
        $images = $this->getSpriteSourceImages();

        $this->createSpriteImage($images);
        $this->createSpriteCss($images);
        $this->createSpriteJson($images);

        return true;
    }

    /**
     * @return array
     */
    protected function getSpriteSourceImages()
    {
        $images = array();

        if ( $this->getConfigParam('fileList') ) {
            foreach ( $this->getConfigParam('fileList') as $fileUri ) {
                $filename = substr($fileUri, strrpos($fileUri, DIRECTORY_SEPARATOR) + 1);
                $fileCode = substr($filename, 0, strrpos($filename, '.'));
                $images[$fileCode]['file'] = $fileUri;
            }
        }
        else {
            $sourceDir = $this->getConfigParam('inDir');
            $dh = opendir($sourceDir);

            while ( false !== ($filename = readdir($dh)) ) {
                if ( !is_file($sourceDir . $filename) ) {
                    continue;
                }
                $fileCode = substr($filename, 0, strrpos($filename, '.'));

                if ( $this->getConfigParam('extensions', false) ) {
                    $fileExtension = substr($filename, strrpos($filename, '.') + 1);
                    if ( !in_array($fileExtension, $this->getConfigParam('extensions')) ) {
                        continue;
                    }
                }

                $images[$fileCode]['file'] = $sourceDir . $filename;
            }
        }

        asort($images);

        return $images;
    }

    /**
     * @param $images
     * @throws \SpriteGenerator\Exception\SpriteException
     * @return bool
     */
    protected function createSpriteImage(&$images)
    {
        $padding = $this->getConfigParam('padding');
        $resultImage = $this->getConfigParam('outImage');

        $positioner = $this->getSpritePositioner();
        $generator = $this->getImageGenerator();

        $images = $positioner->calculate($images, $padding);
        return $generator->generate($images, $resultImage, $positioner);
    }

    /**
     * @return SpritePositionerInterface
     */
    protected function getSpritePositioner()
    {
        switch ($this->getConfigParam('imagePositioning')) {
            case 'one-column':
                $positioner = new OneColumnPositioner();
                break;
            case 'min-image':
                $positioner = new MinImageSizePositioner();
                break;
        }

        return $positioner;
    }

    /**
     * @return ImageGeneratorInterface
     */
    protected function getImageGenerator()
    {
        switch ($this->getConfigParam('imageGenerator')) {
            case 'gd2':
                $generator = new Gd2Generator();
                break;
        }

        return $generator;
    }

    /**
     * @param $images
     * @throws \SpriteGenerator\Exception\SpriteException
     * @return bool
     */
    protected function createSpriteCss($images)
    {
        $formatter = $this->getCssFormatter();
        $spriteClass = $this->getConfigParam('spriteClass');
        $spriteImageName = $this->getRelativeSpriteImageUrl($images);
        $formattedCss = $formatter->format($images, $spriteClass, $spriteImageName);

        $resultCss = $this->getConfigParam('outCss');

        $saved = file_put_contents($resultCss, $formattedCss);
        if ($saved === false) {
            throw new SpriteException('Saving CSS failed. Maybe "'.$resultCss.'" does not have write permissions?');
        }

        return true;
    }

    /**
     * @param $images
     * @throws \SpriteGenerator\Exception\SpriteException
     * @return bool
     */
    protected function createSpriteJson($images)
    {
        $formatter = $this->getJsonFormatter();
        $spriteClass = $this->getConfigParam('spriteClass');
        $spriteImageName = $this->getRelativeSpriteImageUrl($images);
        $formattedJson = $formatter->format($images, $spriteClass, $spriteImageName);

        $resultJson = $this->getConfigParam('outJson');

        $saved = file_put_contents($resultJson, $formattedJson);
        if ($saved === false) {
            throw new SpriteException('Saving JSON failed. Maybe "'.$resultJson.'" does not have write permissions?');
        }

        return true;
    }

    /**
     * @param $images
     * @return string
     */
    protected function getRelativeSpriteImageUrl($images)
    {
        $imageHash = substr(md5(serialize($images)), 10, 20);

        $spriteImageName = $this->getConfigParam('relativeImagePath');
        $spriteImageName .= basename($this->getConfigParam('outImage'));
        $spriteImageName .= '?' . $imageHash;

        return $spriteImageName;
    }

    /**
     * @return CssFormatterInterface
     */
    protected function getCssFormatter()
    {
        switch ($this->getConfigParam('cssFormat')) {
            case 'css':
                $formatter = new PlainCssFormatter();
                break;
            case 'sass':
                $formatter = new SassFormatter();
                break;
        }

        return $formatter;
    }

    protected function getJsonFormatter()
    {
        switch ($this->getConfigParam('jsonFormat')) {
            case 'hash':
                $formatter = new \SpriteGenerator\CssFormatter\JsonHashFormatter();
                break;
        }

        return $formatter;
    }
}
