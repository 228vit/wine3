<?php

namespace App\Controller\Admin;

use App\Entity\EventVisitor;
use App\Filter\EventFilter;
use App\Form\EventVisitorType;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\EventVisitorRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class AdminEventVisitorController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'event_visitor';
    CONST ENTITY_NAME = 'EventVisitor';
    CONST NS_ENTITY_NAME = 'App:EventVisitor';


    /**
     * Lists all event_visitor entities.
     *
     * @Route("backend/event_visitor/index", name="backend_event_visitor_index", methods={"GET"})
     */
    public function index(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, EventFilter::class, 'id', 'DESC');

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
                    'sorting_field' => 'event_visitor.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'event_visitor.name',
                    'sortable' => true,
                ],
                'a.company' => [
                    'title' => 'Company',
                    'row_field' => 'company',
                    'sorting_field' => 'event_visitor.company',
                    'sortable' => false,
                ],
                'a.event' => [
                    'title' => 'Event',
                    'row_field' => 'event',
                    'sorting_field' => 'event_visitor.event',
                    'sortable' => false,
                ],
                'a.isChecked' => [
                    'title' => 'Checked',
                    'row_field' => 'isChecked',
                    'sorting_field' => 'event_visitor.isChecked',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new event_visitor entity.
     *
     * @Route("backend/event_visitor/new", name="backend_event_visitor_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EventRepository $repository, EntityManagerInterface $em)
    {
        $event = new EventVisitor();
        $form = $this->createForm(EventVisitorType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_event_visitor_edit', array('id' => $event->getId()));
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

    /**
     * Finds and displays a event_visitor entity.
     *
     * @Route("backend/event_visitor/{id}", name="backend_event_visitor_show", methods={"GET"})
     */
    public function showAction(EventVisitor $event)
    {
        $deleteForm = $this->createDeleteForm($event);

        return $this->render('admin/event_visitor/show.html.twig', array(
            'event_visitor' => $event,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing event_visitor entity.
     *
     * @Route("backend/event_visitor/{id}/edit", name="backend_event_visitor_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, EventVisitor $event, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($event);
        $editForm = $this->createForm(EventVisitorType::class, $event);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_event_visitor_edit', array('id' => $event->getId()));
        }
        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/common/edit.html.twig', array(
            'row' => $event,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a event_visitor entity.
     *
     * @Route("backend/event_visitor/{id}/delete", name="backend_event_visitor_delete", methods={"POST"})
     */
    public function delete(Request $request, EventVisitor $row, EventVisitorRepository $repository)
    {
        if ($this->isCsrfTokenValid('delete'.$row->getId(), $request->request->get('_token'))) {
            $repository->remove($row);
            $this->addFlash('success', 'Record was successfully deleted!');

            return $this->redirectToRoute('backend_event_visitor_index');
        } else {
            $this->addFlash('danger', 'Bad request!');

            return $this->redirectToRoute('backend_event_visitor_edit', ['id' => $row->getId()]);
        }
    }

    /**
     * Creates a form to delete a event_visitor entity.
     *
     * @param EventVisitor $event The event_visitor entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(EventVisitor $event)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_event_visitor_delete', array('id' => $event->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
