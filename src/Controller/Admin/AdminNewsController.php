<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Filter\NewsFilter;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AdminNewsController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'news';
    CONST ENTITY_NAME = 'News';
    CONST NS_ENTITY_NAME = 'App:News';

    /**
     * Lists all News entities.
     *
     * @Route("backend/news/index", name="backend_news_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, NewsFilter::class);

        return $this->render('admin/news/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'news.id',
                    'sortable' => true,
                ],
                'a.title' => [
                    'title' => 'Title',
                    'row_field' => 'title',
                    'sorting_field' => 'news.title',
                    'sortable' => true,
                ],
            ]
        ));
    }

    private function makeSlug(News $news, NewsRepository $newsRepository)
    {
        $slug = Slugger::urlSlug(sprintf('%s',
            $news->getTitle()), array('transliterate' => true)
        );

        while($newsRepository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Creates a new News entity.
     *
     * @Route("backend/news/new", name="backend_news_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, FileUploader $fileUploader, NewsRepository $repository, EntityManagerInterface $em)
    {
        $news = new News();
        $form = $this->createForm('App\Form\NewsType', $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $news->getPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $news->setPic($fileName);
            }
            $news->setSlug($this->makeSlug($news, $repository));

            $em->persist($news);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_news_edit', array('id' => $news->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $news,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Finds and displays a News entity.
     *
     * @Route("backend/news/{id}", name="backend_news_show", methods={"GET"})
     */
    public function showAction(News $news)
    {
        $deleteForm = $this->createDeleteForm($news);

        return $this->render('admin/news/show.html.twig', array(
            'News' => $news,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing News entity.
     *
     * @Route("backend/news/{id}/edit", name="backend_news_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, News $news, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($news);
        $editForm = $this->createForm(NewsType::class, $news);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var UploadedFile $file */
            $file = $news->getPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $news->setPic($fileName);
            }

            $this->em->persist($news);
            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_news_edit', array('id' => $news->getId()));
        }
        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/news/edit.html.twig', array(
            'row' => $news,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a News entity.
     *
     * @Route("backend/news/{id}", name="backend_news_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, News $news)
    {
        $filter_form = $this->createDeleteForm($news);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($news);
            $em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_news_index');
    }

    /**
     * Creates a form to delete a News entity.
     *
     * @param News $news The News entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(News $news)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_news_delete', array('id' => $news->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
