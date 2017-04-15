<?php

require 'vendor/autoload.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);

use ZipStream\ZipStream;
use Slim\App;
use YoutubeDl\YoutubeDl;
use Slim\Views\Twig;

// Create and configure Slim app
$app = new App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);
$container = $app->getContainer();
$container['view'] = function ($container) {
    $view = new Twig(__DIR__ . '/templates', [
        'cache' => false
    ]);
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
    return $view;
};

$app->get('/', function($request, $response, $args) {
    return $this->view->render($response, 'index.html', [
        'name' => "index"
    ]);
});

$app->post('/download', function ($request, $response, $args) {

    $zip = new ZipStream('videos.zip');

    $id = mt_rand(1000000, 9999999);

    $dl = new YoutubeDl([
        'continue' => true,
        'extract-audio' => true,
        'audio-format' => 'mp3',
        'audio-quality' => 0, // best
        'output' => '%(title)s.%(ext)s',
    ]);
    mkdir("/tmp/video_youtube_$id/");
    $dl->setDownloadPath("/tmp/video_youtube_$id/");
    
    foreach (explode(PHP_EOL, $request->getParam("videos")) as $line){
	$line = trim($line);
	if (filter_var($line, FILTER_VALIDATE_URL)) {
	  $video = $dl->download($line);
	  
	  $zip->addFileFromPath($video->getFile()->getFilename(), $video->getFile()->getPath() . "/" . $video->getFile()->getFilename());
	} else {
	  die ($line . "is not a url to youtube");
	}

    }
    
    
    $zip->finish();

    foreach (glob("/tmp/video_youtube_$id/*.mp3") as $file) unlink($file);
    unlink("/tmp/video_youtube_$id/");
    
});

$app->run();