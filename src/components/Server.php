<?php
namespace shirase55\glide\components;

class Server extends \League\Glide\Server {

    /**
     * Get cache path.
     * @param  string $path   Image path.
     * @param  array  $params Image manipulation params.
     * @return string Cache path.
     */
    public function getCachePath($path, array $params = [])
    {
        $sourcePath = $this->getSourcePath($path);

        if ($this->sourcePathPrefix) {
            $sourcePath = substr($sourcePath, strlen($this->sourcePathPrefix) + 1);
        }

        $params = $this->getAllParams($params);
        unset($params['s'], $params['p']);
        ksort($params);

        if (isset($params['params'])) {
            $cachedPath = $params['params'].'/'.$sourcePath;
        } else {
            $cachedPath = md5($sourcePath.'?'.http_build_query($params));
        }

        /*$md5 = md5($sourcePath.'?'.http_build_query($params));

        $cachedPath = $this->groupCacheInFolders ? $sourcePath.'/'.$md5 : $md5;*/

        if ($this->cachePathPrefix) {
            $cachedPath = $this->cachePathPrefix.'/'.$cachedPath;
        }

        if ($this->cacheWithFileExtensions) {
            $cachedPath .= '.'.(isset($params['fm']) ? $params['fm'] : pathinfo($path)['extension']);
        }

        return $cachedPath;
    }
} 