<?php

namespace Playbloom\Satisfy\Controllers\Admin;

use Playbloom\Satisfy\Controllers\AbstractController;
use Playbloom\Satisfy\Forms\Types\ComposerLockType;
use Symfony\Component\HttpFoundation\Request;

class UploadController extends AbstractController
{
    public function create()
    {
        $form = $this->app['form.factory']->create(new ComposerLockType());

        return $this->app['twig']->render(
            'upload.html.twig',
            ['form' => $form->createView()]
        );
    }

    public function store(Request $request)
    {
        $form = $this->app['form.factory']->create(new ComposerLockType());

        $form->bind($request);

        if ($form->isValid()) {
            $lockFile = $form['file']->getData()->openFile();
            $this->app['satis.lock']->processFile($lockFile);

            return $this->app->redirect(
                $this->app['url_generator']->generate('admin_index')
            );
        }

        return $this->app['twig']->render(
            'upload.html.twig',
            ['form' => $form->createView()]
        );
    }
}
