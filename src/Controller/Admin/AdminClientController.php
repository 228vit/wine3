<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Filter\ClientFilter;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AdminClientController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'client';
    CONST ENTITY_NAME = 'Client';
    CONST NS_ENTITY_NAME = 'App:Client';

    /**
     * Lists all Client entities.
     *
     * @Route("backend/client/index", name="backend_client_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, ClientFilter::class);

        return $this->render('admin/client/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'client.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'client.name',
                    'sortable' => true,
                ],
            ]
        ));
    }

    /**
     * Creates a new Client entity.
     *
     * @Route("backend/client/new", name="backend_client_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, ClientRepository $repository, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $client = new Client();
        $form = $this->createForm('App\Form\ClientType', $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $client->getPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $client->setPic($fileName);
            }

            $em->persist($client);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_client_edit', array('id' => $client->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $client,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function makeSlug(Client $client, ClientRepository $repository)
    {
        $slug = $client->getSlug() ?? Slugger::urlSlug($client->getName(), array('transliterate' => true));

        while($repository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Finds and displays a Client entity.
     *
     * @Route("backend/client/{id}", name="backend_client_show", methods={"GET"})
     */
    public function showAction(Client $client)
    {
        $deleteForm = $this->createDeleteForm($client);

        return $this->render('admin/client/show.html.twig', array(
            'Client' => $client,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Client entity.
     *
     * @Route("backend/client/{id}/edit", name="backend_client_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, Client $client, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($client);
        $editForm = $this->createForm(ClientType::class, $client);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var UploadedFile $file */
            $file = $client->getPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $client->setPic($fileName);
            }

            $this->em->persist($client);
            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_client_edit', array('id' => $client->getId()));
        }
        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/client/edit.html.twig', array(
            'row' => $client,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a Client entity.
     *
     * @Route("backend/client/{id}", name="backend_client_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Client $client)
    {
        $filter_form = $this->createDeleteForm($client);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($client);
            $em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_client_index');
    }

    /**
     * Creates a form to delete a Client entity.
     *
     * @param Client $client The Client entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Client $client)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_client_delete', array('id' => $client->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
