<?php

declare(strict_types=1);

/*
 * This file is part of the Explicit Architecture POC,
 * which is created on top of the Symfony Demo application.
 *
 * (c) Herberto Graça <herberto.graca@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Acme\App\Presentation\Web\Core\Component\Blog\Admin\Post;

use Acme\App\Core\Component\Blog\Application\Repository\PostRepositoryInterface;
use Acme\App\Core\Component\Blog\Application\Service\PostService;
use Acme\App\Core\Component\Blog\Domain\Entity\PostId;
use Acme\App\Core\Port\Router\UrlGeneratorInterface;
use Acme\App\Presentation\Web\Core\Port\Auth\AuthenticationServiceInterface;
use Acme\App\Presentation\Web\Core\Port\Auth\AuthorizationServiceInterface;
use Acme\App\Presentation\Web\Core\Port\FlashMessage\FlashMessageServiceInterface;
use Acme\App\Presentation\Web\Core\Port\Form\FormFactoryInterface;
use Acme\App\Presentation\Web\Core\Port\Response\ResponseFactoryInterface;
use Acme\App\Presentation\Web\Core\Port\TemplateEngine\TemplateEngineInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller used to manage blog contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes. However, in your real Symfony application you should use any of the
 * existing bundles that let you generate ready-to-use backends without effort.
 *
 * See http://knpbundles.com/keyword/admin
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Herberto Graca <herberto.graca@gmail.com>
 */
class PostController
{
    /**
     * @var PostService
     */
    private $postService;

    /**
     * @var FlashMessageServiceInterface
     */
    private $flashMessageService;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var TemplateEngineInterface
     */
    private $templateEngine;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PostRepositoryInterface
     */
    private $postRepository;

    /**
     * @var AuthorizationServiceInterface
     */
    private $authorizationService;

    /**
     * @var AuthenticationServiceInterface
     */
    private $authenticationService;

    public function __construct(
        PostService $postService,
        PostRepositoryInterface $postRepository,
        FlashMessageServiceInterface $flashMessageService,
        UrlGeneratorInterface $urlGenerator,
        TemplateEngineInterface $templateEngine,
        ResponseFactoryInterface $responseFactory,
        FormFactoryInterface $formFactory,
        AuthorizationServiceInterface $authorizationService,
        AuthenticationServiceInterface $authenticationService
    ) {
        $this->postService = $postService;
        $this->flashMessageService = $flashMessageService;
        $this->urlGenerator = $urlGenerator;
        $this->templateEngine = $templateEngine;
        $this->responseFactory = $responseFactory;
        $this->formFactory = $formFactory;
        $this->postRepository = $postRepository;
        $this->authorizationService = $authorizationService;
        $this->authenticationService = $authenticationService;
    }

    /**
     * Finds and displays a Post entity.
     */
    public function getAction(ServerRequestInterface $request): ResponseInterface
    {
        $post = $this->postRepository->find(new PostId($request->getAttribute('id')));
        $this->authorizationService->denyAccessUnlessGranted(
            [],
            'show',
            'Posts can only be shown to their authors.',
            $post
        );

        return $this->templateEngine->renderResponse(
            '@Blog/Admin/Post/get.html.twig',
            GetViewModel::fromPost($post)
        );
    }

    /**
     * Displays a form to edit an existing Post entity.
     */
    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $post = $this->postRepository->find(new PostId($request->getAttribute('id')));

        $this->authorizationService->denyAccessUnlessGranted(
            [],
            'edit',
            'Posts can only be edited by their authors.',
            $post
        );

        $form = $this->formFactory->createEditPostForm(
            $post,
            ['action' => $this->urlGenerator->generateUrl('admin_post_post', ['id' => (string) $post->getId()])]
        );

        return $this->templateEngine->renderResponse(
            '@Blog/Admin/Post/edit.html.twig',
            EditViewModel::fromPostAndForm($post, $form)
        );
    }

    /**
     * Receives data from the form to edit an existing Post entity.
     */
    public function postAction(ServerRequestInterface $request): ResponseInterface
    {
        $post = $this->postRepository->find(new PostId($request->getAttribute('id')));

        $this->authorizationService->denyAccessUnlessGranted(
            [],
            'edit',
            'Posts can only be edited by their authors.',
            $post
        );

        $form = $this->formFactory->createEditPostForm($post);
        $form->handleRequest($request);

        if (!($form->shouldBeProcessed())) {
            return $this->responseFactory->redirectToRoute('admin_post_edit', ['id' => (string) $post->getId()]);
        }

        $this->postService->update($post);

        $this->flashMessageService->success('post.updated_successfully');

        return $this->responseFactory->redirectToRoute('admin_post_edit', ['id' => (string) $post->getId()]);
    }

    /**
     * Deletes a Post entity.
     *
     * The Security annotation value is an expression (if it evaluates to false,
     * the authorization mechanism will prevent the user accessing this resource).
     */
    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $post = $this->postRepository->find(new PostId($request->getAttribute('id')));

        $this->authorizationService->denyAccessUnlessGranted(
            [],
            'delete',
            'Posts can only be deleted by an admin or the author.',
            $post
        );

        if (!$this->authenticationService->isCsrfTokenValid('delete', $request->getParsedBody()['token'] ?? '')) {
            return $this->responseFactory->redirectToRoute('admin_post_list');
        }

        $this->postService->delete($post);

        $this->flashMessageService->success('post.deleted_successfully');

        return $this->responseFactory->redirectToRoute('admin_post_list');
    }
}
