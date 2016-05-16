<?php
namespace shirase55\glide\components;

use League\Glide\Http\RequestArgumentsResolver;

class Server extends \League\Glide\Server {

    /**
     * Get the cache path.
     * @param  mixed
     * @return string The cache path.
     */
    public function getCachePath()
    {
        $request = (new RequestArgumentsResolver())->getRequest(func_get_args());

        if ($params = $request->query->get('params')) {
            $path = $params.'/'.$this->getSourcePath($request);
        } else {
            $path = md5($this->getSourcePath($request).'?'.http_build_query($request->query->all()));
        }

        if ($this->cachePathPrefix) {
            $path = $this->cachePathPrefix.'/'.$path;
        }

        return $path;
    }
} 