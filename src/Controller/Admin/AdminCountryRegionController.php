<?php

namespace App\Controller\Admin;

use App\Entity\CountryRegion;
use App\Filter\CountryRegionFilter;
use App\Form\CountryRegionType;
use App\Repository\CountryRegionRepository;
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

class AdminCountryRegionController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10000;
    CONST MODEL = 'country_region';
    CONST ENTITY_NAME = 'CountryRegion';
    CONST NS_ENTITY_NAME = 'App:CountryRegion';

    /**
     * Lists all country_region entities.
     *
     * @Route("backend/country_region/index", name="backend_country_region_index", methods={"GET","HEAD"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, CountryRegionFilter::class,
            'name', 'ASC');


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
                    'sorting_field' => 'country_region.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'country_region.name',
                    'sortable' => true,
                ],
                'a.country' => [
                    'title' => 'Country',
                    'row_field' => 'country',
                    'sorting_field' => 'country_region.country',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new country_region entity.
     *
     * @Route("backend/country_region/new", name="backend_country_region_new", methods={"GET","POST"})
     */
    public function newAction(Request $request, ValidatorInterface $validator)
    {
        $country_region = new CountryRegion();
        $form = $this->createForm(CountryRegionType::class, $country_region);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($country_region);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_country_region_edit', array('id' => $country_region->getId()));
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'country_region' => $country_region,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Finds and displays a country_region entity.
     *
     * @Route("backend/country_region/{id}", name="backend_country_region_show", methods={"GET"})
     */
    public function showAction(CountryRegion $country_region)
    {
        $deleteForm = $this->createDeleteForm($country_region);

        return $this->render('admin/common/show.html.twig', array(
            'country_region' => $country_region,
            'delete_form' => $deleteForm->createView(),
        ));
    }



    /**
     * Displays a form to edit an existing country_region entity.
     *
     * @Route("backend/country_region/{id}/edit", name="backend_country_region_edit", methods={"GET","POST"})
     */
    public function editAction(Request $request, CountryRegion $country_region, FileUploader $fileUploader)
    {
        $editForm = $this->createForm(CountryRegionType::class, $country_region);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $country_region->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $country_region->setFlagPic($fileName);
            }

            $this->em->persist($country_region);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_country_region_edit', array('id' => $country_region->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $deleteForm = $this->createDeleteForm($country_region);

        return $this->render('admin/common/edit.html.twig', array(
            'row' => $country_region,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a country_region entity.
     *
     * @Route("backend/country_region/{id}", name="backend_country_region_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, CountryRegion $country_region, FileUploader $fileUploader)
    {
        $filter_form = $this->createDeleteForm($country_region);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $country_region->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $country_region->setFlagPic($fileName);
            }


            $this->em->remove($country_region);
            $this->em->flush($country_region);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_country_region_index');
    }

    /**
     * Creates a form to delete a country_region entity.
     */
    private function createDeleteForm(CountryRegion $country_region) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_country_region_delete', array('id' => $country_region->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
