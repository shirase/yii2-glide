<?php

namespace shirase55\glide\controllers;

use yii\web\Controller;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 * With this controller you can create a simple
 * configurations like that @see https://github.com/shirase55/yii2-starter-kit/blob/master/storage/index.php
 */
class GlideController extends Controller
{
    public function actions()
    {
        return [
            'index' => [
                'class' => 'shirase55\glide\actions\GlideAction'
            ]
        ];
    }
}
