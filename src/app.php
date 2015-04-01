<?php

/**
 * This file is part of modHelper
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/finders/ModFinder.php';
require_once __DIR__.'/YamlCache.php';

set_time_limit(0);

$app = new \Silex\Application(['debug' => true]);

$app['cache_dir'] = __DIR__.'/../var/cache/';
if (!is_dir($app['cache_dir'])) {
    mkdir($app['cache_dir'], 0777, true);
}

$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__.'/views',
]);

$app['modFinder'] = new ModFinder(new YamlCache($app['cache_dir'], 'mods'));

$app->get('/', function(Request $request) use($app) {
    if (!$request->query->has('userId')) {
        return $app['twig']->render('index.html.twig', ['mods' => []]);
    }

    $mods = $app['modFinder']->findMods($request->query->get('userId'));

    return $app['twig']->render('index.html.twig', ['mods' => $mods]);
});

$app->match("/import", function(Request $request) use($app) {
    if ($request->isMethod('GET')) {
        return $app['twig']->render('import.html.twig');
    }

    file_put_contents($app['cache_dir'].'xml.cache', $request->request->get('import'));

    return $app->redirect('/');
})->method('GET|POST');

return $app;