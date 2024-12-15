<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\EventOrganizer;
use App\Filter\EventFilter;
use App\Form\EventOrganizerType;
use App\Form\EventType;
use App\Repository\EventOrganizerRepository;
use App\Repository\EventRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class AdminEventOrganizerController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'event_organizer';
    CONST ENTITY_NAME = 'EventOrganizer';
    CONST NS_ENTITY_NAME = 'App:EventOrganizer';


    /**
     * Lists all event_organizer entities.
     *
     * @Route("backend/event_organizer/index", name="backend_event_organizer_index", methods={"GET"})
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
                    'sorting_field' => 'event_organizer.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'event_organizer.name',
                    'sortable' => true,
                ],
                'a.person' => [
                    'title' => 'Contact person',
                    'row_field' => 'person',
                    'sorting_field' => 'event_organizer.person',
                    'sortable' => false,
                ],
                'a.isChecked' => [
                    'title' => 'Checked',
                    'row_field' => 'isChecked',
                    'sorting_field' => 'event_organizer.isChecked',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new event_organizer entity.
     *
     * @Route("backend/event_organizer/new", name="backend_event_organizer_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EventRepository $repository, EntityManagerInterface $em)
    {
        $event = new EventOrganizer();
        $form = $this->createForm(EventOrganizerType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_event_organizer_edit', array('id' => $event->getId()));
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
     * Finds and displays a event_organizer entity.
     *
     * @Route("backend/event_organizer/{id}", name="backend_event_organizer_show", methods={"GET"})
     */
    public function showAction(EventOrganizer $event)
    {
        $deleteForm = $this->createDeleteForm($event);

        return $this->render('admin/event_organizer/show.html.twig', array(
            'event_organizer' => $event,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing event_organizer entity.
     *
     * @Route("backend/event_organizer/{id}/edit", name="backend_event_organizer_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, EventOrganizer $event, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($event);
        $editForm = $this->createForm(EventOrganizerType::class, $event);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_event_organizer_edit', array('id' => $event->getId()));
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
     * Deletes a event_organizer entity.
     *
     * @Route("backend/event_organizer/{id}/delete", name="backend_event_organizer_delete", methods={"POST"})
     */
    public function delete(Request $request, EventOrganizer $row, EventOrganizerRepository $repository)
    {
        if ($this->isCsrfTokenValid('delete'.$row->getId(), $request->request->get('_token'))) {
            $repository->remove($row);
            $this->addFlash('success', 'Record was successfully deleted!');

            return $this->redirectToRoute('backend_event_organizer_index');
        } else {
            $this->addFlash('danger', 'Bad request!');

            return $this->redirectToRoute('backend_event_organizer_edit', ['id' => $row->getId()]);
        }
    }


    /**
     * Creates a form to delete a event_organizer entity.
     *
     * @param EventOrganizer $event The event_organizer entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(EventOrganizer $event)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_event_organizer_delete', array('id' => $event->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
