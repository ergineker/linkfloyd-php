<?php

namespace Linkfloyd\Bundle\CoreBundle\Controller;

use Linkfloyd\Bundle\CoreBundle\Form\InsertPostForm;
use Linkfloyd\Bundle\CoreBundle\Form\UpdatePostForm;
use Linkfloyd\Bundle\CoreBundle\Security\PostVoter;
use Linkfloyd\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class PostController extends BaseController
{
    public function indexAction()
    {
        return $this->render('LinkfloydCoreBundle:Post:index.html.twig', [
        ]);
    }

    public function insertPostAction(Request $request)
    {
        $this->denyAccessUnlessGranted(User::ROLE_DEFAULT);

        $form = $this->createForm(InsertPostForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $url = $form->get('url')->getData();
            $title = $form->get('title')->getData();

            $urlService = $this->get('linkfloyd.frontend.service.url_service');
            $urlDetails = $urlService->getUrlDetails($url);

            $postService = $this->get('linkfloyd.frontend.service.post.create_post_service');
            $post = $postService->insertPost(
                $urlDetails ?: ['url' => $url],
                $this->getUser(),
                $title,
                $description = null
            );
            if ($post) {
                $this->addFlash('success', $this->get('translator')->trans('post.message.success'));

                return $this->redirectToRoute('homepage', [
                    'post_id' => $post->getId(),
                ]);
            }
        }

        return $this->render('LinkfloydCoreBundle:Post:insert_post.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function editPostAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted(User::ROLE_DEFAULT);
        $postService = $this->get('linkfloyd.frontend.service.post_service');

        $post = $postService->getPost($id);
        if (!$post) {
            $this->addFlash('danger', $this->get('translator')->trans('post.not_found'));

            return $this->redirectToRoute('homepage');
        }
        $this->denyAccessUnlessGranted(PostVoter::EDIT, $post);

        $form = $this->createForm(UpdatePostForm::class);
        $form->setData([
            'url' => $post->getLinkDetail()->getUrl(),
            'title' => $post->getDetail()->getTitle(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $title = $form->get('title')->getData();

            $postDetailService = $this->get('linkfloyd.frontend.service.post_detail_service');
            $postDetailService->updatePostDetail($post->getDetail(), $title);

            $this->addFlash('success', $this->get('translator')->trans('post.message.edit_success'));

            return $this->redirectToRoute('homepage', [
                'post_id' => $post->getId(),
            ]);
        }

        return $this->render('LinkfloydCoreBundle:Post:update_post.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    public function deletePostAction(int $id)
    {
        $this->denyAccessUnlessGranted(User::ROLE_DEFAULT);

        $postService = $this->get('linkfloyd.frontend.service.post_service');
        $post = $postService->getPost($id);
        if (!$post) {
            $this->addFlash('danger', $this->get('translator')->trans('post.not_found'));

            return $this->redirectToRoute('homepage'); //todo
        }

        $this->denyAccessUnlessGranted(PostVoter::DELETE, $post);

        $postService->deletePost($post);

        $this->addFlash('info', $this->get('translator')->trans('post.message.delete'));

        return $this->redirectToRoute('homepage'); //todo
    }
}
