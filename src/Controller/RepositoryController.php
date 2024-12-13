<?php

namespace Playbloom\Controller;

use Playbloom\Form\Type\ComposerLockType;
use Playbloom\Form\Type\DeleteFormType;
use Playbloom\Form\Type\RepositoryType;
use Playbloom\Model\Repository;
use Playbloom\Service\LockProcessor;
use Playbloom\Service\Manager;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RepositoryController extends AbstractProtectedController
{
    /** @var Manager */
    private $manager;

    /** @var LockProcessor */
    private $lockProcessor;

    public function __construct(Manager $manager, LockProcessor $lockProcessor)
    {
        $this->manager = $manager;
        $this->lockProcessor = $lockProcessor;
    }

    #[Route('/admin', name: 'repository', methods: ['GET'])]
    public function indexAction(): Response
    {
        $this->checkAccess();
        $this->checkEnvironment();

        $config = $this->manager->getConfig();
        $repositories = $this->manager->getRepositories();
        $satisRepository = [
            'type' => 'composer',
            'url' => $config->getHomepage(),
        ];

        $config = [
            'repositories' => [$satisRepository],
        ];

        return $this->render('@PlaybloomSatisfy/home.html.twig', compact('config', 'repositories'));
    }

    #[Route('/admin/new', name: 'repository_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request): Response
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
                $this->manager->add($form->getData());
                $this->addFlash('success', 'New repository added successfully');

                return $this->redirectToRoute('repository');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('@PlaybloomSatisfy/new.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @return RedirectResponse|Response
     */
    #[Route('/admin/upload', name: 'repository_upload', methods: ['GET', 'POST'])]
    public function uploadAction(Request $request): Response
    {
        $this->checkAccess();

        $form = $this->createForm(ComposerLockType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $lock = $form->get('file')->getData()->openFile();
                $this->lockProcessor->processFile($lock);
                $this->addFlash('success', 'Composer lock file parsed successfully');

                return $this->redirectToRoute('repository');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('@PlaybloomSatisfy/upload.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/edit/{repository}', name: 'repository_edit', requirements: ['repository' => '[a-zA-Z0-9_-]+'], methods: ['GET', 'POST'])]
    public function editAction(Request $request): Response
    {
        $this->checkAccess();
        $repository = $this->manager->findOneRepository($request->attributes->get('repository'));
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
                $this->manager->update($repository, $form->getData());
                $this->addFlash('success', 'Repository updated successfully');

                return $this->redirectToRoute('repository');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('@PlaybloomSatisfy/edit.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/delete/{repository}', name: 'repository_delete', requirements: ['repository' => '[a-zA-Z0-9_-]+'], methods: ['GET', 'DELETE'])]
    public function deleteAction(Request $request): Response
    {
        $this->checkAccess();
        $repository = $this->manager->findOneRepository($request->attributes->get('repository'));
        if (!$repository) {
            return $this->redirectToRoute('repository');
        }

        $form = $this->createForm(DeleteFormType::class);
        if (Request::METHOD_DELETE === $request->getMethod()) {
            try {
                $this->manager->delete($repository);
                $this->addFlash('success', 'Repository removed successfully');

                return $this->redirectToRoute('repository');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render(
            '@PlaybloomSatisfy/delete.html.twig',
            ['form' => $form->createView(), 'repository' => $repository]
        );
    }
}
