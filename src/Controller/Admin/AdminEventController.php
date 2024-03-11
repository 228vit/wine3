<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\EventPic;
use App\Entity\EventProduct;
use App\Filter\EventFilter;
use App\Form\EventType;
use App\Repository\EventPicRepository;
use App\Repository\EventProductRepository;
use App\Repository\EventRepository;
use App\Repository\ProductRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AdminEventController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'event';
    CONST ENTITY_NAME = 'Event';
    CONST NS_ENTITY_NAME = 'App:Event';

    /**
     * @Route("backend/event/{id}/sort_products", name="backend_event_ajax_sort_products", methods={"GET"})
     */
    public function ajaxSortEventProducts(Request $request,
                                          Event $event,
                                          EventProductRepository $repository)
    {
        $elements = $request->query->get('elements');

        foreach ($elements as $i => $id) {
            $item = $repository->find($id);

            if (null === $item) continue;

            $item->setPosition($i+1);
            $this->em->persist($item);
        }
        $this->em->flush();

        return new JsonResponse(null, 200);
    }

    /**
     * @Route("backend/event/{id}/sort_pics", name="backend_event_ajax_sort_pics", methods={"GET"})
     */
    public function ajaxSortEventPics(Request $request,
                                      Event $event,
                                      EventPicRepository $repository)
    {
        $elements = $request->query->get('elements');

        foreach ($elements as $i => $id) {
            $item = $repository->find($id);

            if (null === $item) continue;

            $item->setPosition($i+1);
            $this->em->persist($item);
        }
        $this->em->flush();

        return new JsonResponse(null, 200);
    }

    /**
     * @Route("backend/event/{id}/add_pic", name="backend_event_ajax_add_pic", methods={"POST"})
     */
    public function ajaxAddPic(Request $request,
                               Event $event,
                               FileUploader $fileUploader)
    {
//        return new JsonResponse([
//            'message' => 'Empty file'
//        ], 400);

        $pos = $request->request->getInt('position', 1);
        $file = $request->files->get('newEventPic', null);// $event->getPicFile();

        if (null === $file) {
            return new JsonResponse([
                'message' => 'Empty file'
            ], 400);
        }

        $fileName = $fileUploader->uploadEventPic($file, 'event_pic');

        $eventPic = (new EventPic())
            ->setEvent($event)
            ->setPosition($pos)
            ->setPic($fileName)
        ;
        $this->em->persist($eventPic);

        $event->addEventPic($eventPic);
        $this->em->persist($event);
        $this->em->flush();

        return new JsonResponse(['message' => 'success'], 200);
    }

    /**
     * @Route("backend/event/{id}/render_pics", name="backend_event_ajax_render_pics", methods={"GET"})
     */
    public function ajaxRenderPics(Request $request,
                                   Event $event)
    {
        return $this->render('admin/event/pics.html.twig', [
            $event->getEventPics(),
        ]);
    }

    /**
     * @Route("backend/event/{id}/add_product", name="backend_event_ajax_add_product", methods={"GET"})
     */
    public function ajaxAddProduct(Request $request,
                                   Event $event,
                                   EventRepository $eventRepository,
                                   ProductRepository $productRepository)
    {
        $productId = $request->query->get('product_id', null);
        $price = floatval($request->query->get('price', 0));
        $position = intval($request->query->get('position', 100));

        $product = $productRepository->find($productId);

        if (null === $product) {
            return new JsonResponse(null, 404);
        }
        $eventProduct = (new EventProduct())
            ->setEvent($event)
            ->setProduct($product)
            ->setPrice($price)
            ->setPosition($position)
        ;
        $this->em->persist($eventProduct);
        // todo: products sortable?
        $event->addProduct($eventProduct);
        $this->em->persist($event);
        $this->em->flush();

        return $this->render('admin/event/products.html.twig', array(
            'row' => $event,
            'products' => $event->getProducts(),
        ));
    }

    /**
     * Lists all event entities.
     *
     * @Route("backend/event/index", name="backend_event_index", methods={"GET"})
     */
    public function index(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, EventFilter::class);

        return $this->render('admin/common/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'event.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'event.name',
                    'sortable' => true,
                ],
                'a.slug' => [
                    'title' => 'Slug',
                    'row_field' => 'slug',
                    'sorting_field' => 'event.slug',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new event entity.
     *
     * @Route("backend/event/new", name="backend_event_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EventRepository $repository, EntityManagerInterface $em)
    {
        $event = new Event();
        $form = $this->createForm('App\Form\EventType', $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setSlug($this->makeSlug($event, $repository));

            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_event_edit', array('id' => $event->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $event,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function makeSlug(Event $event, EventRepository $repository)
    {
        $slug = $event->getSlug() ?? Slugger::urlSlug($event->getName(), array('transliterate' => true));

        while($repository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Finds and displays a event entity.
     *
     * @Route("backend/event/{id}", name="backend_event_show", methods={"GET"})
     */
    public function showAction(Event $event)
    {
        $deleteForm = $this->createDeleteForm($event);

        return $this->render('admin/event/show.html.twig', array(
            'event' => $event,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing event entity.
     *
     * @Route("backend/event/{id}/edit", name="backend_event_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Event $event, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($event);
        $editForm = $this->createForm(EventType::class, $event);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
//            $file = $event->getPicFile();
//
//            if (null !== $file) {
//                $fileName = $fileUploader->upload($file);
//                $event->setPic($fileName);
//            }

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_event_edit', array('id' => $event->getId()));
        }
        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/event/edit.html.twig', array(
            'row' => $event,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a event entity.
     *
     * @Route("backend/event/{id}", name="backend_event_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Event $event)
    {
        $filter_form = $this->createDeleteForm($event);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($event);
            $em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_event_index');
    }

    /**
     * Creates a form to delete a event entity.
     *
     * @param Event $event The event entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Event $event)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_event_delete', array('id' => $event->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
