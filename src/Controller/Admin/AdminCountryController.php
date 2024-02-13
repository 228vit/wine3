<?php

namespace App\Controller\Admin;

use App\Entity\Country;
use App\Filter\CountryFilter;
use App\Form\CountryType;
use App\Repository\CountryRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminCountryController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10000;
    CONST MODEL = 'country';
    CONST ENTITY_NAME = 'Country';
    CONST NS_ENTITY_NAME = 'App:Country';

    /**
     * Lists all country entities.
     *
     * @Route("backend/country/index", name="backend_country_index", methods={"GET","HEAD"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, CountryFilter::class,
            'name', 'ASC');


        return $this->render('admin/country/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'country.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'country.name',
                    'sortable' => true,
                ],
                'a.codeAlpha2' => [
                    'title' => 'Code',
                    'row_field' => 'codeAlpha2',
                    'sorting_field' => 'country.codeAlpha2',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new country entity.
     *
     * @Route("backend/country/new", name="backend_country_new", methods={"GET","POST"})
     */
    public function newAction(Request $request, ValidatorInterface $validator)
    {
        $country = new Country();
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($country);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_country_edit', array('id' => $country->getId()));
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'country' => $country,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Finds and displays a country entity.
     *
     * @Route("backend/country/{id}", name="backend_country_show", methods={"GET"})
     */
    public function showAction(Country $country)
    {
        $deleteForm = $this->createDeleteForm($country);

        return $this->render('admin/country/show.html.twig', array(
            'country' => $country,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * @Route("backend/country/default/wp", name="backend_country_wp", methods={"GET"})
     */
    public function setDefaultWP(EntityManagerInterface $entityManager)
    {
        $queryBuilder = $entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->update('App:Country', 'c')
            ->set('c.worldPart', ':wp')
            ->setParameter('wp', 'old_world')
            ->getQuery();

        $query ->execute();

        return new Response('done');
    }


    /**
     * @Route("backend/country/toggle/wp", name="ajax_country_toggle_field", methods={"GET"})
     */
    public function ajaxToggleFieldAction(Request $request, EntityManagerInterface $em)
    {
        $id = $request->query->get('id', false);
        $value = $request->query->get('value', false);

        /** @var Country $country */
        $country = $em->getRepository(Country::class)->find($id);

        if (false === $country) {
            return new JsonResponse('Wrong ID', 400);
        }

        $country->setWorldPart($value);
        $em->persist($country);
        $em->flush();

        return new Response('ok', 200);
    }


    /**
     * Displays a form to edit an existing country entity.
     *
     * @Route("backend/country/{id}/edit", name="backend_country_edit", methods={"GET","POST"})
     */
    public function editAction(Request $request, Country $country, FileUploader $fileUploader)
    {
        $editForm = $this->createForm(CountryType::class, $country);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $country->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $country->setFlagPic($fileName);
            }

            $this->em->persist($country);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_country_edit', array('id' => $country->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $deleteForm = $this->createDeleteForm($country);

        return $this->render('admin/country/edit.html.twig', array(
            'row' => $country,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a country entity.
     *
     * @Route("backend/country/{id}", name="backend_country_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Country $country, FileUploader $fileUploader)
    {
        $filter_form = $this->createDeleteForm($country);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $country->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $country->setFlagPic($fileName);
            }


            $this->em->remove($country);
            $this->em->flush($country);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_country_index');
    }

    /**
     * Creates a form to delete a country entity.
     */
    private function createDeleteForm(Country $country) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_country_delete', array('id' => $country->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
