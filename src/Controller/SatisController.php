<?php

namespace Playbloom\Controller;

use Playbloom\Event\BuildEvent;
use Playbloom\Queue\BuildQueue;
use Playbloom\Runner\SatisBuildRunner;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SatisController extends AbstractProtectedController
{
    #[Route('/admin/satis/build', name: 'satis_build', methods: ['GET', 'POST'])]
    public function buildAction(Request $request, EventDispatcherInterface $dispatcher, BuildQueue $queue, SatisBuildRunner $runner): Response
    {
        $this->checkAccess();

        $form = $this->createFormBuilder()
            ->add('build', SubmitType::class, ['label' => 'Queue a full build'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $event = new BuildEvent();
            $dispatcher->dispatch($event);
            if ($event->isQueued()) {
                $this->addFlash('success', 'Full build queued');
            } else {
                $this->addFlash('warning', 'The build was not queued');
            }

            return $this->redirectToRoute('satis_build');
        }

        return $this->render('views/satis_build.html.twig', [
            'form' => $form->createView(),
            'build' => self::buildState($queue, $runner),
        ]);
    }

    #[Route('/admin/satis/build/status', name: 'satis_build_status', methods: ['GET'])]
    public function statusAction(BuildQueue $queue, SatisBuildRunner $runner): JsonResponse
    {
        $this->checkAccess();

        return new JsonResponse(self::buildState($queue, $runner));
    }

    /**
     * What the build page, its status endpoint and the admin home page show.
     *
     * @return array{pending: int, building: bool, log: string, updated: int|null}
     */
    public static function buildState(BuildQueue $queue, SatisBuildRunner $runner): array
    {
        return [
            'pending' => $queue->pending(),
            'building' => $queue->isBuilding(),
            'log' => $runner->getLastLog(),
            'updated' => $runner->getLastLogTime(),
        ];
    }
}
