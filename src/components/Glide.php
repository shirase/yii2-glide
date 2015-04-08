<?php
namespace trntv\glide\components;

use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Glide\Api\Api;
use League\Glide\Api\Manipulator\Blur;
use League\Glide\Api\Manipulator\Brightness;
use League\Glide\Api\Manipulator\Contrast;
use League\Glide\Api\Manipulator\Filter;
use League\Glide\Api\Manipulator\Gamma;
use League\Glide\Api\Manipulator\Orientation;
use League\Glide\Api\Manipulator\Output;
use League\Glide\Api\Manipulator\Pixelate;
use League\Glide\Api\Manipulator\Rectangle;
use League\Glide\Api\Manipulator\Sharpen;
use League\Glide\Api\Manipulator\Size;
use League\Glide\Http\SignatureException;
use League\Glide\Http\SignatureFactory;
use League\Glide\Http\UrlBuilder;
use League\Glide\Http\UrlBuilderFactory;
use League\Glide\Server;
use Symfony\Component\HttpFoundation\Request;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 * @param $source \League\Flysystem\FilesystemInterface
 * @param $cache \League\Flysystem\FilesystemInterface
 * @param $server \League\Glide\Server
 * @param $httpSignature \League\Glide\Http\Signature
 * @param $urlBuilder \League\Glide\Http\UrlBuilderFactory
 */
class Glide extends Component
{
    /**
     * @var string
     */
    public $sourcePath;
    /**
     * @var string
     */
    public $sourcePathPrefix;
    /**
     * @var string
     */
    public $cachePath;
    /**
     * @var string
     */
    public $cachePathPrefix;
    /**
     * @var string
     */
    public $signKey;
    /**
     * @var string
     */
    public $maxImageSize;
    /**
     * @var string
     */
    public $baseUrl;
    /**
     * @var string
     */
    public $urlManager = 'urlManager';

    /**
     * @var
     */
    protected $source;
    /**
     * @var
     */
    protected $cache;
    /**
     * @var
     */
    protected $server;
    /**
     * @var
     */
    protected $httpSignature;
    /**
     * @var
     */
    protected $urlBuilder;

    /**
     * @return Server
     */
    public function getServer()
    {
        $imageManager = new ImageManager([
            'driver' => extension_loaded('imagick') ? 'imagick' : 'gd'
        ]);
        $manipulators = [
            new Size($this->maxImageSize),
            new Orientation(),
            new Rectangle(),
            new Brightness(),
            new Contrast(),
            new Gamma(),
            new Sharpen(),
            new Filter(),
            new Blur(),
            new Pixelate(),
            new Output()
        ];


        $api = new Api($imageManager, $manipulators);
        $server = new Server($this->getSource(), $this->getCache(), $api);
        if ($this->baseUrl !== null) {
            $server->setBaseUrl($this->baseUrl);
        }
        if ($this->sourcePathPrefix !== null) {
            $server->setSourcePathPrefix($this->sourcePathPrefix);
        }
        if ($this->cachePathPrefix !== null) {
            $server->setCachePathPrefix($this->cachePathPrefix);
        }
        return $server;
    }

    /**
     * @return Filesystem
     */
    public function getSource()
    {
        if (!$this->source && $this->sourcePath) {
           $this->source = new Filesystem(
               new Local(Yii::getAlias($this->sourcePath))
           );
        }
        return $this->source;
    }

    /**
     * @return Filesystem
     */
    public function getCache()
    {
        if (!$this->cache && $this->cachePath) {
            $this->cache = new Filesystem(
                new Local(Yii::getAlias($this->cachePath))
            );
        }
        return $this->cache;
    }

    /**
     * @param FilesystemInterface $source
     */
    public function setSource(FilesystemInterface $source)
    {
        $this->source = $source;
    }

    /**
     * @param FilesystemInterface $cache
     */
    public function setCache(FilesystemInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param UrlBuilder $urlBuider
     */
    public function setUrlBuilder(UrlBuilder $urlBuider)
    {
        $this->urlBuilder = $urlBuider;
    }

    /**
     * @return UrlBuilder
     */
    public function getUrlBuilder()
    {
        if (!$this->urlBuilder) {
            $this->urlBuilder = UrlBuilderFactory::create($this->baseUrl, $this->signKey);
        }
        return $this->urlBuilder;
    }

    /**
     * @return \League\Glide\Http\Signature
     * @throws InvalidConfigException
     */
    public function getHttpSignature()
    {
        if ($this->httpSignature === null) {
            if ($this->signKey === null) {
                throw new InvalidConfigException;
            }
            $this->httpSignature = SignatureFactory::create($this->signKey);
        }
        return $this->httpSignature;

    }

    /**
     * @param array $params
     * @return string
     * @throws InvalidConfigException
     */
    public function createSignedUrl(array $params)
    {
        $route = ArrayHelper::getValue($params, 0);
        if ($this->getUrlManager()->enablePrettyUrl) {
            $showScriptName = $this->getUrlManager()->showScriptName;
            if ($showScriptName) {
                $this->getUrlManager()->showScriptName = false;
            }
            $resultUrl = $this->getUrlManager()->createAbsoluteUrl($params);
            $this->getUrlManager()->showScriptName = $showScriptName;
            $path = parse_url($resultUrl, PHP_URL_PATH);
            parse_str(parse_url($resultUrl, PHP_URL_QUERY), $urlParams);
        } else {
            $path = '/index.php';
            unset($params[0]);
            $urlParams = $params;
        }
        $signature = $this->getHttpSignature()->generateSignature($path, $urlParams);
        $params['s'] = $signature;
        $params[0] = $route;
        return $this->getUrlManager()->createUrl($params);
    }

    /**
     * @param $path
     * @param $params
     */
    public function signUrl($path, $params)
    {
        $this->getUrlBuilder()->getUrl($path, $params);
    }

    /**
     * @param Request $request
     * @return bool
     * @throws InvalidConfigException
     */
    public function validateRequest(Request $request)
    {
        if ($this->signKey !== null) {
            $httpSignature = $this->getHttpSignature();
            try {
                $httpSignature->validateRequest($request);
            } catch (SignatureException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return null|\yii\web\UrlManager
     * @throws InvalidConfigException
     */
    public function getUrlManager()
    {
        return Yii::$app->get($this->urlManager);
    }
}