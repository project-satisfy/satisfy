<?php

namespace Playbloom\Satisfy\Controller;

use Playbloom\Satisfy\Form\Type\ComposerLockType;
use Playbloom\Satisfy\Form\Type\DeleteFormType;
use Playbloom\Satisfy\Form\Type\RepositoryType;
use Playbloom\Satisfy\Model\Repository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RepositoryController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $this->checkAccess();
        $config = $this->get('satisfy.manager')->getConfig();
        $repositories = $this->get('satisfy.manager')->getRepositories();
        $satisRepository = array(
            'type' => 'composer',
            'url'  => $config->getHomepage(),
        );

        $config = array(
            'repositories' => array($satisRepository),
        );

        return $this->render('@PlaybloomSatisfy/home.html.twig', compact('config', 'repositories'));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $this->checkAccess();

        $repository = new Repository();
        $form = $this->createForm(
            RepositoryType::class,
            $repository
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('satisfy.manager')->add($form->getData());

                return $this->redirectToRoute('repository');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('@PlaybloomSatisfy/new.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function uploadAction(Request $request)
    {
        $this->checkAccess();

        $form = $this->createForm(ComposerLockType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $lock = $form->get('file')->getData()->openFile();
                $this->get('satisfy.processor.lock_processor')->processFile($lock);

                return $this->redirectToRoute('repository');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('@PlaybloomSatisfy/upload.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function editAction(Request $request)
    {
        $this->checkAccess();

        $repository = $this->get('satisfy.manager')->findOneRepository($request->attributes->get('repository'));
        if (!$repository) {
            return $this->redirectToRoute('repository');
        }

        $form = $this->createForm(
            RepositoryType::class,
            clone $repository
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('satisfy.manager')->update($repository, $form->getData()->getUrl());

                return $this->redirectToRoute('repository');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('@PlaybloomSatisfy/edit.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $this->checkAccess();

        $repository = $this->get('satisfy.manager')->findOneRepository($request->attributes->get('repository'));
        if (!$repository) {
            return $this->redirectToRoute('repository');
        }

        $form = $this->createForm(DeleteFormType::class);
        if ($request->getMethod() === Request::METHOD_DELETE) {
            try {
                $this->get('satisfy.manager')->delete($repository);

                return $this->redirectToRoute('repository');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render(
            '@PlaybloomSatisfy/delete.html.twig',
            array('form' => $form->createView(), 'repository' => $repository)
        );
    }

    /**
     * Check admin access.
     */
    protected function checkAccess()
    {
        if (!$this->getParameter('admin.auth')) {
            return;
        }

        parent::denyAccessUnlessGranted('ROLE_ADMIN');
    }
}
